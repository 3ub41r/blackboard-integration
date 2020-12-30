#!/bin/bash
# sis_snpshtFF_manual
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

# Provides command-line flat file data feed capabilities. Use alone or in conjunction
#	with sis_snpshtFF_auto.sh.
# Processes a feed file based on passed parameters:
# 	sis_snpshtFF_manual.sh <user> <password> <object> <service> <file path>
# 	eg: sis_snpshtFF_manual.sh 13a29f6e-66e6-4ab9-ad4b-174d3ca71723 pwd person store persons.txt
# and mails a report to the configured system administrator.

#Variables:
#IMPORTANT!
#the following variables need to be set in order to use this script 

#OPTIONAL: uncomment and set the variables and exports between the below begin and end lines
# if you are self hosted and wish to pull log messages from your Oracle database
#-- begin Oracle variables --
#ORACLE_HOME=/opt/app/oracle/product/11.2.0/dbhome_3
#ORACLE_SID=bblearn9

#export $ORACLE_HOME
#export $ORACLE_SID

#NOTE: you must also edit the below SQL surrounded by #EDIT THIS SQL comments.
#-- end Oracle variables --

#process_logs: set this to 1 if you are pulling logs for inclusion in emails
process_logs=0

#admin_email: comma separated list of administrators to receive emails
#eg: admin_email="bbadmin@mountcollege.edu,sisadmin@mountcollege.edu"
admin_email="zubair@utmspace.edu.my"

#Server_FQDN: the target BbLearn server name e.g.:server_FQDN="blackboard.mountcollege.edu"
server_FQDN="utmspace.blackboard.com"

#sleep_time: number of seconds to sleep until next complete check
sleep_time=5 

#threshold: how long (seconds) should this run before sending an alert?
threshold=900 

#do not edit these variables:
# serverEndpointURL="https://$server_FQDN/webapps/bb-data-integration-flatfile-BBLEARN/endpoint"
serverEndpointURL="https://$server_FQDN/webapps/bb-data-integration-flatfile-BB5c2d88ecaab71/endpoint"
multiplier=1  #counter to send incremental alerts on incremental threshold hits
completed_count=-1 #counter for number of records completed processing.

#Functions:
#fail(): in the event of a data or network error this function is called
# composes and sends email to admins
function fail() { #called when a data or network fail occurs
  echo "$1"
	
  subject="[Integration FAIL] $object $service :: $(date)"
  emailmsgin="Error Message: $1\n"
  emailmessage=$(echo -e $emailmsgin)
  emailmessage=$emailmessage$sql_error_results
  /bin/mail -s "$subject" "$admin_email" <<EOF
$emailmessage
EOF
  exit -1
}

#mailLongProcessMsg(): in the event processing hits configured thresholds this function is called
# composes and sends email to admins warning of long processing times (expected in some scenarios eg: large # of course creates using templates
function mailLongProcessMsg () {
  subject="[Integration Long Running Process Warning] $object $service :: $(date)"
  SEC=$1
  ((h=SEC/3600))
  ((m=SEC%3600/60))
  ((s=SEC%60))
  emailmsgin="The process started on $start_date has been running for $h hour(s) $m minute(s) $s second(s)"
  emailmessage=$(echo -e $emailmsgin)
  /bin/mail -s "$subject" "$admin_email" <<EOF
$emailmessage
EOF
}

#mailMsg(): in the event processing hits configured thresholds this function is called
# composes and sends email to admins warning of long processing times (expected in some scenarios eg: large # of course creates using templates
function mailMsg {
  if [ $errorCount = '0' ]; then
    MSG_LEVEL="SUCCESS"
  else
    MSG_LEVEL="ERROR"
  fi
	
  subject="[SIS_SNPSHTFF Report: $MSG_LEVEL] $object $service :: $(date)"
  emailmsg="SIS_SNPSHTFF Report\n\n$time_msg\n"
  emailmsg="$emailmsg\nData Set ID: $data_set_uid"
  emailmsg="$emailmsg\nStart Date: $start_date"
  emailmsg="$emailmsg\nEnd Date: $end_date"
  emailmsg="$emailmsg\nIncomplete: $incomplete_count"
  emailmsg="$emailmsg\nCompleted: $completedCount"
  emailmsg="$emailmsg\nWarnings: $warningCount"

  if [ $errorCount != '0' ]; then
    emailmsg="$emailmsg\nErrors: $errorCount"
  fi

  if [ $errorCount != '0' ]; then
  	if [ $process_logs != '0' ]; then
  		emailmsg="$emailmsg\nError Messages:\n"
    	emailmsg=$emailmsg$sql_error_results
    fi
  fi
  emailmessage=$(echo -e $emailmsg)

  /bin/mail -s "$subject" "$admin_email" <<EOF
$emailmessage
EOF
}

