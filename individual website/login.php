<?php
// login.php
session_start();

// ---- DATABASE CONFIG ----
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'mario_game';
$table = 'manage';

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Ensure manage table exists (admin dashboard source)
$conn->query("CREATE TABLE IF NOT EXISTS `$table` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    high_score INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure required columns exist on existing manage table
$hasPassword = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'password'");
if ($hasPassword && $hasPassword->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN password VARCHAR(255) NULL");
}

$hasRole = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'role'");
if ($hasRole && $hasRole->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
}

$hasHighScore = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'high_score'");
if ($hasHighScore && $hasHighScore->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN high_score INT NOT NULL DEFAULT 0");
}

$hasCreatedAt = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'created_at'");
if ($hasCreatedAt && $hasCreatedAt->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

// Clean existing duplicate usernames (keep newest row), then enforce uniqueness
$conn->query("DELETE m1 FROM `$table` m1
              INNER JOIN `$table` m2
                ON LOWER(m1.username) = LOWER(m2.username)
               AND m1.id < m2.id");
$hasUsernameUnique = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = 'uq_manage_username'");
if ($hasUsernameUnique && $hasUsernameUnique->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD UNIQUE KEY uq_manage_username (username)");
}

// One-time migration from legacy users table if it exists
$hasUsers = $conn->query("SHOW TABLES LIKE 'users'");
if ($hasUsers && $hasUsers->num_rows > 0) {
    $manageCountRes = $conn->query("SELECT COUNT(*) AS c FROM `$table`");
    $manageCount = 0;
    if ($manageCountRes) {
        $manageCountRow = $manageCountRes->fetch_assoc();
        $manageCount = (int)($manageCountRow['c'] ?? 0);
    }
    if ($manageCount === 0) {
    $conn->query("INSERT IGNORE INTO `$table` (username, password)
                  SELECT username, password FROM users");
    }
}
// Admin is system-only, not a player row in manage
$conn->query("DELETE FROM `$table` WHERE username = 'admin'");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username and password required';
    } else {
        // System admin login (not stored as player)
        if (strtolower($username) === 'admin' && $password === '6789') {
            $_SESSION['user'] = 'admin';
            $_SESSION['role'] = 'admin';
            header('Location: admin.php');
            exit;
        }

        $stmt = $conn->prepare("SELECT password, role FROM `$table` WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashed_password, $role);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user'] = $username;
                $_SESSION['role'] = 'user';

                // Keep one row per player; refresh login time for returning players.
                $touch = $conn->prepare("UPDATE `$table` SET created_at = NOW() WHERE username = ? LIMIT 1");
                $touch->bind_param('s', $username);
                $touch->execute();
                $touch->close();

                header('Location: home.php');
                exit;
            }
        }
        $stmt->close();

        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Mario – Login</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Trebuchet MS', Arial, sans-serif;
            background: url('assets/loginbg.png') no-repeat center center;
            background-size: 100% 100%;
            height: 100vh;
            overflow: hidden;
        }

        /* Login Box */
        .login-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255,255,255,0.95);
            padding: 25px 30px;
            width: 320px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            color: #e74c3c;
            text-shadow: 2px 2px #000;
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 5px;
            border: 2px solid #333;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px #b03a2e;
        }

        button:active {
            transform: translateY(2px);
            box-shadow: 0 2px #b03a2e;
        }

        .error {
            color: #c0392b;
            font-size: 14px;
            margin-bottom: 10px;
        }

    </style>
</head>
<body>

    <div class="cloud one"></div>
    <div class="cloud two"></div>

    <div class="pipe left"></div>
    <div class="pipe right"></div>

    <div class="login-box">
        <div class="title">LOGIN</div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Continue</button>
        </form>

        <p style="font-size:12px; margin-top:15px;">
            New user? <a href="register.php" style="color: #3da1c0; text-decoration: none; font-weight: bold;">Register here</a>
        </p>
    </div>

    <div class="ground"></div>

</body>
</html>


