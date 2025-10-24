<?php
/**
 * Emails API Endpoint
 * PHP 8.1+ with MySQLi
 */
// Ensure API endpoints always respond with JSON (helps frontend JSON.parse avoid '<' HTML)
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

// Exception handler for uncaught exceptions
set_exception_handler(function ($e) {
    http_response_code(500);
    $payload = ['error' => 'Internal server error', 'message' => $e->getMessage()];
    echo json_encode($payload);
    exit;
});

// Error handler for recoverable PHP errors/warnings
set_error_handler(function ($severity, $message, $file, $line) {
    // LetFatal errors through so shutdown handler handles them
    if (!(error_reporting() & $severity)) {
        return false;
    }
    http_response_code(500);
    $payload = ['error' => 'PHP error', 'message' => $message, 'file' => $file, 'line' => $line];
    echo json_encode($payload);
    exit;
});

// Shutdown handler to catch fatal errors (like failed require_once) and render JSON
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err !== null) {
        http_response_code(500);
        $payload = ['error' => 'Fatal error', 'message' => $err['message'], 'file' => $err['file'], 'line' => $err['line']];
        // If headers already sent, attempt to clear output buffer
        if (ob_get_length()) {
            ob_end_clean();
        }
        echo json_encode($payload);
        exit;
    }
});

require_once __DIR__ . '/../config/config.php';
// corrected path: backend/function/email_function.php (singular folder/name)
require_once __DIR__ . '/../function/email_function.php';

try {
    // Initialize email functions inside try so constructor exceptions are caught and returned as JSON
    $emailFunctions = new EmailFunctions();

    $method = $_SERVER['REQUEST_METHOD'];
    $input = getRequestData();

    switch ($method) {
        case 'GET':
            handleGetRequest($emailFunctions, $input);
            break;

        case 'POST':
            handlePostRequest($emailFunctions, $input);
            break;

        case 'PUT':
            handlePutRequest($emailFunctions, $input);
            break;

        case 'DELETE':
            handleDeleteRequest($emailFunctions, $input);
            break;

        default:
            errorResponse('Method not allowed', 405);
            break;
    }

} catch (Exception $e) {
    // Log to application log for easier debugging
    if (function_exists('logMessage')) {
        logMessage("Email API Error: " . $e->getMessage(), 'ERROR');
    } else {
        error_log("Email API Error: " . $e->getMessage());
    }

    // Return JSON error (include message for debugging; remove message in production)
    errorResponse('Internal server error: ' . $e->getMessage(), 500);
}

/**
 * Handle GET requests
 */
