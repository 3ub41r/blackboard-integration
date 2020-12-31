<?php
require 'Export.php';
require 'Upload.php';

$serverName = "teams.utmspace.edu.my";
$database = "SPACEDB1000";
$username = 'admin_teams';
$password = '@!admin_teams!@';
$url = 'https://utmspace.blackboard.com/webapps/bb-data-integration-flatfile-BB5c2d88ecaab71/endpoint';

try {
    // Generate files
    $export = new Export($serverName, $username, $password, $database);
    $export->processAll();

    // Upload generated files
    (new Upload('../data', $url))->process();
} catch(Exception $e) {   
    die( print_r( $e->getMessage() ) );   
}