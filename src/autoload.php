<?php
require 'vendor/autoload.php';

spl_autoload_register(function ($class) {
    $className = str_replace("\\", DIRECTORY_SEPARATOR, $class);
    require $className . '.php';
});