<?php
/**
 * Email Functions
 * PHP 8.1+ with MySQLi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class EmailFunctions
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get emails with filtering and pagination
     */
    public function getEmails(array $filters = [], int $page = 1, int $limit = Config::DEFAULT_PAGE_SIZE): array
    {
        $offset = ($page - 1) * $limit;
        $where = ['1=1'];
        $params = [];

        // Filter by category
        if (!empty($filters['category'])) {
            switch ($filters['category']) {
                case 'inbox':
                    $where[] = 'e.is_read = 0';
                    break;
                case 'starred':
                    $where[] = 'e.is_starred = 1';
                    break;
                case 'sent':
                    $where[] = 'JSON_CONTAINS(e.labels, \'"Sent"\')';
                    break;
                case 'drafts':
                    $where[] = 'JSON_CONTAINS(e.labels, \'"Draft"\')';
                    break;
                case 'spam':
                    $where[] = 'e.category = \'spam\'';
                    break;
                case 'trash':
                    $where[] = 'JSON_CONTAINS(e.labels, \'"Trash"\')';
                    break;
                case 'social':
                    $where[] = 'e.category = \'social\'';
                    break;
                case 'promotions':
                    $where[] = 'e.category = \'promotions\'';
                    break;
                case 'updates':
                    $where[] = 'e.category = \'updates\'';
                    break;
            }
        }

        // Search filter
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $where[] = '(e.subject LIKE ? OR e.from_name LIKE ? OR e.preview LIKE ?)';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Date filter
        if (!empty($filters['date_from'])) {
            $where[] = 'e.created_at >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'e.created_at <= ?';
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM emails e WHERE $whereClause";
        $totalResult = Database::fetchOne($countSql, $params);
        $total = $totalResult['total'] ?? 0;

        // Get emails
        $sql = "SELECT e.*, 
                       (SELECT COUNT(*) FROM email_attachments ea WHERE ea.email_id = e.id) as attachment_count
                FROM emails e 
                WHERE $whereClause 
                ORDER BY e.created_at DESC 
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $emails = Database::fetchAll($sql, $params);

        // Format emails
        foreach ($emails as &$email) {
            $email = $this->formatEmail($email);
        }

        return [
            'data' => $emails,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get single email by ID
     */
    public function getEmailById(int $id): ?array
    {
        $sql = "SELECT e.*, 
                       (SELECT COUNT(*) FROM email_attachments ea WHERE ea.email_id = e.id) as attachment_count
                FROM emails e 
                WHERE e.id = ?";

        $email = Database::fetchOne($sql, [$id]);

        if ($email) {
            $email = $this->formatEmail($email);
            $email['attachments'] = $this->getEmailAttachments($id);
        }

        return $email;
    }

    /**
     * Create new email
     */
    public function createEmail(array $data): int
    {
        $sql = "INSERT INTO emails (
                    from_name, from_email, to_email, cc_email, bcc_email,
                    subject, preview, content, category, labels, 
                    is_read, is_starred, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $labels = json_encode($data['labels'] ?? []);
        $createdAt = getCurrentTimestamp();

        $params = [
            $data['from_name'] ?? '',
            $data['from_email'] ?? '',
            $data['to_email'] ?? '',
            $data['cc_email'] ?? null,
            $data['bcc_email'] ?? null,
            $data['subject'] ?? '',
            $data['preview'] ?? '',
            $data['content'] ?? '',
            $data['category'] ?? 'primary',
            $labels,
            (int) ($data['is_read'] ?? 0),
            (int) ($data['is_starred'] ?? 0),
            $createdAt,
            $createdAt
        ];

        Database::query($sql, $params);

        $emailId = Database::lastInsertId();

        // Handle attachments if any
        if (!empty($data['attachments'])) {
            $this->saveAttachments($emailId, $data['attachments']);
        }

        return $emailId;
    }

    /**
     * Update email
     */
    public function updateEmail(int $id, array $data): bool
    {
        $setParts = [];
        $params = [];

        $allowedFields = [
            'subject',
            'preview',
            'content',
            'category',
            'is_read',
            'is_starred',
            'labels'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "$field = ?";
                if ($field === 'labels') {
                    $params[] = json_encode($data[$field]);
                } else {
                    $params[] = $data[$field];
                }
            }
        }

        if (empty($setParts)) {
            return false;
        }

        $setParts[] = 'updated_at = ?';
        $params[] = getCurrentTimestamp();
        $params[] = $id;

        $sql = "UPDATE emails SET " . implode(', ', $setParts) . " WHERE id = ?";

        return Database::query($sql, $params) !== false;
    }

    /**
     * Delete email
     */
    public function deleteEmail(int $id): bool
    {
        // Delete attachments first
        $this->deleteAttachments($id);

        // Delete email
        $sql = "DELETE FROM emails WHERE id = ?";

        return Database::query($sql, [$id]) !== false;
    }

    /**
     * Mark emails as read/unread
     */
    public function markEmails(array $emailIds, bool $isRead = true): bool
    {
        if (empty($emailIds)) {
            return false;
        }

        $placeholders = str_repeat('?,', count($emailIds) - 1) . '?';
        $sql = "UPDATE emails SET is_read = ?, updated_at = ? WHERE id IN ($placeholders)";

        $params = array_merge(
            [(int) $isRead, getCurrentTimestamp()],
            $emailIds
        );

        return Database::query($sql, $params) !== false;
    }

    /**
     * Toggle email star
     */
    public function toggleStar(int $emailId): bool
    {
        $sql = "UPDATE emails 
                SET is_starred = NOT is_starred, updated_at = ? 
                WHERE id = ?";

        return Database::query($sql, [getCurrentTimestamp(), $emailId]) !== false;
    }

    /**
     * Move emails to category/folder
     */
    public function moveEmails(array $emailIds, string $destination): bool
    {
        if (empty($emailIds)) {
            return false;
        }

        $placeholders = str_repeat('?,', count($emailIds) - 1) . '?';

        switch ($destination) {
            case 'trash':
                $sql = "UPDATE emails 
                        SET labels = JSON_ARRAY_APPEND(IFNULL(labels, JSON_ARRAY()), '$', 'Trash'), 
                            updated_at = ? 
                        WHERE id IN ($placeholders)";
                break;
            case 'archive':
                $sql = "UPDATE emails 
                        SET labels = JSON_ARRAY_APPEND(IFNULL(labels, JSON_ARRAY()), '$', 'Archive'), 
                            updated_at = ? 
                        WHERE id IN ($placeholders)";
                break;
            case 'spam':
                $sql = "UPDATE emails 
                        SET category = 'spam', updated_at = ? 
                        WHERE id IN ($placeholders)";
                break;
            default:
                return false;
        }

        $params = array_merge([getCurrentTimestamp()], $emailIds);

        return Database::query($sql, $params) !== false;
    }

    /**
     * Search emails
     */
    public function searchEmails(string $query, array $filters = []): array
    {
        $searchTerm = '%' . $query . '%';
        $where = [
            '(e.subject LIKE ? OR e.from_name LIKE ? OR e.preview LIKE ? OR e.content LIKE ?)'
        ];
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];

        // Add additional filters
        if (!empty($filters['category'])) {
            $where[] = 'e.category = ?';
            $params[] = $filters['category'];
        }

        if (!empty($filters['is_starred'])) {
            $where[] = 'e.is_starred = ?';
            $params[] = $filters['is_starred'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT e.*, 
                       (SELECT COUNT(*) FROM email_attachments ea WHERE ea.email_id = e.id) as attachment_count
                FROM emails e 
                WHERE $whereClause 
                ORDER BY e.created_at DESC 
                LIMIT 100";

        $emails = Database::fetchAll($sql, $params);

        // Format emails
        foreach ($emails as &$email) {
            $email = $this->formatEmail($email);
        }

        return $emails;
    }

    /**
     * Get email statistics
     */
    public function getEmailStats(): array
    {
        $stats = [];

        // Total emails
        $stats['total'] = Database::fetchValue("SELECT COUNT(*) FROM emails") ?? 0;

        // Unread emails
        $stats['unread'] = Database::fetchValue("SELECT COUNT(*) FROM emails WHERE is_read = 0") ?? 0;

        // Starred emails
        $stats['starred'] = Database::fetchValue("SELECT COUNT(*) FROM emails WHERE is_starred = 1") ?? 0;

        // Category counts
        $categories = ['primary', 'social', 'promotions', 'updates', 'spam'];
        foreach ($categories as $category) {
            $stats[$category] = Database::fetchValue(
                "SELECT COUNT(*) FROM emails WHERE category = ?",
                [$category]
            ) ?? 0;
        }

        // Label counts
        $labels = ['Sent', 'Draft', 'Trash', 'Archive'];
        foreach ($labels as $label) {
            $stats[strtolower($label)] = Database::fetchValue(
                "SELECT COUNT(*) FROM emails WHERE JSON_CONTAINS(labels, ?)",
                ['"' . $label . '"']
            ) ?? 0;
        }

        return $stats;
    }

    /**
     * Format email data
     */
    private function formatEmail(array $email): array
    {
        // Parse JSON fields
        $email['labels'] = json_decode($email['labels'] ?? '[]', true) ?: [];
        $email['is_read'] = (bool) $email['is_read'];
        $email['is_starred'] = (bool) $email['is_starred'];
        $email['attachment_count'] = (int) ($email['attachment_count'] ?? 0);

        // Format date
        $email['date'] = $email['created_at'];
        $email['formatted_date'] = $this->formatDate($email['created_at']);

        // Rename fields for frontend compatibility
        $email['from'] = $email['from_name'];
        $email['fromEmail'] = $email['from_email'];

        return $email;
    }

    /**
     * Format date for display
     */
    private function formatDate(string $dateString): string
    {
        $date = new DateTime($dateString);
        $now = new DateTime();
        $diff = $now->diff($date);

        if ($diff->days == 0) {
            return $date->format('g:i A');
        } elseif ($diff->days == 1) {
            return 'Yesterday';
        } elseif ($diff->days < 7) {
            return $date->format('D');
        } elseif ($diff->days < 365) {
            return $date->format('M j');
        } else {
            return $date->format('M j, Y');
        }
    }

    /**
     * Get email attachments
     */
    private function getEmailAttachments(int $emailId): array
    {
        $sql = "SELECT * FROM email_attachments WHERE email_id = ? ORDER BY created_at";
        return Database::fetchAll($sql, [$emailId]);
    }

    /**
     * Save email attachments
     */
    private function saveAttachments(int $emailId, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $sql = "INSERT INTO email_attachments (
                        email_id, name, size, type, file_path, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?)";

            Database::query($sql, [
                $emailId,
                $attachment['name'],
                $attachment['size'],
                $attachment['type'],
                $attachment['file_path'] ?? null,
                getCurrentTimestamp()
            ]);
        }
    }

    /**
     * Delete email attachments
     */
    private function deleteAttachments(int $emailId): void
    {
        // Get attachments to delete files
        $attachments = $this->getEmailAttachments($emailId);

        foreach ($attachments as $attachment) {
            if (!empty($attachment['file_path']) && file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }
        }

        // Delete from database
        $sql = "DELETE FROM email_attachments WHERE email_id = ?";
        Database::query($sql, [$emailId]);
    }
}
?>