<?php
// migrate.php
// WARNING: Delete or rename this file after running it on the live server to prevent unauthorized access!

require_once 'config.php';

echo "<h2>Database Migration Script</h2>";

try {
    // Write your future ALTER TABLE or CREATE TABLE queries here.
    // Example: Adding a phone_number column if it doesn't exist
    
    // $pdo->exec("ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL");
    // echo "<p>Added phone_number column to users table.</p>";

    
    // Put your new queries above this line ^
    echo "<p style='color: green;'><strong>All migrations executed successfully!</strong></p>";

} catch (PDOException $e) {
    // If a column already exists, it might throw an error. You can ignore specific errors or check before adding.
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<br><b>IMPORTANT:</b> Please delete this file from the live server after use for security reasons.";
?>
