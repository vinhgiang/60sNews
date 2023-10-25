<?php

use App\Services\StreamingProviders\Domain\Htv7;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$htv7    = new Htv7();
try {
    $numIp = $htv7->fetchServerIp();
} catch (Exception $e) {
    Logger::log($e->getMessage());
}

die($numIp . ' has been saved.');