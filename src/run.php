<?php

require 'vendor/autoload.php';

use App\Classes\ImportFoundation;
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Add new source classes to this file
$dataSources = include 'datasource.php';

foreach ($dataSources as $dataSource) {
    $import = new $dataSource[0]($dataSource[1]);
    
    $import->processLecturers();
    $import->processStudents();
    $import->processCourses();
    $import->processSubjectLecturers();
    $import->processEnrollments();
}


