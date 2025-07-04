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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['doctor_id'])) {
    $doctor_id = $_POST['doctor_id'];
    $user_id = $_SESSION['id'];  // Assuming user is logged in

    // Fetch doctor and user names (modify your database query as needed)
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $doctor_name = $stmt->fetchColumn() ?: 'Doctor';

    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $patient_name = $stmt->fetchColumn() ?: 'Patient';

    // Fetch chat history between the user and the doctor
    $stmt = $pdo->prepare("SELECT sender_id, message, created_at FROM messages WHERE 
                          (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
                          ORDER BY created_at ASC");
    $stmt->execute([$user_id, $doctor_id, $doctor_id, $user_id]);

    $message_html = "";

    if ($stmt->rowCount() > 0) {
        while ($message = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $isSender = $message['sender_id'] == $doctor_id;
            $messageClass = $isSender ? 'text-end' : 'text-start';
            $nameDisplay = $isSender ? 'Dr. ' . htmlspecialchars($doctor_name) : htmlspecialchars($patient_name);

            $message_html .= '<div class="message ' . $messageClass . ' mb-3">';
            $message_html .= '<p class="mb-1"><strong>' . $nameDisplay . ':</strong> ' . htmlspecialchars($message['message']) . '</p>';
            $message_html .= '<small class="text-muted">' . $message['created_at'] . '</small>';
            $message_html .= '</div>';
        }
    } else {
        $message_html = '<p>No messages yet. Start the conversation!</p>';
    }

    echo json_encode(['status' => 'success', 'messages' => $message_html]);
}

if (isset($_POST['patient_id'])) {
    $doctor_id = $_SESSION['id']; // Doctor is logged in
    $patient_id = $_POST['patient_id']; // Patient's ID from the request

    // Fetch doctor and patient names
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $doctor_name = $stmt->fetchColumn() ?: 'Doctor';

    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient_name = $stmt->fetchColumn() ?: 'Patient';

    // Fetch chat history
    $stmt = $pdo->prepare("SELECT sender_id, message, created_at FROM messages WHERE 
                          (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
                          ORDER BY created_at ASC");
    $stmt->execute([$doctor_id, $patient_id, $patient_id, $doctor_id]);

    $message_html = "";

    if ($stmt->rowCount() > 0) {
        while ($message = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $isSender = $message['sender_id'] == $doctor_id;
            $messageClass = $isSender ? 'text-end' : 'text-start';
            $nameDisplay = $isSender ? 'Dr. ' . htmlspecialchars($doctor_name) : htmlspecialchars($patient_name);

            $message_html .= '<div class="message ' . $messageClass . ' mb-3">';
            $message_html .= '<p class="mb-1"><strong>' . $nameDisplay . ':</strong> ' . htmlspecialchars($message['message']) . '</p>';
            $message_html .= '<small class="text-muted">' . $message['created_at'] . '</small>';
            $message_html .= '</div>';
        }
    } else {
        $message_html = '<p>No messages yet. Start the conversation!</p>';
    }

    echo json_encode(['status' => 'success', 'messages' => $message_html]);
}
?>