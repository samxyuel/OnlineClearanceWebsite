<?php
echo "=== DIAGNOSTIC CHECK ===\n";

// Check if database config exists
if (file_exists('includes/config/database.php')) {
    echo "✅ Database config file exists\n";
} else {
    echo "❌ Database config file missing\n";
    exit;
}

// Check if database class exists
if (file_exists('includes/classes/Auth.php')) {
    echo "✅ Auth class exists\n";
} else {
    echo "❌ Auth class missing\n";
}

// Check if API files exist
$apiFiles = [
    'api/clearance/periods.php',
    'api/clearance/requirements.php', 
    'api/clearance/applications.php',
    'api/clearance/status.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}

echo "\n=== END DIAGNOSTIC ===\n";
?>
