<?php

use App\Services\Ffmpeg\FfmpegService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$startTime = time();
$name      = $_GET['dir'] ?? $_GET['program'] . date('-Y-m-d');
$dir       = "video/$name";

try {
    $video = FfmpegService::concatVideoInDir($dir);
} catch (UnexpectedValueException|LengthException $e) {
    Logger::log($e->getMessage());
}

$video = $video ?? $dir . '.mp4';

if (! file_exists($video)) {
    $msg = "No file is needed to be extracted at $video";
    Logger::log($msg);
    die($msg);
}

$ffmpegService = new FfmpegService($video);
$ffmpegService->extractAllFrames();

die('took ' . time() - $startTime . ' seconds.');