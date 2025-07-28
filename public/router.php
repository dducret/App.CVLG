<?php
// router.php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($uri, '/api')) {
    // forward /api requests to the API entry point
    include __DIR__ . '/../api/index.php';
} else {
    return false; // let PHP’s built-in server handle static files
}
