<?php

use App\Services\StreamingProviders\Domain\Htv7;
use App\Services\StreamingProviders\StreamingProviderFactory;
use App\Services\StreamingProviders\StreamingProviderService;

require __DIR__ . '/App/Configs/configs.php';

$moments = [
    ['06:30:00', '07:01:00'],
    ['18:30:00', '19:01:00']
];

/** @var Htv7 $streamingProvider */
$streamingProvider        = StreamingProviderFactory::build(Htv7::class);
$streamingProviderService = new StreamingProviderService($streamingProvider);
$videoPath                = $streamingProviderService->recordMoments($moments, date('ymd-a'));

die($videoPath);