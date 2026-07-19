<?php

declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__));

require PROJECT_ROOT . '/vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

$_ENV['APP_ENV'] = 'testing';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SESSION = [];
$_POST = [];
