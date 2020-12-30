#!/bin/bash
#sis_snpshtFF_auto
# * ****************************************************************************
# * Copyright (c) 2013, Blackboard Inc.
# * All rights reserved.
# *
# * Redistribution and use in source and binary forms, with or without
# * modification, are permitted provided that the following conditions are met:
# *
# *   -- Redistributions of source code must retain the above copyright notice,
# *        this list of conditions and the following disclaimer.
# *
# *   -- Redistributions in binary form must reproduce the above copyright
# *        notice, this list of conditions and the following disclaimer in the
# *        documentation and/or other materials provided with the distribution.
# *
# *   -- Neither the name of Blackboard nor the names of its contributors may be
# *        used to endorse or promote products derived from this software
# *        without specific prior written permission.
# *
# * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
# * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
# * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
# * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
# * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
# * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
# * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
# * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
# * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
# * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# * POSSIBILITY OF SUCH DAMAGE.
# *
# * ***************************************************************************
 
#What it does
#	Targets a specified directory for stored Feed Files
#	Finds data file(s)
#	Orders processing - Users, Courses, Organizations, Memberships
#	Sequentially and in order calls sis_snpshtFF_manual.sh with 
#		appropriate arguments for processing the found file(s)
#	After processing, move processed file to an archive directory

#Variables:
#IMPORTANT!
#the following variables need to be set in order to use this script

#admin_email: comma separated list of administrators to receive emails
#eg: admin_email="bbadmin@mountcollege.edu,sisadmin@mountcollege.edu"
admin_email="zubair@utmspace.edu.my"

#ScriptPath: full path to manual script - store in same directory as sis_snpshtFF_auto 
#eg: ScriptPath="/usr/local/bin/ssff_scripts/sis_snpshtFF_manual.sh"
ScriptPath="/usr/src/myapp/src/sis_snpshtFF_manual.sh"

#DataFilePath: full path to data file directory eg: DataFilePath="/usr/local/blackboard/apps/snapshot/data/feeds/"
DataFilePath="/usr/src/myapp/data/"

#ArchiveFilePath: full path to data file archive directory eg: ArchiveFilePath="/usr/local/blackboard/apps/snapshot/data/sis_archives/"
ArchiveFilePath="/usr/src/myapp/archives/"

#AutoUser: the username as configured in the snapshot integration eg: AutoUser="13a29f6e-66e6-4ab9-ad4b-174d3ca71723"
AutoUser="a2ae284a-e63b-4e50-bbb8-2256ac38a91a"

#AutoPasswd: the password as configured in the snapshot integration eg: AutoPasswd="ssffpassword"
AutoPasswd="6N^ltz5@2@a0"

#AutoService: The default operation that this script will perform, used by sis_snpshtFF_manual to construct service endpoint URLs - store, refresh, refreshlegacy, delete
AutoService="store"

#script variables - do not change
#AutoObject: the object the feed file represents as discovered by 
AutoObject=""
#FeedType: used for labeling the incoming feed data for conditional processing
FeedType="unknown"
#FeedFile: the file for processing
FeedFile="unknown"
#myFileLock: the lock file which prevents simultaneous execution of this script
myFileLock="/var/tmp/script.lock"
reportMSG=""
filesprocessedcount=0

#set the lockfile
lockfile $myFileLock
# Function exits if cannot start due to lockfile or prior running instance. Cleans up lock in event of script failure.
trap "rm -f $myFileLock; exit" 0 1 2 3 15

#Functions:
#function to exit app and echo err message on critical errors.
function die {
	echo "$1"
	exit 1
}

#Mail Report
#function to mail a report of type and number of files processed (or whether there were no files)
function mailReport {
  if [[ $filesProcessedcount = 0 ]]; then
  	subject="[SIS_SNPSHTFF Report] No Files to Process :: $(date)"
  else 
  	subject="[SIS_SNPSHTFF Report] $filesprocessedcount Files Processed :: $(date)"
  fi

  if [[ $filesProcessedcount > 0 ]]; then
    reportMSG="$reportMSG\n\nYou should have received $filesprocessedcount emails containing the results of POSTED data."
  fi
  
  EMAILMESSAGE=$(echo -e $reportMSG)
  
  echo -e "$subject\n$EMAILMESSAGE"

  /bin/mail -s "$subject" "$admin_email" <<EOF
$EMAILMESSAGE
EOF
}

