<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();