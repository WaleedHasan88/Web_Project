<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

class RegistrationProcessor {
    private $db;
    private $uploadDir;
    private $allowedFileTypes = ['pdf', 'doc', 'docx'];
    private $maxFileSize = 5242880; // 5MB

    public function __construct($db) {
        $this->db = $db;
        $this->uploadDir = __DIR__ . '/../uploads/applications/';
        $this->ensureUploadDirectory();
    }

    private function ensureUploadDirectory() {
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    private function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error uploading file: " . $file['name']);
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new Exception("File too large: " . $file['name']);
        }

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $this->allowedFileTypes)) {
            throw new Exception("Invalid file type: " . $file['name']);
        }

        return true;
    }

    private function uploadFile($file, $prefix = '') {
        $this->validateFile($file);
        
        $fileName = $prefix . '_' . uniqid() . '_' . basename($file['name']);
        $filePath = 'uploads/applications/' . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $fileName)) {
            throw new Exception("Failed to move uploaded file: " . $file['name']);
        }
        
        return $filePath;
    }

    private function validateInput($data) {
        $required = [
            'firstName', 'lastName', 'email', 'phone', 'dob',
            'program', 'major', 'gpa', 'statement'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: " . $field);
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (!is_numeric($data['gpa']) || $data['gpa'] < 0 || $data['gpa'] > 4.0) {
            throw new Exception("Invalid GPA value");
        }

        return true;
    }

    public function process($postData, $files) {
        try {
            // Validate input data
            $this->validateInput($postData);

            // Process file uploads
            $filePaths = [
                'transcript' => $this->uploadFile($files['transcript'], 'transcript'),
                'testScores' => $this->uploadFile($files['testScores'], 'scores'),
                'recommendation' => $this->uploadFile($files['recommendation'], 'recommendation')
            ];

            // Prepare and execute database insertion
            $sql = "INSERT INTO applications (
                first_name, last_name, email, phone, date_of_birth,
                program, major, gpa, transcript_path, test_scores_path,
                recommendation_path, personal_statement, extracurricular_activities,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database preparation failed: " . $this->db->error);
            }

            $extracurricular = $postData['extracurricular'] ?? '';

            $stmt->bind_param(
                "sssssssdsssss",
                $postData['firstName'],
                $postData['lastName'],
                $postData['email'],
                $postData['phone'],
                $postData['dob'],
                $postData['program'],
                $postData['major'],
                $postData['gpa'],
                $filePaths['transcript'],
                $filePaths['testScores'],
                $filePaths['recommendation'],
                $postData['statement'],
                $extracurricular
            );

            if (!$stmt->execute()) {
                throw new Exception("Database execution failed: " . $stmt->error);
            }

            $_SESSION['success_message'] = "Your application has been submitted successfully! We will review it and get back to you soon.";
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            error_log("Registration Error: " . $e->getMessage());
        }

        header("Location: register.php");
        exit();
    }
}

// Process the registration if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processor = new RegistrationProcessor($conn);
    $processor->process($_POST, $_FILES);
} else {
    header("Location: register.php");
    exit();
}
?> 