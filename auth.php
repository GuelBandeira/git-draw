<?php
session_start();

// Load configuration
require_once 'config.php';

// Generate a random state for security
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Build authorization URL
$params = [
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'repo',
    'state' => $state
];

$auth_url = GITHUB_AUTH_URL . '?' . http_build_query($params);

// Redirect to GitHub
header('Location: ' . $auth_url);
exit;
?>

