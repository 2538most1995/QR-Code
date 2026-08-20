<?php
// config/oauth_config.php - จัดการการเชื่อมต่อจริงกับ Google OAuth 2.0 และ LINE Login API
require_once __DIR__ . '/database.php';

// ⭐ ค่า Redirect URI คงที่ — ต้องตรง 100% กับที่ใส่ไว้ใน Google Cloud Console และ LINE Developers Console
// MAMP Document Root ตั้งค่าอยู่ที่โฟลเดอร์ QR_code โดยตรง จึงไม่ต้องมี /QR_code/ ใน URL
define('OAUTH_BASE_URL', 'http://localhost:8888');

function getGoogleRedirectUri() {
    return OAUTH_BASE_URL . '/admin/oauth_callback.php?provider=google';
}

function getLineRedirectUri() {
    return OAUTH_BASE_URL . '/admin/oauth_callback.php?provider=line';
}

function getGoogleOAuthSettings() {
    return [
        'client_id' => getSystemSetting('google_client_id', ''),
        'client_secret' => getSystemSetting('google_client_secret', ''),
        'redirect_uri' => getGoogleRedirectUri()
    ];
}

function getLineOAuthSettings() {
    return [
        'channel_id' => getSystemSetting('line_channel_id', ''),
        'channel_secret' => getSystemSetting('line_channel_secret', ''),
        'redirect_uri' => getLineRedirectUri()
    ];
}

// HTTP POST Request using cURL
function httpPost($url, $data = [], $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }
    
    return json_decode($response, true);
}

// HTTP GET Request using cURL
function httpGet($url, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }
    
    return json_decode($response, true);
}
