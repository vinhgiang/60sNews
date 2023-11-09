<?php

use App\Services\StreamingProviders\Domain\Htv7;
use App\Services\StreamingProviders\Domain\Thvl1;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$htv7  = new Htv7();
$thvl1 = new Thvl1();
try {
    $htv7NumIp  = $htv7->fetchServerIp();
//    $thvl1NumIp = $thvl1->fetchServerIp();

} catch (Exception $e) {
    Logger::log($e->getMessage());
}

print_r($htv7NumIp . ' of HTV7 has been saved.<br>');
//print_r($thvl1NumIp . ' of THVL1 has been saved.');