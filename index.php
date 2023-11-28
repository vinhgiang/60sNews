<?php

use App\Services\StreamingProviders\Domain\Htv7;
use App\Services\StreamingProviders\StreamingProviderFactory;
use App\Services\StreamingProviders\StreamingProviderService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$moments = [
    '60s-sang'    => ['06:30:00', '07:03:00'],
    'chuyen-trua' => ['11:59:00', '12:31:00'],
    '60s-chieu'   => ['18:30:00', '19:03:00']
];

try {
    /** @var Htv7 $streamingProvider */
    $streamingProvider        = StreamingProviderFactory::build(Htv7::class);
    $streamingProviderService = new StreamingProviderService($streamingProvider);
    $videoPath = $streamingProviderService->recordMoments($moments);

    die($videoPath);

} catch (Exception $e) {
    Logger::log($e->getMessage());
}