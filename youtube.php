<?php

use App\Services\YoutubeService;

require __DIR__ . '/App/Configs/configs.php';

$oauthId     = $_ENV['GOOGLE_OAUTH_ID'];
$oauthSecret = $_ENV['GOOGLE_OAUTH_SECRET'];
$redirectUrl = $_ENV['GOOGLE_OAUTH_REDIRECT_URL'];

$youtubeService = new YoutubeService($oauthId, $oauthSecret, $redirectUrl);
?>
<!doctype html>
<html lang="en">
<head>
    <title>Youtube OAuth</title>
</head>
<body>
<?php
    if (!isset($_GET['code'])) :
        $authUrl = $youtubeService->getAuthUrl();
?>
        <h3>Authorization Required</h3>
        <p>You need to <a href="<?= $authUrl ?>">authorize access</a> before proceeding.
<?php
    else:
        $youtubeService->fetchAccessTokenWithAuthCode($_GET['code']);
?>
        <h3>Refresh Token:</h3>

    <p><?= $youtubeService->getRefreshToken() ?><p>
    <p>Insert this token in .env file with the key {GOOGLE_OAUTH_REFRESH_TOKEN}</p>
<?php endif ?>
</body>
</html>