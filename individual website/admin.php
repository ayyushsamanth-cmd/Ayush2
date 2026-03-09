<?php
session_start();

/* ===== DATABASE CONNECTION ===== */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "mario_game";
$table = "manage";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure required admin columns exist
$hasRole = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'role'");
if ($hasRole && $hasRole->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password");
}

$hasHighScore = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'high_score'");
if ($hasHighScore && $hasHighScore->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN high_score INT NOT NULL DEFAULT 0 AFTER role");
}

// Clean duplicate usernames (keep newest row), then enforce unique username
$conn->query("DELETE m1 FROM `$table` m1
              INNER JOIN `$table` m2
                ON LOWER(m1.username) = LOWER(m2.username)
               AND m1.id < m2.id");
$hasUsernameUnique = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = 'uq_manage_username'");
if ($hasUsernameUnique && $hasUsernameUnique->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD UNIQUE KEY uq_manage_username (username)");
}

/* ===== SIMPLE ADMIN CHECK ===== */
/* If you already store role in session, use that instead */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access Denied - Admins Only");
}

function resequenceIds(mysqli $conn, string $table): void {
    // Repack IDs to 1..N so deleted gaps are removed.
    $conn->query("SET @new_id := 0");
    $conn->query("UPDATE `$table` SET id = (@new_id := @new_id + 1) ORDER BY id");
    $conn->query("ALTER TABLE `$table` AUTO_INCREMENT = 1");
}

/* ===== DELETE USER ===== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM `$table` WHERE id = $id AND role != 'admin'");
    resequenceIds($conn, $table);
    header("Location: admin.php");
    exit();
}

/* ===== RESET USER SCORE ===== */
if (isset($_GET['reset'])) {
    $id = intval($_GET['reset']);
    $conn->query("UPDATE `$table` SET high_score = 0 WHERE id = $id");
    header("Location: admin.php");
    exit();
}

/* ===== FETCH ALL USERS ===== */
$result = $conn->query("SELECT * FROM `$table` ORDER BY high_score DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Duck Game</title>
    <style>
        body {
            font-family: Arial;
            background: #111;
            color: white;
            padding: 30px;
        }

        h1 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #222;
        }

        th, td {
            padding: 12px;
            border: 1px solid #444;
            text-align: center;
        }

        th {
            background: #333;
        }

        a {
            padding: 6px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            color: white;
        }

        .delete-btn {
            background: red;
        }

        .reset-btn {
            background: orange;
        }

        .top-bar {
            margin-bottom: 20px;
        }

        .home-btn {
            background: green;
        }

        .stats-box {
            margin-bottom: 20px;
            padding: 10px;
            background: #1c1c1c;
        }
    </style>
</head>
<body>

<h1>Admin Dashboard</h1>

<div class="stats-box">
<?php
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM `$table` WHERE role='user'")->fetch_assoc()['total'];
echo "Total Players: " . $totalUsers;
?>
</div>

<div class="top-bar">
    <a href="home.php" class="home-btn">Go to Home</a>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Role</th>
        <th>High Score</th>
        <th>Registered On</th>
        <th>Actions</th>
    </tr>

    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= $row['role'] ?></td>
        <td><?= $row['high_score'] ?></td>
        <td><?= $row['created_at'] ?></td>
        <td>
            <?php if($row['role'] != 'admin'): ?>
                <a class="reset-btn" href="?reset=<?= $row['id'] ?>">Reset Score</a>
                <a class="delete-btn" 
                   href="?delete=<?= $row['id'] ?>" 
                   onclick="return confirm('Delete this user?')">
                   Delete
                </a>
            <?php else: ?>
                —
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
