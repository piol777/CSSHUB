<?php
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    redirect('/cdsgahub/auth/login.php');
}

// Bawal i-cache ang page na ito — para hindi "ma-stuck" dito kapag pinindot ang Back
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$redirectTo = '../' . $_SESSION['role'] . '/dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading - CDSGA HUB</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
            overflow: hidden;
            background-color: #f3f2fa;
            transition: background-color 0.2s ease;
        }
        body.theme-dark-purple {
            background-color: #0a0818;
        }
        body.theme-dark {
            background-color: #18191a;
        }
        .loading-text {
            color: #7c5cff;
            font-size: 42px;
            font-weight: 800;
            letter-spacing: 1px;
            opacity: 0;
            transform: scale(0.85);
            animation: loadingInOut 5s ease forwards;
        }
        @keyframes loadingInOut {
            0%   { opacity: 0; transform: scale(0.85); }
            15%  { opacity: 1; transform: scale(1); }
            85%  { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(1.08); }
        }
        .loading-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 18px;
        }
        .loading-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #7c5cff;
            animation: dotPulse 1.2s ease-in-out infinite;
        }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dotPulse {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }
    </style>
</head>
<body>
    <div>
        <div class="loading-text">CDSGA HUB</div>
        <div class="loading-dots"><span></span><span></span><span></span></div>
    </div>

    <script>
        const REDIRECT_TO = <?= json_encode($redirectTo) ?>;

        // Gamitin ang parehong theme na naka-set sa buong system (localStorage key: 'cdsga_theme')
        const savedTheme = localStorage.getItem('cdsga_theme') || 'light';
        if (savedTheme === 'dark-purple') {
            document.body.classList.add('theme-dark-purple');
        } else if (savedTheme === 'dark') {
            document.body.classList.add('theme-dark');
        }

        function goToDashboard() {
            // .replace() sa halip na .href — para hindi maidagdag ang loading.php
            // sa browser history, kaya hindi ito "mapupuntahan" pag pinindot ang Back
            window.location.replace(REDIRECT_TO);
        }

        setTimeout(goToDashboard, 5000);

        // Kung sakaling ma-cache pa rin ng browser ang page na ito (bfcache) at
        // bumalik dito ang user gamit ang Back button, i-redirect agad, huwag
        // hintayin pang mag-restart ang 5-second timer.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                goToDashboard();
            }
        });
    </script>
</body>
</html>