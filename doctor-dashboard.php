<?php
session_start();
include 'db.php';

// Check if the user is logged in and is a doctor
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.html");
    exit();
}

// Fetch the doctor's name from the database
$doctor_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $stmt->bind_result($doctor_name);
    $stmt->fetch();
    $stmt->close();
} else {
    die("Database error: " . mysqli_error($conn));
}

// Handle search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$result = [];
if (!empty($search)) {
    $sql = "SELECT id, name, phone FROM users WHERE role = 'patient' AND (name LIKE CONCAT('%', ?, '%') OR phone LIKE CONCAT('%', ?, '%'))";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $searchTerm = "%$search%";
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
}

// Fetch all patients initially
$sql = "SELECT id, name, phone FROM users WHERE role = 'patient'";

// If a search query is present, filter the results
if (!empty($search)) {
    $sql .= " AND (name LIKE CONCAT('%', ?, '%') OR phone LIKE CONCAT('%', ?, '%'))";
}

$stmt = $conn->prepare($sql);

if (!empty($search) && $stmt) {
    $stmt->bind_param("ss", $search, $search);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    echo "<p class='text-danger'>Error fetching patients: " . $conn->error . "</p>";
}

// Handle message submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("iis", $doctor_id, $receiver_id, $message);
            if ($stmt->execute()) {
                // Redirect to refresh the page and show the new message
                header("Location: ?patient_id=" . $receiver_id);
                exit();
            } else {
                echo "<script>console.log('Error sending message: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>console.log('Database prepare error: " . $conn->error . "');</script>";
        }
    }
}

// Fetch messages between doctor and patient
$patient_name = "";
$messages = null; // Initialize variable

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];

    // Get patient name
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $stmt->bind_result($patient_name);
        $stmt->fetch();
        $stmt->close();
    }

    // Retrieve messages
    $stmt = $conn->prepare("SELECT sender_id, message, created_at FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
    if ($stmt) {
        $stmt->bind_param("iiii", $doctor_id, $patient_id, $patient_id, $doctor_id);
        $stmt->execute();
        $messages = $stmt->get_result();
        $stmt->close();
    } else {
        echo "<p class='text-danger'>Error fetching messages: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | ECG Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/doctorstyle.css">
</head>

<body>
    <nav class="navbar navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <img src="https://cdn-icons-png.flaticon.com/512/2785/2785544.png" alt="ECG Logo" class="logo-img">
                ECG Monitoring Dashboard
            </a>
            <!-- Added logout button -->
            <button class="btn btn-outline-light" id="logoutButton" onclick="window.location.href='logout.php';">
                <i class="bi bi-box-arrow-right"></i> Log Out
            </button>

        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid p-4">
        <h2>Welcome, Dr. <?= htmlspecialchars($doctor_name) ?></h2>

        <!-- Search Patient -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h5>Search for a Patient</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search by name or phone"
                        value="<?= htmlspecialchars($search) ?>" required>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>

        <!-- Patient Search Results -->
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h5>Search Results</h5>
            </div>
            <div class="card-body">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Monitor</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($patient = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $patient['id'] ?></td>
                                    <td><?= htmlspecialchars($patient['name']) ?></td>
                                    <td><?= htmlspecialchars($patient['phone']) ?></td>
                                    <td><a href="monitor_patient.php?id=<?= $patient['id'] ?>"
                                            class="btn btn-success btn-sm">Monitor</a></td>
                                    <td><a href="?patient_id=<?= $patient['id'] ?>" class="btn btn-info btn-sm">Message</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No patients found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Message Panel -->
        <?php if (isset($_GET['patient_id'])): ?>
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5>Chat with <?= htmlspecialchars($patient_name) ?></h5>
                </div>
                <div class="card-body" id="messages-container" style="max-height: 300px; overflow-y: scroll;">
                    <?php
                    if ($messages && $messages->num_rows > 0) {
                        while ($message = $messages->fetch_assoc()) {
                            $isSender = $message['sender_id'] == $doctor_id;
                            $messageClass = $isSender ? 'text-end' : 'text-start';
                            $nameDisplay = $isSender ? 'Dr. ' . htmlspecialchars($doctor_name) : htmlspecialchars($patient_name);
                            
                            echo '<div class="message ' . $messageClass . ' mb-3">';
                            echo '<p class="mb-1"><strong>' . $nameDisplay . ':</strong> ' . htmlspecialchars($message['message']) . '</p>';
                            echo '<small class="text-muted">' . $message['created_at'] . '</small>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No messages yet. Start the conversation!</p>';
                    }
                    ?>
                </div>

                <!-- Send Message -->
                <div class="card-footer">
                    <form method="POST">
                        <input type="hidden" name="receiver_id" value="<?= $_GET['patient_id'] ?>">
                        <textarea name="message" class="form-control" rows="3" required></textarea>
                        <button type="submit" name="send_message" class="btn btn-primary mt-2">Send Message</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-scroll to the bottom of the messages container
        window.onload = function() {
            var messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        };
    </script>
</body>

</html>