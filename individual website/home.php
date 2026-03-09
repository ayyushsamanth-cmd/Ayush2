<?php
session_start();

// Protect page: only logged-in users
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Load character configuration
include 'assets/character.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Start Game</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<style>
    @font-face {
        font-family: 'DuckDashFont';
        src: url('assets/font.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        height: 100vh;
        overflow: hidden;
        font-family: 'Trebuchet MS', Arial, sans-serif;
        background: url('assets/origbig.png') no-repeat center center;
        background-size: cover;
    }
    /* Ground */
    .ground {
        position: absolute;
        bottom: 0;
        width: 100%;
        height: 120px;
        background: #8e5a2b;
        border-top: 8px solid #5d3b1a;
    }

    /* Start container */
    .start-container {
        position: absolute;
        top: 42%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: min(92vw, 1100px);
        text-align: center;
        z-index: 10;
    }

    .game-title {
        margin: 0 0 18px;
        font-family: 'DuckDashFont', 'Press Start 2P', cursive;
        font-weight: 900;
        font-size: clamp(56px, 10vw, 130px);
        line-height: 0.95;
        letter-spacing: -2px;

    /* Main bright orange fill */
    color: #FF7A1A;

    /* Light golden outer border */
    -webkit-text-stroke: 8px #FFB347;

    /* Layered 3D effect */
    text-shadow:
        0 6px 0 #E05A00,   /* darker orange depth */
        0 14px 0 #000,    /* thick black arcade shadow */
        0 18px 25px rgba(0, 0, 0, 0.4); /* soft blur shadow */
}


    .game-title span {
        display: block;
    }

    .game-title .top-line {
        white-space: nowrap;
    }

    .menu-actions {
        margin-top: 56px;
    }

    .title {
        display: inline-block;
        margin-top: 8px;
        font-size: 56px;
        font-family: 'Press Start 2P', cursive;
        word-spacing: -14px;
        color: #f1c40f;
        text-shadow: 4px 4px #000;
        text-decoration: none;
        cursor: pointer;
    }

    .title:hover {
        color: #ffd84d;
    }

    .logout-link {
        display: inline-block;
        margin-top: 14px;
        font-size: 24px;
        font-family: 'Press Start 2P', cursive;
        color: #f1c40f;
        text-shadow: 4px 4px #000;
        text-decoration: none;
        cursor: pointer;
    }

    .logout-link:hover {
        color: #ffd84d;
    }

    /* Character */
    .character {
        position: absolute;
        bottom: 120px;
        left: 120px;
        width: 120px;
        height: 120px;
        background-size: contain;
        background-repeat: no-repeat;
        background-position: bottom center;
    }
</style>
</head>
<body>

<div class="start-container">
    <h1 class="game-title"><span class="top-line">DUCK RUN</span></h1>
    <div class="menu-actions">
        <a class="title" href="game.php">START GAME</a>
        <br>
        <a class="logout-link" href="logout.php">LOG OUT</a>
    </div>
</div>

<div class="character" style="background-image: url('assets/<?php echo $character_image ?>.png'); background-size: contain; background-repeat: no-repeat;"></div>
<div class="ground"></div>

</body>
</html>
