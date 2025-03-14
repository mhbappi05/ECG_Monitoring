<?php
session_start();
include 'db.php';

// Check if the user is logged in and is a doctor
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403);
    echo "Unauthorized access.";
    exit();
}

// Validate and sanitize input
$patient_id = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($patient_id && $message) {
    // Insert message into the database
    $sql = "INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $_SESSION['id'], $patient_id, $message);

    if ($stmt->execute()) {
        echo "Message sent successfully.";
    } else {
        http_response_code(500);
        echo "Failed to send message.";
    }
} else {
    http_response_code(400);
    echo "Invalid input.";
}
?>