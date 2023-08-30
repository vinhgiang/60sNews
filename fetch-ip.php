<?php

use App\Services\StreamingProviders\Domain\Htv7;
use App\Services\StreamingProviders\StreamingProviderFactory;
use App\Services\StreamingProviders\StreamingProviderService;

require __DIR__ . '/App/Configs/configs.php';

$htv7    = new Htv7();
$summary = $htv7->fetchServerIp();

die($summary);