function handleGetRequest(EmailFunctions $emailFunctions, array $input): void
{
    $action = $input['action'] ?? 'list';

    switch ($action) {
        case 'list':
            handleGetEmails($emailFunctions, $input);
            break;

        case 'single':
            handleGetSingleEmail($emailFunctions, $input);
            break;

        case 'search':
            handleSearchEmails($emailFunctions, $input);
            break;

        case 'stats':
            handleGetStats($emailFunctions);
            break;

        default:
            errorResponse('Invalid action', 400);
            break;
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest(EmailFunctions $emailFunctions, array $input): void
{
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            handleCreateEmail($emailFunctions, $input);
            break;

        case 'mark_read':
            handleMarkAsRead($emailFunctions, $input);
            break;

        case 'mark_unread':
            handleMarkAsUnread($emailFunctions, $input);
            break;

        case 'toggle_star':
            handleToggleStar($emailFunctions, $input);
            break;

        case 'move':
            handleMoveEmails($emailFunctions, $input);
            break;

        case 'delete':
            handleDeleteEmails($emailFunctions, $input);
            break;

        case 'bulk_operation':
            handleBulkOperation($emailFunctions, $input);
            break;

        default:
            errorResponse('Invalid action', 400);
            break;
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest(EmailFunctions $emailFunctions, array $input): void
{
    $id = $input['id'] ?? null;

    if (!$id) {
        errorResponse('Email ID is required', 400);
    }

    $errors = validateRequired($input, ['subject', 'content']);
    if (!empty($errors)) {
        errorResponse('Validation failed', 400, $errors);
    }

    $emailData = [
        'subject' => sanitizeInput($input['subject']),
        'content' => sanitizeInput($input['content']),
        'preview' => substr(sanitizeInput($input['content']), 0, 200) . '...'
    ];

    if (isset($input['labels'])) {
        $emailData['labels'] = $input['labels'];
    }

    if (isset($input['category'])) {
        $emailData['category'] = sanitizeInput($input['category']);
    }

    $success = $emailFunctions->updateEmail((int) $id, $emailData);

    if ($success) {
        $email = $emailFunctions->getEmailById((int) $id);
        successResponse($email, 'Email updated successfully');
    } else {
        errorResponse('Failed to update email', 500);
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest(EmailFunctions $emailFunctions, array $input): void
{
    $id = $input['id'] ?? null;

    if (!$id) {
        errorResponse('Email ID is required', 400);
    }

    $success = $emailFunctions->deleteEmail((int) $id);

    if ($success) {
        successResponse(null, 'Email deleted successfully');
    } else {
        errorResponse('Failed to delete email', 500);
    }
}

/**
 * Get emails list
 */
function handleGetEmails(EmailFunctions $emailFunctions, array $input): void
{
    $filters = [];

    // Parse filters
    if (!empty($input['category'])) {
        $filters['category'] = sanitizeInput($input['category']);
    }

    if (!empty($input['search'])) {
        $filters['search'] = sanitizeInput($input['search']);
    }

    if (!empty($input['date_from'])) {
        $filters['date_from'] = sanitizeInput($input['date_from']);
    }

    if (!empty($input['date_to'])) {
        $filters['date_to'] = sanitizeInput($input['date_to']);
    }

    // Pagination
    $page = (int) ($input['page'] ?? 1);
    $limit = min((int) ($input['limit'] ?? Config::DEFAULT_PAGE_SIZE), Config::MAX_PAGE_SIZE);

    $result = $emailFunctions->getEmails($filters, $page, $limit);

    successResponse($result);
}

/**
 * Get single email
 */
function handleGetSingleEmail(EmailFunctions $emailFunctions, array $input): void
{
    $id = $input['id'] ?? null;

    if (!$id) {
        errorResponse('Email ID is required', 400);
    }

    $email = $emailFunctions->getEmailById((int) $id);

    if ($email) {
        successResponse($email);
    } else {
        errorResponse('Email not found', 404);
    }
}

/**
 * Search emails
 */
function handleSearchEmails(EmailFunctions $emailFunctions, array $input): void
{
    $query = $input['q'] ?? '';

    if (empty($query)) {
        errorResponse('Search query is required', 400);
    }

    $filters = [];

    if (!empty($input['category'])) {
        $filters['category'] = sanitizeInput($input['category']);
    }

    if (!empty($input['is_starred'])) {
        $filters['is_starred'] = (bool) $input['is_starred'];
    }

    $emails = $emailFunctions->searchEmails(sanitizeInput($query), $filters);

    successResponse($emails);
}

/**
 * Get email statistics
 */
function handleGetStats(EmailFunctions $emailFunctions): void
{
    $stats = $emailFunctions->getEmailStats();
    successResponse($stats);
}

/**
 * Create new email
 */
function handleCreateEmail(EmailFunctions $emailFunctions, array $input): void
{
    $errors = validateRequired($input, ['to_email', 'subject', 'content']);
    if (!empty($errors)) {
        errorResponse('Validation failed', 400, $errors);
    }

    // Validate email
    if (!validateEmail($input['to_email'])) {
        errorResponse('Invalid recipient email', 400);
    }

    $emailData = [
        'from_name' => $input['from_name'] ?? 'Me',
        'from_email' => $input['from_email'] ?? 'me@example.com',
        'to_email' => sanitizeInput($input['to_email']),
        'cc_email' => !empty($input['cc_email']) ? sanitizeInput($input['cc_email']) : null,
        'bcc_email' => !empty($input['bcc_email']) ? sanitizeInput($input['bcc_email']) : null,
        'subject' => sanitizeInput($input['subject']),
        'preview' => substr(sanitizeInput($input['content']), 0, 200) . '...',
        'content' => sanitizeInput($input['content']),
        'category' => 'primary',
        'labels' => ['Sent'],
        'is_read' => 1,
        'is_starred' => 0
    ];

    if (isset($input['attachments'])) {
        $emailData['attachments'] = $input['attachments'];
    }

    $emailId = $emailFunctions->createEmail($emailData);

    if ($emailId) {
        $email = $emailFunctions->getEmailById($emailId);
        successResponse($email, 'Email sent successfully');
    } else {
        errorResponse('Failed to send email', 500);
    }
}

/**
 * Mark emails as read
 */
function handleMarkAsRead(EmailFunctions $emailFunctions, array $input): void
{
    // Log incoming payload for debugging
    logMessage('handleMarkAsRead called with input: ' . json_encode($input), 'DEBUG');

    $emailIds = $input['email_ids'] ?? [];

    if (empty($emailIds)) {
        logMessage('handleMarkAsRead failed: email_ids empty', 'ERROR');
        errorResponse('Email IDs are required', 400);
    }

    try {
        logMessage('Calling markEmails with IDs: ' . json_encode($emailIds), 'DEBUG');
        $success = $emailFunctions->markEmails($emailIds, true);
        logMessage('markEmails returned: ' . ($success ? 'true' : 'false'), 'DEBUG');

        if ($success) {
            successResponse(null, 'Emails marked as read');
        } else {
            $dbError = Database::getError();
            logMessage('markEmails failed. DB error: ' . $dbError, 'ERROR');
            errorResponse('Failed to mark emails as read. DB error: ' . $dbError, 500);
        }
    } catch (Exception $e) {
        logMessage('handleMarkAsRead exception: ' . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

/**
 * Mark emails as unread
 */
function handleMarkAsUnread(EmailFunctions $emailFunctions, array $input): void
{
    $emailIds = $input['email_ids'] ?? [];

    if (empty($emailIds)) {
        errorResponse('Email IDs are required', 400);
    }

    $success = $emailFunctions->markEmails($emailIds, false);

    if ($success) {
        successResponse(null, 'Emails marked as unread');
    } else {
        errorResponse('Failed to mark emails as unread', 500);
    }
}

/**
 * Toggle email star
 */
function handleToggleStar(EmailFunctions $emailFunctions, array $input): void
{
    $emailId = $input['email_id'] ?? null;

    if (!$emailId) {
        errorResponse('Email ID is required', 400);
    }

    $success = $emailFunctions->toggleStar((int) $emailId);

    if ($success) {
        $email = $emailFunctions->getEmailById((int) $emailId);
        successResponse($email, 'Email star updated');
    } else {
        errorResponse('Failed to update star', 500);
    }
}

/**
 * Move emails to folder
 */
function handleMoveEmails(EmailFunctions $emailFunctions, array $input): void
{
    $emailIds = $input['email_ids'] ?? [];
    $destination = $input['destination'] ?? '';

    if (empty($emailIds)) {
        errorResponse('Email IDs are required', 400);
    }

    if (empty($destination)) {
        errorResponse('Destination is required', 400);
    }

    $validDestinations = ['trash', 'archive', 'spam'];
    if (!in_array($destination, $validDestinations)) {
        errorResponse('Invalid destination', 400);
    }

    $success = $emailFunctions->moveEmails($emailIds, $destination);

    if ($success) {
        successResponse(null, "Emails moved to $destination");
    } else {
        errorResponse('Failed to move emails', 500);
    }
}

/**
 * Delete emails
 */
function handleDeleteEmails(EmailFunctions $emailFunctions, array $input): void
{
    $emailIds = $input['email_ids'] ?? [];

    if (empty($emailIds)) {
        errorResponse('Email IDs are required', 400);
    }

    $successCount = 0;
    foreach ($emailIds as $emailId) {
        if ($emailFunctions->deleteEmail((int) $emailId)) {
            $successCount++;
        }
    }

    if ($successCount > 0) {
        successResponse(null, "$successCount emails deleted successfully");
    } else {
        errorResponse('Failed to delete emails', 500);
    }
}

/**
 * Handle bulk operations
 */
function handleBulkOperation(EmailFunctions $emailFunctions, array $input): void
{
    $emailIds = $input['email_ids'] ?? [];
    $operation = $input['operation'] ?? '';

    if (empty($emailIds)) {
        errorResponse('Email IDs are required', 400);
    }

    if (empty($operation)) {
        errorResponse('Operation is required', 400);
    }

    $result = ['success' => false, 'message' => ''];

    switch ($operation) {
        case 'mark_read':
            $success = $emailFunctions->markEmails($emailIds, true);
            $result['message'] = $success ? 'Emails marked as read' : 'Failed to mark emails as read';
            break;

        case 'mark_unread':
            $success = $emailFunctions->markEmails($emailIds, false);
            $result['message'] = $success ? 'Emails marked as unread' : 'Failed to mark emails as unread';
            break;

        case 'star':
            foreach ($emailIds as $emailId) {
                $emailFunctions->updateEmail((int) $emailId, ['is_starred' => 1]);
            }
            $result['success'] = true;
            $result['message'] = 'Emails starred';
            break;

        case 'unstar':
            foreach ($emailIds as $emailId) {
                $emailFunctions->updateEmail((int) $emailId, ['is_starred' => 0]);
            }
            $result['success'] = true;
            $result['message'] = 'Emails unstarred';
            break;

        case 'trash':
            $success = $emailFunctions->moveEmails($emailIds, 'trash');
            $result['message'] = $success ? 'Emails moved to trash' : 'Failed to move emails to trash';
            break;

        case 'delete':
            $successCount = 0;
            foreach ($emailIds as $emailId) {
                if ($emailFunctions->deleteEmail((int) $emailId)) {
                    $successCount++;
                }
            }
            $result['success'] = $successCount > 0;
            $result['message'] = "$successCount emails deleted";
            break;

        default:
            errorResponse('Invalid operation', 400);
            break;
    }

    if ($result['success']) {
        successResponse(null, $result['message']);
    } else {
        errorResponse($result['message'], 500);
    }
}
?>