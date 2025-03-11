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
    <script>
        const ecgMotherCtx = document.getElementById('ecgMotherChart').getContext('2d');
        const ecgFetalCtx = document.getElementById('ecgFetalChart').getContext('2d');

        const chartOptions = {
            animation: false,
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 8,
                        color: '#666',
                        font: {
                            size: 10
                        }
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 5,
                        color: '#666',
                        font: {
                            size: 10
                        }
                    },
                    min: -0.5,
                    max: 2.5
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            },
            elements: {
                line: {
                    tension: 0.4,
                    borderWidth: 1.5,
                    fill: false
                },
                point: {
                    radius: 0
                }
            }
        };

        const motherChartGradient = ecgMotherCtx.createLinearGradient(0, 0, 0, 200);
        motherChartGradient.addColorStop(0, '#e74c3c');
        motherChartGradient.addColorStop(1, '#e74c3c80');

        const fetalChartGradient = ecgFetalCtx.createLinearGradient(0, 0, 0, 200);
        fetalChartGradient.addColorStop(0, '#3498db');
        fetalChartGradient.addColorStop(1, '#3498db80');

        const ecgMotherChart = new Chart(ecgMotherCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Mother ECG',
                    borderColor: '#e74c3c',
                    backgroundColor: motherChartGradient,
                    borderWidth: 1.5,
                    pointRadius: 0,
                    data: [],
                    fill: 'start',
                    tension: 0.4
                }]
            },
            options: chartOptions
        });

        const ecgFetalChart = new Chart(ecgFetalCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Fetal ECG',
                    borderColor: '#3498db',
                    backgroundColor: fetalChartGradient,
                    borderWidth: 1.5,
                    pointRadius: 0,
                    data: [],
                    fill: 'start',
                    tension: 0.4
                }]
            },
            options: chartOptions
        });
        function updateHealthSuggestions() {
            const motherECG = parseInt(document.getElementById('mother_ecg_stats').innerText.replace(/\D/g, ''), 10);
            const fetalECG = parseInt(document.getElementById('fetal_ecg_stats').innerText.replace(/\D/g, ''), 10);
            const motherTemp = parseFloat(document.getElementById('temperature_mother').innerText);
            const fetalTemp = parseFloat(document.getElementById('temperature_fetal').innerText);
            const oxygenMother = parseFloat(document.getElementById('oxygen_mother').innerText);

            let suggestions = [];

            // ECG Analysis (Mother)
            if (motherECG < 60) {
                suggestions.push("Mother's heart rate is low. Consider checking for dizziness or fatigue.");
            } else if (motherECG > 100) {
                suggestions.push("Mother's heart rate is high. Rest and hydration are recommended.");
            }

            // ECG Analysis (Fetal)
            if (fetalECG < 110) {
                suggestions.push("Fetal heart rate is low. Monitor closely and consider consulting a doctor.");
            } else if (fetalECG > 160) {
                suggestions.push("Fetal heart rate is high. Ensure the mother is well-hydrated and resting.");
            }

            // Temperature Analysis
            if (motherTemp > 37.5) {
                suggestions.push("Mother's temperature is slightly high. Check for fever and stay hydrated.");
            }
            if (fetalTemp > 38) {
                suggestions.push("Fetal temperature is high. Immediate medical attention may be needed.");
            }

            // Oxygen Level Analysis
            if (oxygenMother < 95) {
                suggestions.push("Mother's oxygen level is low. Consider deep breathing exercises or using supplemental oxygen if necessary.");
            }

            // Display suggestions
            document.getElementById('health_suggestions').innerHTML = suggestions.length > 0
                ? suggestions.join("<br>")
                : "Vitals are stable. No immediate action required.";
        }

        function updateData() {
            const dummyECG = () => Math.random() * 2;
            const currentTime = new Date().toLocaleTimeString();

            // Update mother ECG
            ecgMotherChart.data.labels.push(currentTime);
            const motherValue = dummyECG();
            ecgMotherChart.data.datasets[0].data.push(motherValue);

            // Update fetal ECG
            ecgFetalChart.data.labels.push(currentTime);
            const fetalValue = dummyECG();
            ecgFetalChart.data.datasets[0].data.push(fetalValue);

            // Keep last 50 data points
            if (ecgMotherChart.data.labels.length > 50) {
                ecgMotherChart.data.labels.shift();
                ecgMotherChart.data.datasets[0].data.shift();
                ecgFetalChart.data.labels.shift();
                ecgFetalChart.data.datasets[0].data.shift();
            }

            // Update stats
            document.getElementById('mother_ecg_stats').innerText = `Rate: ${Math.floor(Math.random() * 20 + 70)} bpm`;
            document.getElementById('fetal_ecg_stats').innerText = `Rate: ${Math.floor(Math.random() * 40 + 120)} bpm`;

            // Update metrics
            document.getElementById('heart_rate_fetal').innerText = Math.floor(Math.random() * 40 + 120) + ' bpm';
            document.getElementById('temperature_mother').innerText = (36 + Math.random()).toFixed(1) + ' °C';
            document.getElementById('temperature_fetal').innerText = (37 + Math.random()).toFixed(1) + ' °C';
            document.getElementById('oxygen_mother').innerText = (96 + Math.random() * 4).toFixed(1) + '%';

            ecgMotherChart.update();
            ecgFetalChart.update();
            updateLastUpdate();
            updateHealthSuggestions();
        }

        function updateLastUpdate() {
            const now = new Date();
            document.getElementById('last-update').textContent =
                now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        setInterval(updateData, 2000);

        // Log out button functionality
        document.getElementById('logoutButton').addEventListener('click', function () {
            // Redirect to login page or perform logout logic
            window.location.href = "login.html";  // Example redirect to login page
        });
    </script>
</body>

</html>