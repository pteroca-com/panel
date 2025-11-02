<?php

/**
 * Router script for PHP built-in web server
 * This script enables pretty URLs by routing all requests through index.php
 *
 * Usage: php -S localhost:8000 -t public/ public/router.php
 *
 * WARNING: This is for DEVELOPMENT ONLY!
 * DO NOT use PHP built-in server in production. Use nginx/Apache + PHP-FPM instead.
 */

if (php_sapi_name() !== 'cli-server') {
    die('This script can only be run by the PHP built-in web server.');
}

// Get the requested path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Security: Block path traversal attempts
if (str_contains($path, '..')) {
    http_response_code(403);
    die('Forbidden: Path traversal attempt detected');
}

// Get the absolute path to the file (follows symlinks)
$filePath = __DIR__ . $path;

// If the requested file exists AND it's a file (not a directory), serve it directly
// This handles static assets (including symlinked plugin assets)
if ($path !== '/' && is_file($filePath)) {
    return false; // serve the requested file as-is
}

// Otherwise, route everything through Symfony's front controller
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/index.php';
