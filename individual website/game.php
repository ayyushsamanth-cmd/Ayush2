<?php
// Simple game scene – no backend logic yet
?>
<?php
session_start();

// Get current user and their high score if available
$currentUser = $_SESSION['user'] ?? '';
$initialHighScore = 0;

// Only query the database if we have a logged-in user who isn't admin (admin doesn't have a DB record)
if ($currentUser !== '' && strtolower($currentUser) !== 'admin') {
    $conn = new mysqli('localhost', 'root', '', 'mario_game');
    if (!$conn->connect_error) {
        $table = 'manage';
        $stmt = $conn->prepare("SELECT high_score FROM `$table` WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $currentUser);
            $stmt->execute();
            $stmt->bind_result($dbHighScore);
            if ($stmt->fetch()) {
                $initialHighScore = (int)$dbHighScore;
            }
            $stmt->close();
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<title>Moving Ground Demo</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /*set a dark background color while the game assets load to avoid a white flash */
    body {
        overflow: hidden;
        background: #000;
    }

    /* Game container */
    #game {
        position: relative;
        width: 100vw;
        height: 100vh;
        overflow: hidden;
    }

    /* Background (STAYS STILL) */
    #background {
        position: absolute;
        width: 100%;
        height: 100%;
        background: url('assets/origbig.png') no-repeat center center;
        background-size: cover;
        z-index: 1;
    }

    /* Ground - match home page ground */
    #ground {
        position: absolute;
        bottom: 0;
        width: 100%;
        height: 120px;
        background: #8e5a2b;
        border-top: 8px solid #5d3b1a;
        z-index: 2;
    }

    /* Grass container (tiles are created via JS) */
    #grass {
        position: absolute;
        bottom: 0; /* container sits at bottom; tiles use bottom:120px to align with ground */
        left: 0;
        width: 100%;
        height: 20px; /* grass height */
        z-index: 3; /* behind player (player z-index:4) */
    }

    /* Individual grass tile defaults (created by JS) */
    .spike-tile {
        position: absolute;
        width: 40px;
        height: 40px;
        background: url('assets/Spike_Pixel.png') no-repeat center/contain;
        clip-path: inset(0 0 10px 0);
        pointer-events: none;
        z-index: 3;
    }

    /* Raven tiles (same as spike but different images and sizes, set by JS)*/
    .raven-tile {
        position: absolute;
        width: 130px;
        height: 78px;
        background: url('assets/raven.png') no-repeat bottom center/contain;
        pointer-events: none;
        z-index: 3;
    }

    /* low cloud tiles */
    .felpudo-tile {
        position: absolute;
        width: 250px;
        height: 200px;
        background: url('assets/cloud.png') no-repeat center/contain;
        pointer-events: none;
        z-index: 3;
    }

    /* Player character (stands on ground) */
    #player {
        position: absolute;
        bottom: 120px; /* same as ground height */
        left: 120px;
        width: 120px;
        height: 120px;
        background: url('assets/Mushroom-Run.png') no-repeat center;
        background-size: contain;
        z-index: 4;
        pointer-events: none;
    }

    /* Lives UI */
    #lives {
        position: absolute;
        top: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        z-index: 20;
    }

    #lives img {
        width: 64px;
    }

    #pause-btn {
        position: absolute;
        right: 20px;
        bottom: -20px;
        width: 120px;
        height: 120px;
        z-index: 25;
        display: inline-block;
        cursor: pointer;
        border: 0;
        background: transparent;
        padding: 0;
    }

    #pause-btn img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }

    #pausemenu {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 28;
        background: rgba(0,0,0,0.35);
        font-family: 'Press Start 2P', cursive;
        color: #fff;
        text-shadow: 0 4px 0 #000;
    }

    #pausemenu .pause-wrap {
        text-align: center;
    }

    #pausemenu .pause-action {
        display: block;
        margin-top: 18px;
        color: #f1c40f;
        text-decoration: none;
        font-size: 4vw;
        line-height: 1.2;
        cursor: pointer;
    }

    #pausemenu .pause-action:hover {
        color: #ffd84d;
    }

    #pausemenu .pause-countdown {
        display: none;
        margin-top: 18px;
        font-size: 7vw;
        line-height: 1;
        color: #f1c40f;
    }

    #pausemenu.counting {
        background: transparent;
        pointer-events: none;
    }

    /* Score UI */
    #scoreboard {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 20;
        font-family: 'Press Start 2P', cursive;
        font-size: 19px;
        line-height: 1.8;
        color: #000;
        text-shadow: none;
    }

    /* Game Over overlay */
    #gameover {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        font-family: 'Press Start 2P', cursive;
        color: #fff;
        text-shadow: 0 4px 0 #000;
        z-index: 30;
        background: rgba(0,0,0,0.35);
    }

    #gameover .go-wrap {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    #gameover .go-title {
        font-size: 10vw;
        line-height: 1;
    }

    #gameover .go-title span {
        display: block;
    }

    #gameover .go-retry {
        display: inline-block;
        margin-top: 18px;
        font-family: 'Press Start 2P', monospace;
        font-size: 4vw;
        padding: 14px 20px;
        background: #0b3d91;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
    }

    #gameover .go-home {
        display: inline-block;
        margin-top: 14px;
        font-family: 'Press Start 2P', monospace;
        font-size: 4vw;
        padding: 14px 20px;
        background: #0b3d91;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
    }
