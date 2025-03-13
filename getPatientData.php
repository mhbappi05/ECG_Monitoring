<?php
include 'db.php';
session_start();

$doctor_id = $_SESSION['doctor_id'];
$patient_id = $_GET['patient_id'];  // Patient ID passed from the frontend

// Check if the patient belongs to the doctor
$query = "SELECT * FROM users WHERE id=? AND doctor_id=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $patient_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the patient's data
    $patient_data = [
        'ecg_data' => 'ECG Data Here', // Replace with actual data retrieval
        'oxygen_level' => 98.5, // Replace with actual data
        'heart_rate' => 72, // Replace with actual data
        'blood_pressure' => '120/80', // Replace with actual data
        'status' => 'normal' // Replace with actual data
    ];

    echo json_encode($patient_data);
} else {
    echo json_encode(['error' => 'Patient not found or not assigned to this doctor.']);
}

$stmt->close();
$conn->close();
?>
