<?php
session_start();

// Load configuration
require_once 'config.php';

// Verify state
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Invalid state parameter');
}

// Check for error
if (isset($_GET['error'])) {
    die('Authorization failed: ' . htmlspecialchars($_GET['error']));
}

// Exchange code for token
if (!isset($_GET['code'])) {
    die('No authorization code received');
}

$code = $_GET['code'];

// Detect if running locally (WAMP/XAMPP)
$is_local = (strpos(GITHUB_REDIRECT_URI, 'localhost') !== false || strpos(GITHUB_REDIRECT_URI, '127.0.0.1') !== false);

// Request access token
$ch = curl_init(GITHUB_TOKEN_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => GITHUB_CLIENT_ID,
    'client_secret' => GITHUB_CLIENT_SECRET,
    'code' => $code,
    'redirect_uri' => GITHUB_REDIRECT_URI
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded'
]);
// SSL configuration
// Para WAMP/XAMPP local, pode ser necessário desabilitar verificação SSL
// ATENÇÃO: NÃO use isso em produção!
if ($is_local) {
    // Desabilita verificação SSL para desenvolvimento local
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
} else {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
}
// Timeout settings
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
// Follow redirects
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$curl_errno = curl_errno($ch);
curl_close($ch);

// Check for cURL errors
if ($curl_errno !== 0) {
    die('cURL Error (' . $curl_errno . '): ' . $curl_error . '<br>URL: ' . GITHUB_TOKEN_URL);
}

if ($http_code === 0) {
    die('Failed to connect to GitHub. HTTP Code: 0<br>cURL Error: ' . ($curl_error ?: 'Unknown error') . '<br>Please check your internet connection and SSL configuration.');
}

if ($http_code !== 200) {
    die('Failed to get access token. HTTP Code: ' . $http_code . '<br>Response: ' . htmlspecialchars($response));
}

$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    die('No access token in response: ' . $response);
}

$access_token = $data['access_token'];
$_SESSION['github_token'] = $access_token;

// Get user information
$ch = curl_init(GITHUB_API_URL . '/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'User-Agent: GitHub-Contribution-Generator',
    'Accept: application/vnd.github+json',
    'X-GitHub-Api-Version: 2022-11-28'
]);
// SSL configuration (same as above)
if ($is_local) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
} else {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
}
// Timeout settings
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
// Follow redirects
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$user_response = curl_exec($ch);
$user_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$user_curl_error = curl_error($ch);
curl_close($ch);

if ($user_http_code === 200) {
    $user_data = json_decode($user_response, true);
    $_SESSION['github_user'] = $user_data;
}

// Redirect back to index
header('Location: index.php');
exit;
