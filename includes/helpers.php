<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/db.php';

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with optional flash message
 */
function redirect(string $url, ?string $type = null, ?string $message = null): void {
    if ($type && $message) {
        setFlash($type, $message);
    }
    header("Location: $url");
    exit;
}

/**
 * Format date
 */
function formatDate(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

/**
 * Format date relative
 */
function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d', $time);
}

/**
 * Get user by ID
 */
function getUserById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Calculate percentage
 */
function calcPercent(int $part, int $total): float {
    return $total > 0 ? round(($part / $total) * 100, 1) : 0;
}

/**
 * Generate pagination HTML
 */
function paginate(int $total, int $perPage, int $currentPage, string $baseUrl): string {
    $totalPages = ceil($total / $perPage);
    if ($totalPages <= 1) return '';
    
    $html = '<nav class="flex items-center justify-center space-x-1 mt-6">';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage - 1) . '" class="px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>';
    }
    
    // Pages
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '&page=1" class="px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">1</a>';
        if ($start > 2) $html .= '<span class="px-2 text-gray-400">...</span>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="px-3 py-2 rounded-lg bg-blue-600 text-white font-medium">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">' . $i . '</a>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="px-2 text-gray-400">...</span>';
        $html .= '<a href="' . $baseUrl . '&page=' . $totalPages . '" class="px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">' . $totalPages . '</a>';
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage + 1) . '" class="px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
    }
    
    $html .= '</nav>';
    return $html;
}

/**
 * Handle file upload
 */
function uploadFile(array $file, string $destination, array $allowedTypes = []): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_UPLOAD_SIZE) return null;
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($ext, $allowedTypes)) return null;
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $destination . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    return null;
}

/**
 * Get streak for user
 */
function getUserStreak(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT current_streak FROM user_streaks WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['current_streak'] : 0;
}

/**
 * Update user streak
 */
function updateStreak(int $userId): void {
    $db = getDB();
    $today = date('Y-m-d');
    
    $stmt = $db->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
    $stmt->execute([$userId]);
    $streak = $stmt->fetch();
    
    if (!$streak) {
        $stmt = $db->prepare("INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_activity_date) VALUES (?, 1, 1, ?)");
        $stmt->execute([$userId, $today]);
        return;
    }
    
    $lastDate = $streak['last_activity_date'];
    if ($lastDate === $today) return;
    
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($lastDate === $yesterday) {
        $newStreak = $streak['current_streak'] + 1;
        $longest = max($newStreak, $streak['longest_streak']);
        $stmt = $db->prepare("UPDATE user_streaks SET current_streak = ?, longest_streak = ?, last_activity_date = ? WHERE user_id = ?");
        $stmt->execute([$newStreak, $longest, $today, $userId]);
    } else {
        $stmt = $db->prepare("UPDATE user_streaks SET current_streak = 1, last_activity_date = ? WHERE user_id = ?");
        $stmt->execute([$today, $userId]);
    }
}

/**
 * Log activity
 */
function logActivity(int $userId, string $type, string $description, ?int $referenceId = null): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, activity_type, description, reference_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $description, $referenceId]);
    updateStreak($userId);
}

/**
 * Log mistake
 */
function logMistake(int $userId, string $module, int $questionId, string $questionText, string $userAnswer, string $correctAnswer, ?string $explanation = null): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO mistake_reviews (user_id, module, question_id, question_text, user_answer, correct_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $module, $questionId, $questionText, $userAnswer, $correctAnswer, $explanation]);
}

/**
 * Get count helper
 */
function getCount(string $table, string $where = '1=1', array $params = []): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM $table WHERE $where");
    $stmt->execute($params);
    return (int)$stmt->fetch()['cnt'];
}
