<?php

use App\Services\StreamingProviders\Domain\Thvl1;
use App\Services\StreamingProviders\StreamingProviderFactory;
use App\Services\StreamingProviders\StreamingProviderService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$moments = json_decode($_ENV['THVL1_MOMENTS'], true);

try {
    /** @var Thvl1 $streamingProvider */
    $streamingProvider        = StreamingProviderFactory::build(Thvl1::class);
    $streamingProviderService = new StreamingProviderService($streamingProvider);
    $videoPath = $streamingProviderService->recordMoments($moments);

    die($videoPath);

} catch (Exception $e) {
    Logger::log($e->getMessage());
}