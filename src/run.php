<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Add new source classes to this file
$dataSources = include 'config/datasource.php';

$sleepSeconds = 60;

foreach ($dataSources as $dataSource) {
    $import = new $dataSource[0]($dataSource[1]);
    
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