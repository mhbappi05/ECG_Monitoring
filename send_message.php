<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "ecg_monitoring";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if the request is POST and necessary parameters are present
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receiver_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : null;  // doctor_id will be receiver
    $sender_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;  // user_id will be sender
    $message = isset($_POST['message']) ? $_POST['message'] : '';

    // Check if the required fields are present
    if ($receiver_id && $sender_id && $message) {
        try {
            var_dump($_POST);

            // Insert the message into the database
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$sender_id, $receiver_id, $message]);

            echo "Message sent!";
            echo json_encode(["status" => "success", "message" => "Message sent!"]);

        } catch (PDOException $e) {
            echo "Error inserting message: " . $e->getMessage();
        }
    } else {
        echo "Missing required parameters!";
    }
} else {
    echo "Invalid request method!";
}
