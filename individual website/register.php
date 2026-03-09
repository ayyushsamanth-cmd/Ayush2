<?php
session_start();

// ---- DATABASE CONFIG ----
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'mario_game';
$table = 'manage';

// Connect without specifying database first
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Create manage table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS `$table` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    high_score INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (strlen($username) < 3 || strlen($password) < 4) {
        $error = 'Username must be 3+ characters, password 4+ characters';
    } elseif (strtolower($username) === 'admin') {
        $error = 'This username is reserved';
    } else {
        // Check if user already exists
        $stmt = $conn->prepare("SELECT id FROM `$table` WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Hash password and insert into database
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO `$table` (username, password) VALUES (?, ?)");
            $insert->bind_param('ss', $username, $hashed);
            
            if ($insert->execute()) {
                // Registration successful - redirect to login page
                header('Location: login.php');
                exit;
            } else {
                $error = 'Registration failed: ' . $insert->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<style>
    body {
        margin: 0;
        height: 100vh;
        background: url('assets/Summer8.png') no-repeat center center;
        background-size: cover;
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }
    .box {
        box-sizing: border-box;
        background: #fff;
        padding: 25px 30px;
        width: 320px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
    }
    h2 {
        margin: 0 0 20px 0;
        color: #e67e22;
    }
    input {
        width: 100%;
        padding: 10px;
        margin-bottom: 12px;
        border-radius: 5px;
        border: 2px solid #333;
        font-size: 14px;
        box-sizing: border-box;
    }
    button {
        width: 100%;
        padding: 10px;
        background: #e67e22;
        border: none;
        color: #fff;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        box-shadow: 0 4px #a84300;
    }
    button:hover {
        background: #f39c12;
    }
    button:active {
        transform: translateY(2px);
        box-shadow: 0 2px #a84300;
    }
    .error { 
        color: #c0392b; 
        margin-bottom: 10px;
        font-size: 14px;
    }
    .success { 
        color: #27ae60; 
        margin-bottom: 10px;
        font-size: 14px;
    }
    a {
        color: #3498db;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    .auth-link {
        margin-top: 15px;
        font-size: 12px;
        font-family: 'Trebuchet MS', Arial, sans-serif;
    }
    .auth-link a {
        color: #3498db;
        text-decoration: none;
        font-weight: bold;
    }
    .auth-link a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="box">
    <h2>Register</h2>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>

    <p class="auth-link">
        Already have an account? <a href="login.php">Login</a>
    </p>
</div>

</body>
</html>

