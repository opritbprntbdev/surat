<?php
/**
 * General Configuration
 * PHP 8.1+
 */

// Enable error reporting for development, but don't display them directly in production
error_reporting(E_ALL);
ini_set('display_errors', 0); // JANGAN tampilkan error ke browser
ini_set('log_errors', 1); // TAPI, catat error ke log file
ini_set('error_log', __DIR__ . '/../logs/php_errors.log'); // Tentukan lokasi log

date_default_timezone_set('Asia/Jakarta');

// Utility functions
function jsonResponse(array $data, int $statusCode = 200): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function errorResponse(string $message, int $statusCode = 400, array $errors = []): void
{
    jsonResponse([
        'success' => false,
        'error' => $message,
        'errors' => $errors,
    ], $statusCode);
}

function successResponse(mixed $data = null, string $message = 'Success'): void
{
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ]);
}
?>