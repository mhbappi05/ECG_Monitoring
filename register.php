<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, phone, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $password);

    if ($stmt->execute()) {
        echo "Registration successful. <a href='index.html'>Login here</a>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>