#contains: determines if the argument you passed is in the header of the feed file
function contains { shopt -s nocasematch; local line; read -r line < "$FeedFile" && [[ $line = *"$1"* ]]; }

#getFeedType: determines the object type for the feed file by inspecting the feed header
function getFeedType {
FeedFile=$1
   if contains external_course_key; then
	FeedType="course"
	if contains external_association_key; then
		FeedType="courseassociation"
	elif contains external_category_key; then
		FeedType="coursecategorymembership"
	elif contains external_person_key; then
		FeedType="membership"
	fi

   elif contains organization_key; then
	FeedType="organization"
	if contains external_association_key; then
		FeedType="organizationassociation"
	elif contains external_category_key; then
		FeedType="organizationcategorymembership"
	elif contains external_person_key; then
		FeedType="organizationmembership"
	fi

   elif contains external_category_key; then
	# need to supply whether this is an ORG or COURSE category
	# not sure by reading the file
	if [[ "$CATEGORY_TYPE" == "course" ]]; then
		FeedType="coursecategory"
	elif [[ "$CATEGORY_TYPE" == "org" ]]; then
		FeedType="organizationcategory"
	else
		die "Unknown category type, specify: course or org"
	fi

   elif contains std_sub_doc_key; then
	FeedType="standardsassociation"

   elif contains external_node_key; then
	FeedType="node"
	if contains external_user_key; then
		FeedType="userassociation"
	fi

   elif contains external_observer_key; then
	FeedType="associateobserver"

   elif contains external_person_key; then
	FeedType="person"
	if contains role_id; then
		FeedType="secondaryinstrole"
	fi

   elif contains external_term_key; then
	FeedType="term"

   else
	FeedType="unknown"
   fi

}

#Core code...
#Change to the configured data directory
cd $DataFilePath
#echo `pwd` #uncomment to print out the path

#get an array of files in our target path 
Files=(`ls $DataFilePath`)
#get the number of files to process
FilesSize=${#Files[@]} #Number of elements in the array

#set up empty arrays to hold the discovered feed files
#organize by types
CourseFiles=()
PersonFiles=()
MembershipFiles=()
#rinse repeat for additional feed types you wish to support as derived from the above.
#e.g.:SecondaryInstRoleFiles=()

#order the found files into a list for processing
counter=0;
while [ $counter -lt $FilesSize ]
do 
	FeedFile="$DataFilePath${Files[$counter]}"
	getFeedType "$FeedFile"
	
	case "$FeedType" in
		"course")	echo "Found course feed: $DataFilePath${Files[$counter]}"
					CourseFiles+="$FeedFile"
					line_no=$(awk '/([^[:space:]])+/ {x++} END {print x}' "$FeedFile")
					((line_no--))
					echo "$line_no records to process"
				;;
		"person") echo "Found person feed: $DataFilePath${Files[$counter]}"
					PersonFiles+="$FeedFile"
					line_no=$(awk '/([^[:space:]])+/ {x++} END {print x}' "$FeedFile")
					((line_no--))
					echo "$line_no records to process"
				;;
		"membership") echo "Found membership feed: $DataFilePath${Files[$counter]}"
					MembershipFiles+="$FeedFile"
					line_no=$(awk '/([^[:space:]])+/ {x++} END {print x}' "$FeedFile")
					((line_no--))
					echo "$line_no records to process"
				;;
		*) echo "Could not identify feed type from: $DataFilePath${Files[$counter]}"
	esac
	
	counter=$(( $counter + 1 ))
done

