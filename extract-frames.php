<?php

use App\Services\Ffmpeg\FfmpegService;

require __DIR__ . '/App/Configs/configs.php';

$startTime = time();
$name      = $_GET['dir'] ?? $_GET['program'] . date('-Y-m-d');
$dir       = "video/$name";

$video = FfmpegService::concatVideoInDir($dir, false);

$ffmpegService = new FfmpegService($video);
$ffmpegService->extractAllFrames();

die('took ' . time() - $startTime . ' seconds.');