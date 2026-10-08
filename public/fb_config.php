<?php
$appId = getenv('FB_APP_ID');
$appSecret = getenv('FB_APP_SECRET');

if (!is_string($appId) || $appId === '' || !is_string($appSecret) || $appSecret === '') {
    throw new RuntimeException('Facebook OAuth credentials are not configured.');
}

define('FB_APP_ID', $appId);
define('FB_APP_SECRET', $appSecret);
