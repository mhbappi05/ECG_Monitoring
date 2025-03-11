<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert user into users table
        $stmt = $conn->prepare("INSERT INTO users (name, phone, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $phone, $password);
        $stmt->execute();
        
        // Get the newly created user ID
        $user_id = $conn->insert_id;
        
        // Create a table specific to this user
        // Use user_id in the table name to ensure uniqueness
        $table_name = "client_" . $user_id . "_data";
        
        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ecg_data TEXT,
            fetal_ecg_data TEXT,
            oxygen_level FLOAT,
            heart_rate INT,
            blood_pressure VARCHAR(20),
            notes TEXT,
            status VARCHAR(50) DEFAULT 'normal'
        )";
        
        $conn->query($sql);
        
        // Commit transaction
        $conn->commit();
        
        // Redirect directly to login page instead of showing a message
        header("Location: login.html");
        exit();
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
    
    $stmt->close();
    $conn->close();
}
?><?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert user into users table
        $stmt = $conn->prepare("INSERT INTO users (name, phone, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $phone, $password);
        $stmt->execute();
        
        // Get the newly created user ID
        $user_id = $conn->insert_id;
        
        // Create a table specific to this user
        // Use user_id in the table name to ensure uniqueness
        $table_name = "client_" . $user_id . "_data";
        
        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ecg_data TEXT,
            fetal_ecg_data TEXT,
            oxygen_level FLOAT,
            heart_rate INT,
            blood_pressure VARCHAR(20),
            notes TEXT,
            status VARCHAR(50) DEFAULT 'normal'
        )";
        
        $conn->query($sql);
        
        // Commit transaction
        $conn->commit();
        
        // Redirect directly to login page instead of showing a message
        header("Location: login.html");
        exit();
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
    
    $stmt->close();
    $conn->close();
}
?>