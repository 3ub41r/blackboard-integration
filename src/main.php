<?php
require_once 'Export.php';

$serverName = "teams.utmspace.edu.my";
$database = "SPACEDB1000";
$username = 'admin_teams';
$password = '@!admin_teams!@';

try  
{
    // $export = new Export($serverName, $username, $password, $database);
    // $export->processAll();

    $path = '../data';
    $files = scandir($path);

    var_dump($files);
}  
catch(Exception $e)  
{   
    die( print_r( $e->getMessage() ) );   
}