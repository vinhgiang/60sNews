<?php

use App\Services\Ffmpeg\FfmpegService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$name  = $_GET['file'] ?? $_GET['program'] . date('-Y-m-d') . '.mp4';
$video = "video/$name";
if (! file_exists($video) || file_exists('video/' . $name . '-trimmed.mp4')) {
    die("No file is needed to be trimmed at $video");
}

$startTime = time();

$ffmpegService = new FfmpegService($video);

$boundaryScanner = json_decode(file_get_contents("log/scan-boundary.txt"));
if (! $boundaryScanner->isDone) {
    die('Have not finished scanning boundary.');
}

$borderTimesDetected = $boundaryScanner->result;
$times[] = [0, $borderTimesDetected[0][0]];

// if there is no end border indicator
$borderTimesDetected[0][1] = $borderTimesDetected[0][1] ?? $boundaryScanner->processed;

$adsScanner = json_decode(file_get_contents("log/scan-ads.txt"));
if (! $adsScanner->isDone) {
    die('Have not finished scanning ads.');
}

$adsTimesDetected = $adsScanner->result;

// filter out parts that beyonds borders
$adsTimesDetected = array_filter($adsTimesDetected, function ($moment) use ($borderTimesDetected) {
    return $moment[0] > $borderTimesDetected[0][0] && $moment[0] < $borderTimesDetected[0][1];
});

$times = array_merge($times, $adsTimesDetected);

$lastOccasion = end($times);
// has a pair
if (count($lastOccasion) == 2) {
    $times[] = [$borderTimesDetected[0][1], $borderTimesDetected[0][1]];
}
// missing one indicator
else {
    $times[count($times) - 1][0] = $borderTimesDetected[0][1];
}

$trimmedVideo = $ffmpegService->trimVideo($times);

$ffmpegService->cleanup();

$outroVideos[0] = 'video/' . $trimmedVideo;
while (count($outroVideos) < 10) {
    $videoId = rand(1, 481);
    $outroVideos[$videoId] = "resources/outro/$videoId.mp4";
}

Logger::log('[' . implode(', ', array_keys($outroVideos)). ']');

$ffmpegService->concatVideos($outroVideos, 'video/final');

die('took ' . time() - $startTime . ' seconds.');