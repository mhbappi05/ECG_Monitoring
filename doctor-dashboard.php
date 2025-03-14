<?php
session_start();
include 'db.php';

// Check if the user is logged in and is a doctor
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.html");
    exit();
}

// Fetch the doctor's name from the database
$doctor_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stmt->bind_result($doctor_name);
$stmt->fetch();
$stmt->close();

// Handle search query
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT id, name, phone FROM users WHERE role = 'patient' AND (name LIKE ? OR phone LIKE ?)";
$stmt = $conn->prepare($sql);
$searchTerm = "%$search%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

// Handle message submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $doctor_id, $receiver_id, $message);
    $stmt->execute();
    $stmt->close();
}

// Fetch messages between doctor and patient
if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    $stmt = $conn->prepare("SELECT sender_id, message, created_at FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
    $stmt->bind_param("iiii", $doctor_id, $patient_id, $patient_id, $doctor_id);
    $stmt->execute();
    $messages = $stmt->get_result();
    $stmt->close();
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
                <?php if ($result->num_rows > 0): ?>
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
                                    <td><?= $patient['name'] ?></td>
                                    <td><?= $patient['phone'] ?></td>
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
        <?php if (isset($patient_id)): ?>
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5>Chat with <?= htmlspecialchars($patient_id) ?></h5>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: scroll;">
                    <?php while ($message = $messages->fetch_assoc()): ?>
                        <div class="message <?= $message['sender_id'] == $doctor_id ? 'text-end' : 'text-start' ?>">
                            <p><strong><?= $message['sender_id'] == $doctor_id ? 'Dr. ' . htmlspecialchars($doctor_name) : 'Patient' ?>:</strong>
                                <?= htmlspecialchars($message['message']) ?></p>
                            <small class="text-muted"><?= $message['created_at'] ?></small>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Send Message -->
                <div class="card-footer">
                    <form method="POST">
                        <input type="hidden" name="receiver_id" value="<?= $patient_id ?>">
                        <textarea name="message" class="form-control" rows="3" required></textarea>
                        <button type="submit" name="send_message" class="btn btn-primary mt-2">Send Message</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script>
        document.getElementById("logoutButton").addEventListener("click", function () {
            window.location.href = "login.html";
        });
    </script>
</body>

</html>