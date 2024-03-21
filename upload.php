<?php

use App\Services\Youtube\YoutubeService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$name  = $_GET['file'] ?? $_GET['program'] . date('-Y-m-d') . '-trimmed.mp4';
$video = "video/$name";

if (! file_exists($video)) {
    die('No file is needed to be uploaded.');
}

$oauthId      = $_ENV['GOOGLE_OAUTH_ID'];
$oauthSecret  = $_ENV['GOOGLE_OAUTH_SECRET'];
$redirectUrl  = $_ENV['GOOGLE_OAUTH_REDIRECT_URL'];
$refreshToken = $_ENV['GOOGLE_OAUTH_REFRESH_TOKEN'];
$dateOfYear   = date('z');
$date         = date('d/m/Y');
$dayPart      = date('a') == 'am' ? 'Sáng' : 'Chiều';

$youtubeService = new YoutubeService($oauthId, $oauthSecret, $redirectUrl);
try {
    $youtubeService->fetchAccessTokenWithRefreshToken($refreshToken);
    // Have to set Defer because we need to combine/create other requests before executing it at once
    $youtubeService->setDefer();

    $title       = "Bản tin thời sự 60S $dayPart Ngày - $date";
    $description = "Chương trình thời sự 60 Giây ngày Tp.HCM hôm nay $date bao gồm các nội dung chính sau:\n";
    $tags        = [
        "#TinTucThoiSuVietnam",
        "60Giay",
        "TinTuc60Giay",
        "TinTuc",
        "TinThoiSu",
        "Tin Tuc TP Ho Chi Minh Moi Nhat",
        "Tin Tuc Sai Gon",
        "Thoi Su Ngay Hom Nay",
    ];
    $snippet     = $youtubeService->createVideoSnippet($title, $description, 25, $tags);

    // Specify the size of each chunk of data, in bytes. Set a higher value for
    // reliable connection as fewer chunks lead to faster uploads. Set a lower
    // value for better recovery on less reliable connections.
    $chunkSizeBytes = 60 * 1024 * 1024; // 60Mb
    $videoId = $youtubeService->uploadVideo($video, $snippet, 'unlisted', false, $chunkSizeBytes);

    //    $youtubeService->setVideoThumbnail('84ynjCwjPBE', 'thumb.jpg');

    $msg = "Uploaded!";
    Logger::log($msg);

    die($msg);

} catch (Google_Service_Exception $e) {
    Logger::log("A service error occurred: \n{$e->getMessage()}");
} catch (Google_Exception $e) {
    Logger::log("An client error occurred: \n{$e->getMessage()}");
} catch (Exception $e) {
    Logger::log("Generic error: \n{$e->getMessage()}");
}