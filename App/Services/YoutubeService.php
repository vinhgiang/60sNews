<?php

namespace App;

use Exception;
use Google_Client;
use Google_Http_MediaFileUpload;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;

class YoutubeService
{
    /** @var Google_Client */
    private $client;

    /** @var Google_Service_YouTube */
    private $youtube;

    /**
     * @param string $oauthId
     * @param string $oauthSecret
     * @param string $oauthRedirectUrl
     */
    public function __construct($oauthId, $oauthSecret, $oauthRedirectUrl)
    {
        /*
         * You can acquire an OAuth 2.0 client ID and client secret from the
         * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
         * For more information about using OAuth 2.0 to access Google APIs, please see:
         * <https://developers.google.com/youtube/v3/guides/authentication>
         * Please ensure that you have enabled the YouTube Data API for your project.
         */
        $OAUTH2_CLIENT_ID     = $oauthId;
        $OAUTH2_CLIENT_SECRET = $oauthSecret;
        $redirect             = $oauthRedirectUrl;

        $client = new Google_Client();
        $client->setClientId($OAUTH2_CLIENT_ID);
        $client->setClientSecret($OAUTH2_CLIENT_SECRET);
        $client->setScopes(['https://www.googleapis.com/auth/youtube']);
        $client->setRedirectUri($redirect);

        /* We will need these 2 lines for Refresh Token */
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $this->client = $client;

        $this->youtube = new Google_Service_YouTube($this->client);
    }

    /**
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * @param string $code
     * @return array
     * @throws Exception
     */
    public function fetchAccessTokenWithAuthCode($code)
    {
        $cred = $this->client->fetchAccessTokenWithAuthCode($code);
        if (!isset($cred['access_token'])) {
            //TODO: Log error
            throw new Exception($cred['error_description']);
        }
        return $cred;
    }

    /**
     * @param string $refreshToken
     * @return array
     * @throws Exception
     */
    public function fetchAccessTokenWithRefreshToken($refreshToken)
    {
        $cred = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (!isset($cred['access_token'])) {
            //TODO: Log error
            throw new Exception($cred['error_description']);
        }
        return $cred;
    }

    /**
     * @param bool $isDefer
     * @return void
     */
    public function setDefer($isDefer = true)
    {
        // Setting the defer flag to true tells the client to return a request which can be called
        // with ->execute(); instead of making the API call immediately.
        $this->client->setDefer($isDefer);
    }

    /**
     * @return array
     */
    public function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    /**
     * @param string $token
     * @return void
     */
    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
    }

    /**
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->client->getRefreshToken();
    }

    /**
     * @param string $title
     * @param string $description
     * @param array $tags
     * @return Google_Service_YouTube_VideoSnippet
     */
    public function createVideoSnippet($title, $description = '', $categoryId = 22, $tags = [])
    {
        $snippet = new Google_Service_YouTube_VideoSnippet();
        // Create a snippet with title, description, tags and category ID
        // Create an asset resource and set its snippet metadata and type.
        // This example sets the video's title, description, keyword tags, and
        // video category.
        $snippet->setTitle($title);
        $snippet->setDescription($description);
        $snippet->setTags($tags);

        // Numeric video category. See
        // https://developers.google.com/youtube/v3/docs/videoCategories/list
        // https://mixedanalytics.com/blog/list-of-youtube-video-category-ids/
        // 22 is People & Blogs. 25 is News & Politics
        $snippet->setCategoryId($categoryId);

        return $snippet;
    }

    /**
     * @param string $videoPath
     * @param Google_Service_YouTube_VideoSnippet $snippet
     * @param string $status
     * @param boolean $isMadeForKids
     * @param int $chunkSizeBytes
     * @return string
     */
    public function uploadVideo($videoPath, $snippet, $status, $isMadeForKids = false, $chunkSizeBytes = 1 * 1024 * 1024)
    {
        // Set the video's status to "public". Valid statuses are "public", "private" and "unlisted".
        $videoStatus                          = new Google_Service_YouTube_VideoStatus();
        $videoStatus->privacyStatus           = $status;
        $videoStatus->selfDeclaredMadeForKids = $isMadeForKids;

        // Associate the snippet and status objects with a new video resource.
        $video = new Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($videoStatus);

        // Create a request for the API's videos.insert method to create and upload the video.
        $insertRequest = $this->youtube->videos->insert("status,snippet", $video);

        // Create a MediaFileUpload object for resumable uploads.
        $media = new Google_Http_MediaFileUpload(
            $this->client,
            $insertRequest,
            'video/*',
            null,
            true,
            $chunkSizeBytes
        );
        $media->setFileSize(filesize($videoPath));

        // Read the media file and upload it chunk by chunk.
        $videoStatus = false;
        $handle      = fopen($videoPath, "rb");
        while (!$videoStatus && !feof($handle)) {
            $chunk       = fread($handle, $chunkSizeBytes);
            $videoStatus = $media->nextChunk($chunk);
        }

        fclose($handle);

        return $videoStatus['id'];
    }

    /**
     * @param string $videoId
     * @param string $thumbPath
     * @return void
     */
    public function setVideoThumbnail($videoId, $thumbPath)
    {
        // This only work after account verification or app verification.
        // https://www.youtube.com/verify
        $this->youtube->thumbnails->set(
            $videoId,
            [
                'data'       => file_get_contents($thumbPath),
                'mimeType'   => 'application/octet-stream',
                'uploadType' => 'multipart'
            ]
        );
    }
}