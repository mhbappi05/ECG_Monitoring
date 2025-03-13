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

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch user data
$user_id = $_SESSION['id'];
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$username = $user ? $user['name'] : "Guest";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECG Monitoring Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chatbot.css"> <!-- Chatbot CSS -->
</head>

<body>
    <nav class="navbar navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <img src="https://cdn-icons-png.flaticon.com/512/2785/2785544.png" alt="ECG Logo" class="logo-img">
                ECG Monitoring Dashboard
            </a>
            <!-- Added logout button -->
            <button class="btn btn-outline-light" id="logoutButton">
                <i class="bi bi-box-arrow-right"></i> Log Out
            </button>
        </div>
    </nav>
    <div class="container">
        

        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="dashboard-title">Patient Monitoring</h1>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">Last updated: <span id="last-update">Just now</span></span>
                </div>
            </div>
        </div>

        <div class="patient-info">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135789.png" alt="Patient Avatar"
                class="patient-avatar">
            <div class="patient-details">
                <h4>Welcome, <?php echo htmlspecialchars($username); ?>!</h4>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card chart-container">
                    <div class="chart-header">
                        <h5 class="chart-title">Mother ECG</h5>
                        <div class="chart-stats">
                            <span id="mother_ecg_stats">Rate: -- bpm</span>
                        </div>
                    </div>
                    <div class="ecg-chart">
                        <canvas id="ecgMotherChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card chart-container">
                    <div class="chart-header">
                        <h5 class="chart-title">Fetal ECG</h5>
                        <div class="chart-stats">
                            <span id="fetal_ecg_stats">Rate: -- bpm</span>
                        </div>
                    </div>
                    <div class="ecg-chart">
                        <canvas id="ecgFetalChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="metric-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <h5>Fetal Heart Rate</h5>
                    <p id="heart_rate_fetal" class="metric-value">-- bpm</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="metric-icon">
                        <i class="bi bi-thermometer-half"></i>
                    </div>
                    <h5>Mother Temperature</h5>
                    <p id="temperature_mother" class="metric-value">-- °C</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="metric-icon">
                        <i class="bi bi-thermometer"></i>
                    </div>
                    <h5>Fetal Temperature</h5>
                    <p id="temperature_fetal" class="metric-value">-- °C</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="metric-icon">
                        <i class="bi bi-lungs"></i>
                    </div>
                    <h5>Mother Oxygen</h5>
                    <p id="oxygen_mother" class="metric-value">--%</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card health-card">
                <h5 class="health-title"><i class="bi bi-activity"></i> Health Suggestions</h5>
                <div id="health_suggestions" class="suggestions-container">
                    <p class="suggestion-info">Monitoring vitals...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chatbot -->
    <div class="chatbot-container">
        <button class="chatbot-toggle">
            <i class="bi bi-chat-dots"></i>
        </button>
        <div class="chatbox">
            <div class="chatbox-header">
                <span>Health Assistant</span>
                <button class="close-chat">&times;</button>
            </div>
            <div class="chatbox-body" id="chatbox-body">
                <div class="bot-message">Hello! How can I assist you today?</div>
            </div>
            <div class="chatbox-footer">
                <input type="text" id="chat-input" placeholder="Ask about ECG, oxygen, etc.">
                <button id="send-btn"><i class="bi bi-send"></i></button>
            </div>
        </div>
    </div>


    <script src="js/chatbot.js"></script>
    <script src="js/ecg.js"></script>
</body>

</html>