<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();
        
        if (password_verify($password, $hashed_password)) {
            $_SESSION['id'] = $id;
            header("Location: ecg.php");
            exit();
        } else {
            echo "Invalid credentials. <a href='index.html'>Try again</a>";
        }
    } else {
        echo "No user found with this phone number.";
    }

    $stmt->close();
    $conn->close();
}
?>
