<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];  // New field to capture user role (doctor or patient)

    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO users (name, phone, password, role) VALUES (?, ?, ?, ?)");
        
        // Check if prepare() failed
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        // Bind parameters and execute
        $stmt->bind_param("ssss", $name, $phone, $password, $role);
        $stmt->execute();
        
        // Get the newly created user ID
        $user_id = $conn->insert_id;
        
        // Create a table specific to this user, using user_id in the table name to ensure uniqueness
        if ($role == 'doctor') {
            $table_name = "doctor_" . $user_id . "_data";
        } else {
            $table_name = "patient_" . $user_id . "_data";
        }

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
    
    $stmt->close();  // Close the statement properly
    $conn->close();  // Close the database connection
}
?>
