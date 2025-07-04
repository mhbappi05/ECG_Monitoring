<?php
session_start();
include 'db.php';

// Check if the user is logged in and is a doctor
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: index.html");
    exit();
}

// Validate patient_id exists and is numeric
$patient_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$patient_id) {
    die("Invalid patient ID. Please provide a valid patient ID.");
}

// Fetch patient data
$sql = "SELECT * FROM users WHERE id = ? AND role = 'patient'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if patient exists
if ($result->num_rows === 0) {
    die("Patient not found. Please check the patient ID.");
}

$patient = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Patient | ECG Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/monitorstyle.css">
    <link rel="stylesheet" href="css/doctorstyle.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="doctor-dashboard.php">
                <img src="https://cdn-icons-png.flaticon.com/512/2785/2785544.png" alt="ECG Logo"
                    class="logo-img"></i>ECG Monitoring Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="doctor-dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Patient Monitoring</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Records</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <button class="btn btn-outline-light" id="logoutButton"
                        onclick="window.location.href='logout.php';">
                        <i class="bi bi-box-arrow-right"></i> Log Out
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <?php if (isset($patient) && is_array($patient)): ?>
            <div class="patient-header">
                <div>
                    <h2><i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($patient['name']) ?></h2>
                    <div class="d-flex align-items-center mt-2">
                        <div><i class="fas fa-phone me-2"></i><?= htmlspecialchars($patient['phone']) ?></div>
                        <div class="ms-4"><i class="fas fa-calendar-alt me-2"></i>Patient ID:
                            <?= htmlspecialchars($patient_id) ?>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="badge bg-success p-2">Online</span>
                    <div class="timestamp mt-1">Last updated: <span id="lastUpdated">March 14, 2025 14:32</span></div>
                </div>
            </div>

            <!-- Real-time vital stats -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card vital-card">
                        <div class="card-body">
                            <div class="vital-icon text-primary">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h1 class="vital-value">72 <small>bpm</small></h1>
                            <p class="vital-label">HEART RATE</p>
                            <span class="alert-indicator normal"></span> Normal
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card vital-card">
                        <div class="card-body">
                            <div class="vital-icon text-info">
                                <i class="fas fa-lungs"></i>
                            </div>
                            <h1 class="vital-value">98<small>%</small></h1>
                            <p class="vital-label">OXYGEN SATURATION</p>
                            <span class="alert-indicator normal"></span> Normal
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card vital-card">
                        <div class="card-body">
                            <div class="vital-icon text-warning">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <h1 class="vital-value">120/80</h1>
                            <p class="vital-label">BLOOD PRESSURE</p>
                            <span class="alert-indicator normal"></span> Normal
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card vital-card">
                        <div class="card-body">
                            <div class="vital-icon text-secondary">
                                <i class="fas fa-thermometer-half"></i>
                            </div>
                            <h1 class="vital-value">98.6<small>°F</small></h1>
                            <p class="vital-label">TEMPERATURE</p>
                            <span class="alert-indicator normal"></span> Normal
                        </div>
                    </div>
                </div>
            </div>

            <!-- ECG Graph -->
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>ECG Monitoring</h5>
                    <div>
                        <button class="btn btn-sm btn-light me-2">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-light active">Real-time</button>
                            <button class="btn btn-light">6h</button>
                            <button class="btn btn-light">24h</button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="ecg-container">
                        <canvas id="ecgChart" class="img-fluid"></canvas>
                    </div>
                </div>
            </div>



            <!-- Additional patient data -->
            <div class="row">
                <!-- Latest readings -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Latest Readings</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Heart Rate</th>
                                        <th>O2 Sat</th>
                                        <th>BP</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>14:30</td>
                                        <td>72 bpm</td>
                                        <td>98%</td>
                                        <td>120/80</td>
                                        <td><span class="badge bg-success">Normal</span></td>
                                    </tr>
                                    <tr>
                                        <td>14:00</td>
                                        <td>75 bpm</td>
                                        <td>97%</td>
                                        <td>122/82</td>
                                        <td><span class="badge bg-success">Normal</span></td>
                                    </tr>
                                    <tr>
                                        <td>13:30</td>
                                        <td>78 bpm</td>
                                        <td>96%</td>
                                        <td>126/84</td>
                                        <td><span class="badge bg-success">Normal</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Patient Notes -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Patient Notes</h5>
                        </div>
                        <div class="card-body">
                            <div class="history-item">
                                <div class="d-flex justify-content-between">
                                    <strong>Medication Update</strong>
                                    <small>Today, 09:15</small>
                                </div>
                                <p class="mb-0">Patient reports taking all prescribed medications as directed. No adverse
                                    effects noted.</p>
                            </div>
                            <div class="history-item">
                                <div class="d-flex justify-content-between">
                                    <strong>Activity Level</strong>
                                    <small>Yesterday, 16:30</small>
                                </div>
                                <p class="mb-0">Patient completed 20 minutes of moderate exercise. Vitals remained stable
                                    throughout.</p>
                            </div>
                            <div class="history-item">
                                <div class="d-flex justify-content-between">
                                    <strong>Diet Notes</strong>
                                    <small>Mar 12, 2025</small>
                                </div>
                                <p class="mb-0">Patient adhering to low-sodium diet as recommended.</p>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i> Add Note
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="quick-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contactPatientModal">
                    <i class="fas fa-phone me-1"></i> Contact Patient
                </button>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#prescribeMedicationModal">
                    <i class="fas fa-prescription me-1"></i> Prescribe Medication
                </button>
                <button class="btn btn-outline-primary">
                    <i class="fas fa-calendar-plus me-1"></i> Schedule Visit
                </button>
                <button class="btn btn-outline-primary">
                    <i class="fas fa-file-medical me-1"></i> View Full Medical Record
                </button>
                <a href="doctor-dashboard.php" class="btn btn-outline-secondary float-end">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            <!-- Contact Patient Modal -->
            <div class="modal fade" id="contactPatientModal" tabindex="-1" aria-labelledby="contactPatientModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="contactPatientModalLabel">Contact Patient</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Name:</strong> <?= htmlspecialchars($patient['name']) ?></p>
                            <p><strong>Phone Number:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
                            <div class="mb-3">
                                <label for="messageText" class="form-label">Message</label>
                                <textarea class="form-control" id="messageText" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="sendMessageButton">Send Message</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prescribe Medication Modal -->
            <div class="modal fade" id="prescribeMedicationModal" tabindex="-1"
                aria-labelledby="prescribeMedicationModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="prescribeMedicationModalLabel">Prescribe Medication</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Name:</strong> <?= htmlspecialchars($patient['name']) ?></p>
                            <p><strong>Phone Number:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
                            <div class="mb-3">
                                <label for="medicationText" class="form-label">Medication</label>
                                <textarea class="form-control" id="medicationText" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="extraNoteText" class="form-label">Extra Note</label>
                                <textarea class="form-control" id="extraNoteText" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="sendMedicationButton">Send Medication</button>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-danger mt-4">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Error</h4>
                <p>Unable to load patient data. Please check the patient ID and try again.</p>
                <a href="doctor-dashboard.php" class="btn btn-outline-secondary mt-3">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Update the last updated time every minute
        function updateTime() {
            const now = new Date();
            document.getElementById('lastUpdated').textContent =
                now.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                }) + ' ' +
                now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
        }

        setInterval(updateTime, 60000);
        updateTime();

        document.getElementById("logoutButton").addEventListener("click", function () {
            window.location.href = "login.html";
        });
        // Send message to patient
        document.getElementById("sendMessageButton").addEventListener("click", function () {
            const messageText = document.getElementById("messageText").value;
            const patientId = <?= $patient_id ?>;

            $.ajax({
                url: 'send_message_Contact_patient.php',
                type: 'POST',
                data: {
                    patient_id: patientId,
                    message: messageText
                },
                success: function (response) {
                    alert('Message sent successfully!');
                    $('#contactPatientModal').modal('hide');
                },
                error: function () {
                    alert('Failed to send message. Please try again.');
                }
            });
        });
        // Send medication to patient
        document.getElementById("sendMedicationButton").addEventListener("click", function () {
            const medicationText = document.getElementById("medicationText").value;
            const extraNoteText = document.getElementById("extraNoteText").value;
            const patientId = <?= $patient_id ?>;

            const message = `**Medication Prescribed**\n\n**Medication:** ${medicationText}\n**Note:** ${extraNoteText}`;

            $.ajax({
                url: 'send_message_pescribe_medi.php',
                type: 'POST',
                data: {
                    patient_id: patientId,
                    message: message
                },
                success: function (response) {
                    alert('Medication prescribed successfully!');
                    $('#prescribeMedicationModal').modal('hide');
                },
                error: function () {
                    alert('Failed to prescribe medication. Please try again.');
                }
            });
        });

        // Dummy ECG Data (This would be replaced with real data in a real implementation)
        let ecgData = {
            labels: ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], // Time or index labels
            datasets: [{
                label: 'ECG Data',
                data: [0, 10, 5, 2, 20, 30, 45, 40, 60, 90], // Example ECG data points
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                fill: false
            }]
        };

        // Render the ECG chart
        const ctx = document.getElementById('ecgChart').getContext('2d');
        const ecgChart = new Chart(ctx, {
            type: 'line', // Line chart for ECG
            data: ecgData,
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom'
                    },
                    y: {
                        min: -10,
                        max: 100
                    }
                },
                animation: {
                    duration: 0, // Instant update, for real-time data
                }
            }
        });

        // Function to update the ECG chart with new data
        function updateECGGraph(newData) {
            ecgChart.data.datasets[0].data.push(newData);
            ecgChart.data.labels.push(ecgChart.data.labels.length); // Update labels (time)
            if (ecgChart.data.datasets[0].data.length > 10) {
                ecgChart.data.datasets[0].data.shift(); // Remove the first data point if it's too long
                ecgChart.data.labels.shift(); // Remove corresponding label
            }
            ecgChart.update();
        }

        // Simulate adding new ECG data every 2 seconds
        setInterval(() => {
            let randomData = Math.floor(Math.random() * 100); // Simulate new data
            updateECGGraph(randomData);
        }, 2000);

    </script>
</body>

</html>