</style>
</head>
<body>

<div id="game">
    <div id="background"></div>
    <div id="ground"></div>
    <div id="grass"></div>
    <div id="player"></div>

    <div id="scoreboard">
        <div>HIGH SCORE: <span id="high-score">0</span></div>
        <div>SCORE: <span id="score">0</span></div>
    </div>

    <div id="lives">
        <img src="assets/heart.png">
        <img src="assets/heart.png">
        <img src="assets/heart.png">
    </div>
    <button id="pause-btn" aria-label="Pause game">
        <img src="assets/pausebutton.png" alt="Pause">
    </button>

    <div id="pausemenu">
        <div class="pause-wrap">
            <a id="continue-link" class="pause-action" href="#">CONTINUE</a>
            <a id="home-link" class="pause-action" href="home.php">HOME</a>
            <div id="pause-countdown" class="pause-countdown">3</div>
        </div>
    </div>

    <div id="gameover">
        <div class="go-wrap">
            <div class="go-title"><span>GAME</span><span>OVER</span></div>
            <a id="retry-link" class="go-retry" href="#">RETRY</a>
            <a id="go-home-link" class="go-home" href="home.php">HOME</a>
        </div>
    </div>
</div>

</body>
</html>
<script>
    // Spike tiles, jump, and collision (game over) JAVA SCRIPT
    (function(){
        const game = document.getElementById('game');
        const player = document.getElementById('player');
        const spikeContainer = document.getElementById('grass'); // reuse container
        const hearts = document.querySelectorAll('#lives img');
        const gameOverEl = document.getElementById('gameover');
        const retryLink = document.getElementById('retry-link');
        const goHomeLink = document.getElementById('go-home-link');
        const pauseBtn = document.getElementById('pause-btn');
        const pauseMenuEl = document.getElementById('pausemenu');
        const continueLink = document.getElementById('continue-link');
        const homeLink = document.getElementById('home-link');
        const pauseCountdownEl = document.getElementById('pause-countdown');
        const scoreEl = document.getElementById('score');
        const highScoreEl = document.getElementById('high-score');
        if (!game || !player || !spikeContainer || hearts.length === 0 || !gameOverEl || !scoreEl || !highScoreEl || !pauseBtn || !pauseMenuEl || !continueLink || !homeLink || !pauseCountdownEl || !goHomeLink) return;

        function syncGameOverButtonWidths(){
            const retryWidth = retryLink.getBoundingClientRect().width;
            if (retryWidth > 0) {
                goHomeLink.style.width = retryWidth + 'px';
                goHomeLink.style.textAlign = 'center';
            }
        }

        syncGameOverButtonWidths();

        // game state
        let running = true;
        let score = 0;
        const currentUser = <?= json_encode($currentUser) ?>;
        const serverHighScore = Number(<?= json_encode((int)$initialHighScore) ?>);
        const highScoreKey = 'gamedemo_high_score_' + (currentUser || 'guest');
        let highScore = Math.max(serverHighScore, Number(localStorage.getItem(highScoreKey) || 0));
        highScoreEl.textContent = String(highScore);
        let paused = false;
        let pauseElapsedMs = 0;
        let pauseCountdownTimer = null;

        function updateScoreDisplay(){
            scoreEl.textContent = String(Math.floor(score));
        }

        function saveHighScoreIfNeeded(){
            const finalScore = Math.floor(score);
            if (finalScore > highScore) {
                highScore = finalScore;
                localStorage.setItem(highScoreKey, String(highScore));
                highScoreEl.textContent = String(highScore);
            }

            // Always sync best score to DB on game over so admin dashboard stays current.
            if (currentUser && currentUser !== 'admin') {
                const scoreToSync = Math.max(highScore, finalScore);
                fetch('save_score.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'score=' + encodeURIComponent(String(scoreToSync))
                }).catch(() => {});
            }
        }

        // player physics
        let vy = 0;
        const gravity = 0.6;
        const jumpVel = 12;
        let onGround = true;
        const jumpBufferMs = 120;
        let jumpQueuedAt = -1;

        // world / ground alignment
        const groundY = 120; // matches #ground height and player standing baseline
        // spikes settings
        const tileW = 40, tileH = 40;
        const spikeBuriedPx = 10; // hide the brown base under the ground
        const ravenW = 130, ravenH = 78;
        const ravenGroundOffset = -16; // push raven down to sit on the floor line
        const felpudoW = 120, felpudoH = 60;
        const felpudoBottom = groundY + 95; // low enough to feel close, high enough to pass under
        const spacing = 420; // base spacing between spikes
        const baseSpeed = 6; // starting speed
        const speedRampPerMs = 0.00015; // gradual speed increase
        const maxSpacingScale = 1.3; // mild cap so difficulty still ramps
        const restGapMinSpeed = 9.5; // only start giving rest gaps at high speed
        const restGapCooldownMs = 7000; // minimum time between rest gaps
        const restGapChance = 0.2; // chance per respawn check when eligible
        const restGapMultiplier = 2.1; // one noticeably larger gap
        let lastRestGapAtMs = -Infinity;
        let ravenSpikeCooldown = 0; // after raven, force next 2 obstacles to spike
        let lastSpawnType = 'spike';

        // calculate spacing based on current speed to keep a consistent feel (wider gaps at higher speeds to compensate for faster movement)
        function spacingForSpeed(speedNow){
            const speedRatio = speedNow / baseSpeed;
            const scaled = 1 + ((speedRatio - 1) * 0.25);
            return Math.round(spacing * Math.min(maxSpacingScale, Math.max(1, scaled)));
        }

        // create tiles (spawn offscreen to the right so they come toward the player)
        let totalWidth = window.innerWidth * 3;
        const spikes = [];

        // next obstacle type picker 
        function pickObstacleType(){
            if (ravenSpikeCooldown > 0) {
                ravenSpikeCooldown--;
                lastSpawnType = 'spike';
                return 'spike';
            }

            const roll = Math.random();
            let type = 'spike';
            if (roll < 0.2) {
                type = 'raven';
            } else if (roll < 0.4) {
                type = 'felpudo';
            }

            // avoid long same-type chains (except forced spike cooldown)
            if (type === lastSpawnType && type !== 'spike' && Math.random() < 0.7) {
                type = 'spike';
            }

            if (type === 'raven') {
                ravenSpikeCooldown = 2;
            }

            lastSpawnType = type;
            return type;
        }

        //position and style a tile element based on type
        function applyObstacleStyle(el, type){
            el.dataset.type = type;
            if (type === 'raven') {
                el.className = 'raven-tile';
                el.style.width = ravenW + 'px';
                el.style.height = ravenH + 'px';
                el.style.bottom = (groundY + ravenGroundOffset) + 'px';
                return;
            }
            if (type === 'felpudo') {
                el.className = 'felpudo-tile';
                el.style.width = felpudoW + 'px';
                el.style.height = felpudoH + 'px';
                el.style.bottom = felpudoBottom + 'px';
                return;
            }
            el.className = 'spike-tile';
            el.style.width = tileW + 'px';
            el.style.height = tileH + 'px';
            el.style.bottom = (groundY - spikeBuriedPx) + 'px';
        }

        // initial tile creation offscreen for smooth entry
        function createTiles(){
            spikeContainer.innerHTML = '';
            spikes.length = 0;
            ravenSpikeCooldown = 0;
            lastSpawnType = 'spike';
            totalWidth = window.innerWidth * 2; // wrap width used for logic
            const count = Math.ceil(window.innerWidth / spacing) + 3;
            const startX = Math.floor(window.innerWidth + 50);
            for (let i = 0; i < count; i++) {
                const x = startX + i * spacing;
                const t = document.createElement('div');
                applyObstacleStyle(t, pickObstacleType());
                t.style.position = 'absolute';
                t.style.left = x + 'px';
                t.style.zIndex = 3; // behind player
                spikeContainer.appendChild(t);
                spikes.push(t);
            }
        }

        createTiles();

        // startup delay so spikes don't immediately hit the player
        let started = false;
        let startedAt = 0;
        setTimeout(()=> {
            started = true;
            startedAt = performance.now();
        }, 1000);

        // lives state
        let lives = 3;
        let invincible = false;

        // handle jump
        function jump(){
            if (!onGround || !running || paused) return;
            vy = jumpVel;
            onGround = false;
        }

        // immediate second jump
        function queueJump(){
            if (paused) return;
            jumpQueuedAt = performance.now();
            jump();
        }

        // spacebar to jump
        window.addEventListener('keydown', e => {
            if (e.code === 'Space') {
                e.preventDefault();
                queueJump();
            }
        });

        // checks if player collides with a obstacle
        function rectsOverlap(a,b){
            return !(a.right < b.left || a.left > b.right || a.bottom < b.top || a.top > b.bottom);
        }

        // blink to show damage and loss of life
        function blinkPlayer(){
            let count = 0;
            const blink = setInterval(() => {
                player.style.opacity = player.style.opacity === '0.3' ? '1' : '0.3';
                count++;
                if (count === 4) {
                    clearInterval(blink);
                    player.style.opacity = '1';
                }
            }, 120);
        }

        function blinkHeart(heart, done){
            if (!heart) {
                if (done) done();
                return;
            }
            let count = 0;
            const blink = setInterval(() => {
                heart.style.opacity = heart.style.opacity === '0.3' ? '1' : '0.3';
                count++;
                if (count === 4) {
                    clearInterval(blink);
                    heart.style.opacity = '1';
                    if (done) done();
                }
            }, 120);
        }

        // resets lives and hearts on restart
        function resetLives(){
            lives = 3;
            invincible = false;
            hearts.forEach(h => {
                h.style.display = '';
                h.style.opacity = '1';
            });
        }

        // lose life on collision with blink and temporary invincibility to avoid multiple hits
        function loseLife(hitSpike){
            if (invincible) return;

            lives--;
            const removeIndex = hearts.length - lives - 1;
            const heartToRemove = hearts[removeIndex];
            hearts.forEach((h, i) => {
                if (i !== removeIndex) h.style.opacity = '1';
            });
            blinkPlayer();

            if (hitSpike) {
                hitSpike.style.zIndex = '2';
            }

            invincible = true;
            setTimeout(() => {
                invincible = false;
                if (hitSpike) {
                    hitSpike.style.zIndex = '3';
                }
            }, 1200);

            blinkHeart(heartToRemove, () => {
                heartToRemove.style.display = 'none';
                if (lives === 0) {
                    running = false;
                    saveHighScoreIfNeeded();
                    gameOverEl.style.display = 'flex';
                    syncGameOverButtonWidths();
                }
            });
        }

        // restart game state
        function restartGame(){
            running = true;
            started = false;
            paused = false;
            pauseElapsedMs = 0;
            if (pauseCountdownTimer) {
                clearInterval(pauseCountdownTimer);
                pauseCountdownTimer = null;
            }
            pauseMenuEl.style.display = 'none';
            pauseMenuEl.classList.remove('counting');
            continueLink.style.display = 'block';
            homeLink.style.display = 'block';
            pauseCountdownEl.style.display = 'none';
            gameOverEl.style.display = 'none';
            player.style.bottom = groundY + 'px';
            player.style.opacity = '1';
            vy = 0;
            onGround = true;
            score = 0;
            updateScoreDisplay();
            resetLives();
            createTiles();
            setTimeout(() => {
                started = true;
                startedAt = performance.now();
            }, 3000);
            requestAnimationFrame(step);
        }

        // pause game and show menu
        function pauseGame(){
            if (!running || paused || gameOverEl.style.display === 'flex') return;
            paused = true;
            if (started) {
                pauseElapsedMs = performance.now() - startedAt;
            }
            pauseMenuEl.classList.remove('counting');
            pauseMenuEl.style.display = 'flex';
            continueLink.style.display = 'block';
            homeLink.style.display = 'block';
            pauseCountdownEl.style.display = 'none';
        }

        // resume game with countdown to give player a moment to prepare
        function resumeWithCountdown(){
            if (!paused || !running) return;
            continueLink.style.display = 'none';
            homeLink.style.display = 'none';
            pauseCountdownEl.style.display = 'block';
            pauseMenuEl.classList.add('counting');
            let count = 3;
            pauseCountdownEl.textContent = String(count);
            if (pauseCountdownTimer) {
                clearInterval(pauseCountdownTimer);
            }
            pauseCountdownTimer = setInterval(() => {
                count--;
                if (count > 0) {
                    pauseCountdownEl.textContent = String(count);
                    return;
                }
                clearInterval(pauseCountdownTimer);
                pauseCountdownTimer = null;
                pauseMenuEl.style.display = 'none';
                pauseMenuEl.classList.remove('counting');
                pauseCountdownEl.style.display = 'none';
                continueLink.style.display = 'block';
                homeLink.style.display = 'block';
                paused = false;
                if (started) {
                    startedAt = performance.now() - pauseElapsedMs;
                }
            }, 1000);
        }

        if (retryLink) {
            retryLink.addEventListener('click', function(e){
                e.preventDefault();
                restartGame();
            });
        }

        if (pauseBtn) {
            pauseBtn.addEventListener('click', function(){
                pauseGame();
            });
        }

        if (continueLink) {
            continueLink.addEventListener('click', function(e){
                e.preventDefault();
                resumeWithCountdown();
            });
        }

        function step(){
            if (!running) return;
            if (paused) {
                requestAnimationFrame(step);
                return;
            }

            // update spikes (only after start delay)
            if (started) {
                score += 0.1;
                updateScoreDisplay();
                const elapsed = Math.max(0, performance.now() - startedAt);
                const speed = baseSpeed + (elapsed * speedRampPerMs);
                const dynamicSpacing = spacingForSpeed(speed);
                for (let i=0;i<spikes.length;i++){
                    const el = spikes[i];
                    let x = parseFloat(el.style.left || 0);
                    const obstacleW = parseFloat(el.style.width || tileW);
                    x -= speed;
                    if (x < -obstacleW) {
                        // place this tile just after the current rightmost tile
                        let maxX = -Infinity;
                        for (let j=0;j<spikes.length;j++) {
                            const v = parseFloat(spikes[j].style.left || 0);
                            if (v > maxX) maxX = v;
                        }
                        let spawnGap = dynamicSpacing;
                        const canSpawnRestGap = speed >= restGapMinSpeed && (elapsed - lastRestGapAtMs) >= restGapCooldownMs;
                        if (canSpawnRestGap && Math.random() < restGapChance) {
                            spawnGap = Math.round(dynamicSpacing * restGapMultiplier);
                            lastRestGapAtMs = elapsed;
                        }
                        applyObstacleStyle(el, pickObstacleType());
                        x = maxX + spawnGap;
                    }
                    el.style.left = x + 'px';

                    // collision with player
                    // use inset rects to avoid transparent pixel collisions
                    function insetRect(r, inset){
                        return {
                            left: r.left + inset,
                            top: r.top + inset,
                            right: r.right - inset,
                            bottom: r.bottom - inset
                        };
                    }
                    function insetRectXY(r, insetX, insetY){
                        return {
                            left: r.left + insetX,
                            top: r.top + insetY,
                            right: r.right - insetX,
                            bottom: r.bottom - insetY
                        };
                    }
                    const pr0 = player.getBoundingClientRect();
                    const sr0 = el.getBoundingClientRect();
                    // tighten hitboxes so only actual contact counts
                    const playerInset = 18; // reduce player's collision box
                    const obstacleType = el.dataset.type || 'spike';
                    const obstacleInset = obstacleType === 'felpudo' ? 8 : (obstacleType === 'raven' ? 10 : 12);
                    const pr = insetRect(pr0, playerInset);
                    const sr = obstacleType === 'raven'
                        ? insetRectXY(sr0, 36, 16)
                        : insetRect(sr0, obstacleInset);
                    if (!invincible && rectsOverlap(pr, sr)) {
                        loseLife(el);
                        break;
                    }
                }
            }

            // update player physics
            let bottomPx = parseFloat(player.style.bottom) || groundY;
            if (!onGround) {
                vy -= gravity;
                bottomPx += vy;
                if (bottomPx <= groundY) {
                    bottomPx = groundY;
                    vy = 0;
                    onGround = true;
                }
                player.style.bottom = bottomPx + 'px';
            }

            // buffered input: if jump was pressed just before landing, jump instantly on touchdown
            if (running && onGround && jumpQueuedAt > 0 && (performance.now() - jumpQueuedAt) <= jumpBufferMs) {
                jump();
            }

            if (jumpQueuedAt > 0 && (performance.now() - jumpQueuedAt) > jumpBufferMs) {
                jumpQueuedAt = -1;
            }

            requestAnimationFrame(step);
        }

        requestAnimationFrame(step);

        window.addEventListener('resize', ()=>{
            createTiles();
            syncGameOverButtonWidths();
        });
    })();
</script>




