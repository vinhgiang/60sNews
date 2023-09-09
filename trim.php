<?php

use App\Services\Ffmpeg\FfmpegService;

require __DIR__ . '/App/Configs/configs.php';

$filename = date('ymd-a');
$video    = 'video/' . $filename . '.ts';
if (! file_exists($video) || file_exists('video/' . $filename . '-trimmed.mp4')) {
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

$ffmpegService->trimVideo($times);

$ffmpegService->cleanup();

die('took ' . time() - $startTime . ' seconds.');