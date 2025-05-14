<?php
/**
 * Schedule utility functions
 */

/**
 * Check if there's a schedule conflict
 * @param PDO $db Database connection
 * @param int $course_id Course ID
 * @param string $day Day of the week
 * @param string $start_time Start time (HH:MM)
 * @param string $end_time End time (HH:MM)
 * @param int|null $exclude_id Schedule ID to exclude from conflict check (for updates)
 * @return bool True if conflict exists, false otherwise
 */
function has_schedule_conflict($db, $course_id, $day, $start_time, $end_time, $exclude_id = null) {
    $sql = "SELECT id FROM schedules WHERE course_id = ? AND day = ?";
    $params = [$course_id, $day];
    $types = "is";
    
    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
        $types .= "i";
    }
    
    $sql .= " AND ((start_time <= ? AND end_time > ?) OR 
                   (start_time < ? AND end_time >= ?) OR 
                   (start_time >= ? AND end_time <= ?))";
    
    $params = array_merge($params, [$start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
    $types .= str_repeat("s", 6);
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Validate time format
 * @param string $time Time string to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_time_format($time) {
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
}

/**
 * Format time for display
 * @param string $time Time string (HH:MM)
 * @return string Formatted time (HH:MM AM/PM)
 */
function format_time_display($time) {
    return date('h:i A', strtotime($time));
}

/**
 * Get all days of the week
 * @return array Array of days
 */
function get_days_of_week() {
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
}
?> 