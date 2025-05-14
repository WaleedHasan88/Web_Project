<?php
// Function to create applications table if it doesn't exist
function create_applications_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        date_of_birth DATE NOT NULL,
        program VARCHAR(50) NOT NULL,
        major VARCHAR(50) NOT NULL,
        gpa DECIMAL(3,2) NOT NULL,
        personal_statement TEXT NOT NULL,
        extracurricular_activities TEXT,
        transcript_path VARCHAR(255) NOT NULL,
        test_scores_path VARCHAR(255) NOT NULL,
        recommendation_path VARCHAR(255) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if (!$conn->query($sql)) {
        error_log("Error creating applications table: " . $conn->error);
        return false;
    }
    return true;
}

// Create applications table if it doesn't exist
$conn = get_db_connection();
create_applications_table($conn);
?> 