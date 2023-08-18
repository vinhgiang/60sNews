<?php

use App\Services\Youtube\YoutubeService;

require __DIR__ . '/App/Configs/configs.php';

$videoPath = 'video/' . date('ymd-a') . '.ts';
if (! file_exists($videoPath)) {
    die('No file is needed to be uploaded.');
}

$oauthId      = $_ENV['GOOGLE_OAUTH_ID'];
$oauthSecret  = $_ENV['GOOGLE_OAUTH_SECRET'];
$redirectUrl  = $_ENV['GOOGLE_OAUTH_REDIRECT_URL'];
$refreshToken = $_ENV['GOOGLE_OAUTH_REFRESH_TOKEN'];
$date         = date('d/m/Y');
$dayPart      = date('a') == 'am' ? 'Sáng' : 'Chiều';

$youtubeService = new YoutubeService($oauthId, $oauthSecret, $redirectUrl);
try {
    $youtubeService->fetchAccessTokenWithRefreshToken($refreshToken);
    // Have to set Defer because we need to combine/create other requests before executing it at once
    $youtubeService->setDefer();

    $title       = "60 Giây $dayPart Ngày $date - Mới Hơn - HTV Tin Tức Mới Nhất Hơn";
    $description = "Tin Tức HTV $dayPart $date\n60 Giây $dayPart Mới Hơn Nhất - Ngày $date - HTV Tin Tức Mới Nhất Hơn\nKênh Tin Tức Thời Sự 60 Giây Là Kênh Tổng Hợp Tin Tức - Sự Kiện - Giải trí Nhanh Nhất So Với Các Kênh Khác";
    $tags        = [
        "#TinTucThoiSuVietnam",
        "#HTVTinTuc",
        "#HTVnews",
        "60Giay",
        "TinTuc60Giay",
        "HTV7",
        "HTV9",
        "TinTuc",
        "TinThoiSu",
        "Tin Tuc TP Ho Chi Minh Moi Nhat"
    ];
    $snippet     = $youtubeService->createVideoSnippet($title, $description, 25, $tags);

    // Specify the size of each chunk of data, in bytes. Set a higher value for
    // reliable connection as fewer chunks lead to faster uploads. Set a lower
    // value for better recovery on less reliable connections.
    $chunkSizeBytes = 60 * 1024 * 1024; // 60Mb
    $videoId = $youtubeService->uploadVideo($videoPath, $snippet, 'private', false, $chunkSizeBytes);

    //    $youtubeService->setVideoThumbnail('84ynjCwjPBE', 'thumb.jpg');

} catch (Google_Service_Exception $e) {
    print_r("A service error occurred: \n");
    print_r($e->getMessage());
} catch (Google_Exception $e) {
    print_r("An client error occurred: \n");
    print_r($e->getMessage());
} catch (Exception $e) {
    print_r($e->getMessage());
}