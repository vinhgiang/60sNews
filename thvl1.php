<?php

use App\Services\StreamingProviders\Domain\Thvl1;
use App\Services\StreamingProviders\StreamingProviderFactory;
use App\Services\StreamingProviders\StreamingProviderService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$moments = [
    'chao-buoi-sang'     => ['05:58:00', '06:30:00'],
    'nguoi-dua-tin'      => ['06:31:00', '07:16:00'],
    'nguoi-dua-tin-trua' => ['11:09:00', '11:50:00'],
    'thoi-tiet-nong-vu'  => ['18:59:00', '19:11:00']
];

try {
    /** @var Thvl1 $streamingProvider */
    $streamingProvider        = StreamingProviderFactory::build(Thvl1::class);
    $streamingProviderService = new StreamingProviderService($streamingProvider);
    $videoPath = $streamingProviderService->recordMoments($moments);

    die($videoPath);

} catch (Exception $e) {
    Logger::log($e->getMessage());
}