<?php
require 'Export.php';
require 'Upload.php';

$serverName = "teams.utmspace.edu.my";
$username = 'admin_teams';
$password = '@!admin_teams!@';
$url = 'https://utmspace.blackboard.com/webapps/bb-data-integration-flatfile-BB5c2d88ecaab71/endpoint';

$databases = [
    'SPACEDB1000',
    'SPACEDB1000Dip',
    'SPACEDB1000Foundation',
];

try {
    foreach ($databases as $database) {
        echo "\n\n==============================\nProcessing data for $database...\n==============================\n\n";

        // Generate files
        $export = new Export($serverName, $username, $password, $database);
        $export->processAll();

        // Upload generated files
        (new Upload('../data', $url))->process();
    }
} catch(Exception $e) {   
    die( print_r( $e->getMessage() ) );   
}