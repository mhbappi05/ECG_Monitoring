<?php
include 'db.php';
session_start();

// Assuming the doctor is logged in, and their ID is stored in session
$doctor_id = $_SESSION['doctor_id'];

$query = "SELECT id, name, phone FROM users WHERE role='patient' AND doctor_id=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

echo json_encode($patients);

$stmt->close();
$conn->close();
?>
