<?php

use Dotenv\Dotenv;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require __DIR__ . '/../../vendor/autoload.php';

session_start();

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

date_default_timezone_set($_ENV['TIMEZONE']);

if ($_ENV['ENV'] == 'local') {
    set_time_limit(60 * 15);
}