#date2stamp: converts a properly formatted date string into a timestamp for time calculations
date2stamp () {
    date --utc --date "$1" +%s
}

#CORE script....
#clear #uncomment if you wish to clear the terminal screen before beginning to process.

# pull the args passed on the command line - could use getopt  for fancier handling
#eg: --user intgr_user --password intgr_pwd --object person --service store --filepath persons.txt
#Keep it simple for now...
user=$1
password=$2
object=$3
service=$4
file=$5

#count the number of records in the file minus the header row.
recordsToProcess=$(awk '/([^[:space:]])+/ {x++} END {print x}' "$file")
((recordsToProcess--))

#comment out the below 7 lines for debugging automation
#echo "Parameters for this process:"
#echo "    Integration user: $user"
#echo "    Integration password: $password"
#echo "    object Type to Process: $object"
#echo "    service to call: $service"
#echo "    file to process: $file"
#echo "    The above will be sent to $serverEndpointURL\n"

# sanity check on <service> argument - call fail function if incoming service null or incorrectly specified.
[[ "$service" == "store" ]] || [[ "$service" == "refresh" ]] || [[ "$service" == "delete" ]] || [[ "$service" == "refreshlegacy" ]] || fail "You must use either store, refresh, delete, or refreshlegacy for <service>"

# sanity check on <file PATH> argument - should exist and not be empty
if [ ! -f $file ] || [ ! -s $file ];
then
    fail "File $file not found!"
fi

#build the request and call curl to POST the file - storing the result in post_resp.
#eg: curl -s -k -w %%{http_code} -H "Content-Type:text/plain" -u <intgr_usr>:<intgr_pwd> --url <intgr_url> --data-bin @<file>

post_resp=$(curl -s -k -w %{http_code} -H "Content-Type:text/plain" -u $user:$password --url $serverEndpointURL/$object/$service --data-bin @$file)

#get the http code off the end of the response from curl:
http_code="${post_resp:${#post_resp} - 3}"

#ADD FATAL ERROR BLOCK - No sense going further if not http_code 200 == the returned result does not indicate success.
if [ $http_code != '200' ];
then
	echo $http_code
    fail "The service endpoint $serverEndpointURL/$object/$service not reached! $serverEndpointURL/$object/$service $http_code"
fi

#additionally the return may have been 200, but contain a stack trace or some other data indicating a failure - success means that the response contains "success: Feed file Uploaded."
success="Success: Feed File Uploaded"
if [[ ! "$post_resp" =~ "$success" ]];
then
    fail "The service endpoint responded with a fatal error! Check URL and or Data file for errors.\n$success\n\n$post_resp"
fi

#at this point we have passed our sanity checks on the original POST so it is safe to continue...
#-- uncomment the below 2 lines for debugging
#echo "Data Post Code: $http_code"
#echo "Data Post Response: $post_resp"

#pull the process id from the response - this is used to test whether the processing is complete or not and to pull log messages if using that feature.
data_set_uid=$(echo $post_resp|awk '{print $9}')

#get processing status in a loop that repeats until incomplete_count is equal to zero
until [[ $completed_count -eq $recordsToProcess ]]
do
#-- uncomment the below line for debugging
#echo "Sleeping for $sleep_time seconds..."
sleep $sleep_time
#-- uncomment out the below line for debugging
#echo "Check if we are done..."
get_status_resp=$(curl -s -k -w %{http_code} -H "Content-Type:text/xml" -u $user:$password -g $serverEndpointURL/dataSetStatus/$data_set_uid)
completed=$(echo "$get_status_resp" | grep '<completedCount>')
completed_count=$(echo $completed|awk '{gsub(/[A-Za-z<>\/]/,"")}1')
if [ "$SECONDS" -gt "$((threshold*multiplier))" ]; then
  echo "long runner at $((threshold*multiplier))" 
  (( multiplier++ )) #hit another threshold level so update our checkpoint
  mailLongProcessMsg $SECONDS
fi
done

