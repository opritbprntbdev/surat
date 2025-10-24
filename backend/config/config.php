<?php
/**
 * General Configuration
 * PHP 8.1+
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Set default headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Application configuration
class Config
{
    // Database settings
    public const DB_HOST = 'localhost';
    public const DB_NAME = 'surat_app';
    public const DB_USER = 'root';
    private const PORT = 3308;
    public const DB_PASS = '';

    // Application settings
    public const APP_NAME = 'surat_app';
    public const APP_VERSION = '1.0.0';
    public const APP_URL = 'http://localhost';

    // Security settings
    public const JWT_SECRET = 'your-secret-key-change-this-in-production';
    public const JWT_EXPIRY = 3600; // 1 hour

    // Email settings
    public const EMAIL_FROM = 'noreply@gmailclone.com';
    public const EMAIL_FROM_NAME = 'surat_app';

    // File upload settings
    public const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    public const ALLOWED_FILE_TYPES = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'txt',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'zip',
        'rar'
    ];

    // Pagination settings
    public const DEFAULT_PAGE_SIZE = 50;
    public const MAX_PAGE_SIZE = 100;

    // Cache settings
    public const CACHE_ENABLED = false;
    public const CACHE_TTL = 300; // 5 minutes

    // Rate limiting
    public const RATE_LIMIT_ENABLED = true;
    public const RATE_LIMIT_REQUESTS = 100;
    public const RATE_LIMIT_WINDOW = 3600; // 1 hour
}

// Utility functions
function jsonResponse(array $data, int $statusCode = 200): void
{
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
        'timestamp' => date('c')
    ], $statusCode);
}

function successResponse(mixed $data = null, string $message = 'Success'): void
{
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
}

function getRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            errorResponse('Invalid JSON data: ' . json_last_error_msg(), 400);
        }

        return $data ?? [];
    }

    return $_POST;
}

function validateRequired(array $data, array $requiredFields): array
{
    $errors = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[$field] = "Field '$field' is required";
        }
    }

    return $errors;
}

function sanitizeInput(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_ARGON2ID);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function getCurrentTimestamp(): string
{
    return date('Y-m-d H:i:s');
}

function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, 2) . ' ' . $units[$i];
}

function logMessage(string $message, string $level = 'INFO'): void
{
    $logFile = __DIR__ . '/../logs/app.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function getClientIp(): string
{
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function rateLimitCheck(string $identifier, int $maxRequests = Config::RATE_LIMIT_REQUESTS, int $window = Config::RATE_LIMIT_WINDOW): bool
{
    if (!Config::RATE_LIMIT_ENABLED) {
        return true;
    }

    $cacheFile = sys_get_temp_dir() . "/rate_limit_$identifier";
    $currentTime = time();

    // Read existing requests
    $requests = [];
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        $requests = json_decode($data, true) ?: [];
    }

    // Remove old requests
    $requests = array_filter($requests, fn($time) => $time > $currentTime - $window);

    // Check if limit exceeded
    if (count($requests) >= $maxRequests) {
        return false;
    }

    // Add current request
    $requests[] = $currentTime;

    // Save to cache
    file_put_contents($cacheFile, json_encode($requests));

    return true;
}

// Rate limiting check
$clientIp = getClientIp();
if (!rateLimitCheck($clientIp)) {
    errorResponse('Rate limit exceeded', 429);
}

// Log request
logMessage("Request: {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']} from $clientIp");
?>