<?php
require_once 'Export.php';

$serverName = "teams.utmspace.edu.my";
$database = "SPACEDB1000";
$username = 'admin_teams';
$password = '@!admin_teams!@';

try  
{
    $export = new Export($serverName, $username, $password, $database);

    $export->generateFile($export->getLecturers(), 'lecturers.txt');
    $export->generateFile($export->getStudents(), 'students.csv');
    // $export->generateFile($export->getSubjects(), 'subjects.csv');

    // $export->generateFile($export->getEnrollment(), 'enrollment.txt');
}  
catch(Exception $e)  
{   
    die( print_r( $e->getMessage() ) );   
}