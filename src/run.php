<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Add new source classes to this file
$dataSources = include 'config/datasource.php';

// Sleep to avoid throttling the server
// $sleepSeconds = 60;
$sleepSeconds = 2;

foreach ($dataSources as $dataSource) {
    $import = new $dataSource();

    echo "\nImporting {$dataSource}\n";
    
    $import->processCourses();
    sleep($sleepSeconds);

    $import->processLecturers();
    sleep($sleepSeconds);

    $import->processStudents();
    sleep($sleepSeconds);

    $import->processCourseLecturers();
    sleep($sleepSeconds);

    $import->processEnrollments();
}