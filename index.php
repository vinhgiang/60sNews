<?php

use App\Services\StreamingProviders\Domain\Htv7Sctv;
use App\Services\StreamingProviders\StreamingProviderFactory;
use App\Services\StreamingProviders\StreamingProviderService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$moments = json_decode($_ENV['HTV7_MOMENTS'], true);

try {
    /** @var Htv7Sctv $streamingProvider */
    $streamingProvider        = StreamingProviderFactory::build(Htv7Sctv::class);
    $streamingProviderService = new StreamingProviderService($streamingProvider);
    $videoPath = $streamingProviderService->recordMoments($moments);

    die($videoPath);

} catch (Exception $e) {
    Logger::log($e->getMessage());
}