#get the stats
errors=$(echo "$get_status_resp" | grep '<errorCount>')
errorCount=$(echo $errors|awk '{gsub(/[A-Za-z<>\/]/,"")}1')
warnings=$(echo "$get_status_resp" | grep '<warningCount>')
warningCount=$(echo $warnings|awk '{gsub(/[A-Za-z<>\/]/,"")}1')
completed=$(echo "$get_status_resp" | grep '<completedCount>')
completedCount=$(echo $completed|awk '{gsub(/[A-Za-z<>\/]/,"")}1')
start=$(echo "$get_status_resp" | grep '<startDate>')
startDate=$(echo $start|awk '{gsub(/[^T0-9:\-\+]/,"")}1')
end=$(echo "$get_status_resp" | grep '<lastEntryDate>')
endDate=$(echo $start|awk '{gsub(/[^T0-9:\-\+]/,"")}1')

#-- uncomment the below 11 lines for debugging
#echo "Final Status Response:\n $get_status_resp"
#echo "Parsed data points:"
#echo "Data Set ID: $data_set_uid"
#echo "Start Date: $startDate"
#echo "End Date: $endDate"
#echo "Incomplete: $incomplete_count"
#echo "Completed: $completedCount"
#echo "Record Count to Process: $recordsToProcessCount"
#echo "Errors: $errorCount"
#echo "Warnings: $warningCount"
#echo "http code: ${get_status_resp:${#get_status_resp} - 3}"

#--- part two: build and email a report
#This portion of the script handles the mailing of the report 
# - note that if you are using the Oracle log retrieval code 
# you MUST set the correct variables in the code per the example.


#clean up the end date for passing to the date2stamp function
#requires the date to be in YYYY-MM-DD HH:MM:SS format and as 
#retrieved it is in the format YYYY-MM-DDTHH:MM:SS-HH:MM 
end_date="${endDate:0:19}"
end_date="${end_date/T/ }"
echo "end_date: $end_date"
#generate timestamp
lastEntryDateStamp=$(date2stamp "$end_date")
echo "lastEntryDateStamp: $lastEntryDateStamp"

#clean up the start date for passing to the date2stamp function - see above end_date description.
start_date="${startDate:0:19}"
start_date="${start_date/T/ }"
#-- uncomment the below line for debugging
#echo "start_date: $start_date"
#generate timestamp
start_dateStamp=$(date2stamp "$start_date")
#-- uncomment the below line for debugging
#echo "start_dateStamp: $start_dateStamp"

#determine the diff between start and end time for processing and generate a msg to add to the report
timeDiff=$(((lastEntryDateStamp - start_dateStamp))) 
mins=$(($timeDiff/60))
secs=$(($timeDiff - ($mins * 60)))
time_msg="Total time taken (mins:secs) is $mins:$secs to process $completedCount records."
#-- uncomment the below line for debugging
#echo "$time_msg"

#-- uncomment the below line for debugging
#echo "Build report..."

if [ $process_logs -gt '0' ]; then
#-- MAKE CERTAIN TO PROPERLY SET BELOW sql_error_results ORACLE CONNECTION STRING
#-- FORMATTING IS IMPORTANT MAINTAIN CURRENT LINE BREAKS

if [ $errorCount != '0' ]; then
#Get the query , no validation applied for query # 
#Note log_level 3==Errors
ERROR_QUERY="select log_message from bblearn.data_intgr_log where data_set_uid='$data_set_uid' and log_level='3';"

#BEGIN EDIT THIS SQL
#eg:
#sql_error_results=("$($ORACLE_HOME/bin/sqlplus -s BBLEARN/bbuser'(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=moneil.localdomain)(PORT=1522)))(CONNECT_DATA=(SID=BBLEARN9)))' << EOF
#Note if you are not interested in the stack trace and only want to know the object which errored out you may remove LONG 30000 from the below SET statement.

sql_error_results=("$($ORACLE_HOME/bin/sqlplus -s <USER>/<PWD>'(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=<HOSTNAME>)(PORT=<PORT#>)))(CONNECT_DATA=(SID=<SID>)))' << EOF
SET PAGESIZE 0 FEEDBACK OFF VERIFY OFF ECHO OFF serverOUT ON TIME OFF TIMING OFF LONG 30000
$ERROR_QUERY
EOF
)"
)
fi
fi
#END EDIT THIS SQL

#-- uncomment the below line for debugging
#echo "Mail message..."
mailMsg