counter=0
if [ "${#CourseFiles[@]}" -gt 0 ]; then #there are course feeds to process
	echo "Course files to process=${#CourseFiles[@]}"
	reportMSG="$reportMSG Course files to process=${#CourseFiles[@]}\n"
	filesprocessedcount=$(($filesprocessedcount + ${#CourseFiles[@]}))
	AutoObject="course"
	
	until [[ $counter -eq "${#CourseFiles[@]}" ]]
	do
		fileToProcess="${CourseFiles[$counter]}"		
		command=$(echo "$ScriptPath $AutoUser $AutoPasswd $AutoObject $AutoService $fileToProcess")
		echo "Command $command"
		$command
		echo "Completed: $command"
		rm $fileToProcess
		# mv $fileToProcess $ArchiveFilePath$(basename "$fileToProcess")`date +_%Y%m%d-%H:%M:%S`
		counter=$(( $counter + 1 ))
	done
fi

counter=0
if [ "${#PersonFiles[@]}" -gt 0 ]; then
	echo "Person files to process=${#PersonFiles[@]}" 
	reportMSG="$reportMSG Person files to process=${#PersonFiles[@]}\n" 
	filesprocessedcount=$(($filesprocessedcount + ${#PersonFiles[@]}))
	AutoObject="person"
	until [[ $counter -eq "${#PersonFiles[@]}" ]]
	do
		fileToProcess="${PersonFiles[$counter]}"
		echo "Command to call: sis_snpshtFF.sh $AutoUser $AutoPasswd $AutoObject $AutoService $fileToProcess"
		command=$(echo "$ScriptPath $AutoUser $AutoPasswd $AutoObject $AutoService $fileToProcess")
		echo "Command $command"
		$command
		echo "Completed: $command"
		# mv $fileToProcess $ArchiveFilePath$(basename "$fileToProcess")`date +_%Y%m%d-%H:%M:%S`
		counter=$(( $counter + 1 ))
	done
fi

counter=0
if [ "${#MembershipFiles[@]}" -gt 0 ]; then
	echo "Membership files to process=${#MembershipFiles[@]}" 
	reportMSG="$reportMSG Membership files to process=${#MembershipFiles[@]}" 
	filesprocessedcount=$(($filesprocessedcount + ${#MembershipFiles[@]}))
	AutoObject="membership"
	until [[ $counter -eq "${#MembershipFiles[@]}" ]]
	do
		fileToProcess="${MembershipFiles[$counter]}"
		echo "Command to call: sis_snpshtFF.sh $AutoUser $AutoPasswd $AutoObject $AutoService $fileToProcess"
                
		command=$(echo "$ScriptPath $AutoUser $AutoPasswd $AutoObject $AutoService $fileToProcess")
		echo "Command $command"
		$command
		echo "Completed: $command"
		# mv $fileToProcess $ArchiveFilePath$(basename "$fileToProcess")`date +_%Y%m%d-%H:%M:%S`
		counter=$(( $counter + 1 ))
	done
fi

#repeat counter=0...fi block for each of the object types you wish to support.
#e.g.: process secondaryInstRoles
#counter=0
#if [ "${#SecondaryInstRoleFiles[@]}" -gt 0 ]; then
#	echo "SecondaryInstRole files to process=${#SecondaryInstRoleFiles[@]}" 
#	reportMSG="$reportMSG SecondaryInstRole files to process=${#SecondaryInstRoleFiles[@]}" 
#   filesprocessedcount=$(($filesprocessedcount + ${#SecondaryInstRoleFiles[@]}))
#	AutoObject="secondaryinstrole"
#	until [[ $counter -eq "${#SecondaryInstRoleFiles[@]}" ]]
#	do
#		fileToProcess="${SecondaryInstRoleFiles[$counter]}"
#		echo "Command to call: sis_snpshtFF.sh $AutoUser $AutoPasswd $AutoObject $AutoService $fileToProcess"
#               
#		command=$(echo "$ScriptPath $AutoUser $AutoPasswd $AutoObject $AutoService $fileToProcess")
#		echo "Command $command"
#		$command
#		echo "Completed: $command"
#		mv $fileToProcess $ArchiveFilePath$(basename "$fileToProcess")`date +_%Y%m%d-%H:%M:%S`
#		counter=$(( $counter + 1 ))
#	done
#fi

# mailReport

#successfully made it here so discard the lock and exit
rm -f "/var/tmp/script.lock"