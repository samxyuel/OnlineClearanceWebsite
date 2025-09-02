<?php
require_once 'includes/config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Creating missing clearance tables...\n";
    
    // Create clearance_periods table
    $sql = "CREATE TABLE IF NOT EXISTS `clearance_periods` (
        `period_id` INT PRIMARY KEY AUTO_INCREMENT,
        `academic_year_id` INT NOT NULL,
        `semester_id` INT NOT NULL,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `is_active` BOOLEAN DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`academic_year_id`),
        FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`semester_id`)
    )";
    
    $connection->exec($sql);
    echo "✅ Created clearance_periods table\n";
    
    // Create clearance_applications table
    $sql = "CREATE TABLE IF NOT EXISTS `clearance_applications` (
        `application_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `period_id` INT NOT NULL,
        `status` ENUM('pending', 'in-progress', 'completed', 'rejected') DEFAULT 'pending',
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `completed_at` TIMESTAMP NULL,
        `rejected_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
        FOREIGN KEY (`period_id`) REFERENCES `clearance_periods`(`period_id`)
    )";
    
    $connection->exec($sql);
    echo "✅ Created clearance_applications table\n";
    
    // Create clearance_signatory_status table
    $sql = "CREATE TABLE IF NOT EXISTS `clearance_signatory_status` (
        `status_id` INT PRIMARY KEY AUTO_INCREMENT,
        `application_id` INT NOT NULL,
        `requirement_id` INT NOT NULL,
        `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        `approved_at` TIMESTAMP NULL,
        `rejected_at` TIMESTAMP NULL,
        `rejection_reason_id` INT NULL,
        `additional_remarks` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`application_id`) REFERENCES `clearance_applications`(`application_id`),
        FOREIGN KEY (`requirement_id`) REFERENCES `clearance_requirements`(`requirement_id`),
        FOREIGN KEY (`rejection_reason_id`) REFERENCES `rejection_reasons`(`reason_id`)
    )";
    
    $connection->exec($sql);
    echo "✅ Created clearance_signatory_status table\n";
    
    echo "\n✅ All clearance tables created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
