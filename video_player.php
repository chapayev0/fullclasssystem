<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;
// Validate $id is int
$id = intval($id);

$sql = "SELECT * FROM resources WHERE id = $id AND category = 'video'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $video = $result->fetch_assoc();
} else {
    die("Video not found or access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch: <?php echo htmlspecialchars($video['title']); ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <!-- Video.js CSS -->
    <link href="https://vjs.zencdn.net/8.6.1/video-js.css" rel="stylesheet" />
    <style>
        /* Homepage Theme Variables */
        :root {
            --primary: #000000;
            --primary-hover: #333333;
            --secondary: #F8F8FB;
            --accent: #E5E5E8;
            --dark: #1A1A1A;
            --light: #FFFFFF;
            --gray: #6B7280;
            --border: #E5E7EB;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            color: var(--dark);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navbar (Matches Homepage) */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            font-family: 'Space Mono', monospace;
            text-decoration: none;
        }

        .back-btn {
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .back-btn:hover {
            color: var(--gray);
            transform: translateX(-5px);
        }

        /* Container */
        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        /* Video Player Wrapper */
        .video-wrapper {
            width: 100%;
            max-width: 1100px;
            aspect-ratio: 16/9;
            background: black;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            position: relative;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Custom Audio/Video Player Styling for Video.js */
        .video-js {
            width: 100%;
            height: 100%;
            font-family: 'Outfit', sans-serif;
        }

        .video-js .vjs-big-play-button {
            background-color: var(--primary); /* Black */
            border-color: var(--primary);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: 0;
            margin-left: 0;
            transition: all 0.3s ease;
        }

        .video-js .vjs-big-play-button:hover {
            background-color: var(--primary-hover);
            transform: translate(-50%, -50%) scale(1.1);
        }

        .video-js .vjs-control-bar {
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 0 0 12px 12px;
        }

        .video-js .vjs-slider {
            background-color: rgba(255,255,255,0.3);
        }
        
        .video-js .vjs-play-progress {
            background-color: var(--primary); /* Use Primary Color (Black/Dark) might be invisible on black bar? actually primary is black. Let's use White for progress or a distinct color. */
            background-color: #3B82F6; /* Use Blue for visibility against black control bar */
        }

        /* Info Section */
        .info {
            max-width: 1100px;
            width: 100%;
        }

        .title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--primary);
            line-height: 1.2;
        }

        .desc-box {
            background: var(--secondary);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .desc {
            color: var(--gray);
            line-height: 1.8;
            font-size: 1.05rem;
            margin-bottom: 2rem;
        }

        .btn-download {
            display: inline-block;
            background: var(--primary);
            color: var(--light);
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Custom Control Buttons */
        .vjs-skip-btn {
            font-size: 14px;
            width: 40px;
            cursor: pointer;
            margin-top: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="student_dashboard.php" class="back-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to Dashboard
        </a>
        <a href="#" class="logo"><img src="assest/logo/logo1.png" width="120"  alt="Logo"></a>
    </nav>
    
    <div class="container">
        <!-- Video Player -->
        <div class="video-wrapper">
            <?php if ($video['video_type'] == 'link'): ?>
                <?php
                // Attempt to embed if YouTube or Google Drive
                $url = $video['filepath'];
                $embed_url = $url;
                
                // YouTube
                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $url, $matches)) {
                    $embed_url = "https://www.youtube.com/embed/" . $matches[1] . "?rel=0&modestbranding=1";
                } 
                // Google Drive
                elseif (preg_match('/drive\.google\.com\/(?:file\/d\/|open\?id=)([^"&?\/ ]+)/', $url, $matches)) {
                    $embed_url = "https://drive.google.com/file/d/" . $matches[1] . "/preview";
                }
                ?>
                <iframe src="<?php echo htmlspecialchars($embed_url); ?>" allowfullscreen></iframe>
            <?php else: ?>
                <!-- Local File with Video.js -->
                <video id="my-video" class="video-js vjs-big-play-centered" controls preload="auto" data-setup='{"playbackRates": [0.5, 1, 1.5, 2], "fluid": true}'>
                    <source src="<?php echo htmlspecialchars($video['filepath']); ?>" type="video/mp4">
                    <p class="vjs-no-js">
                        To view this video please enable JavaScript, and consider upgrading to a web browser that
                        <a href="https://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
                    </p>
                </video>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="info">
            <h1 class="title"><?php echo htmlspecialchars($video['title']); ?></h1>
            <div class="desc-box">
                <div class="desc">
                    <?php echo nl2br(htmlspecialchars($video['description'])); ?>
                </div>
                
                <?php if ($video['allow_download'] && $video['video_type'] == 'file'): ?>
                    <a href="<?php echo htmlspecialchars($video['filepath']); ?>" download class="btn-download">
                        Download Video
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Video.js JS -->
    <script src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>
    <script>
        <?php if ($video['video_type'] !== 'link'): ?>
        var player = videojs('my-video');

        // Add Custom Forward/Backward Buttons
        var Button = videojs.getComponent('Button');

        // Rewind 10s
        var RewindBtn = videojs.extend(Button, {
            constructor: function() {
                Button.apply(this, arguments);
                this.addClass('vjs-icon-replay-10');
                this.controlText('Rewind 10s');
                this.el().innerHTML = '<span style="font-size:1.2rem;line-height:2.5;">↺ 10</span>';
            },
            handleClick: function() {
                var newTime = player.currentTime() - 10;
                player.currentTime(newTime < 0 ? 0 : newTime);
            }
        });
        videojs.registerComponent('RewindBtn', RewindBtn);
        player.getChild('controlBar').addChild('RewindBtn', {}, 0);

        // Forward 10s
        var ForwardBtn = videojs.extend(Button, {
            constructor: function() {
                Button.apply(this, arguments);
                this.addClass('vjs-icon-forward-10');
                this.controlText('Forward 10s');
                this.el().innerHTML = '<span style="font-size:1.2rem;line-height:2.5;">10 ↻</span>';
            },
            handleClick: function() {
                var newTime = player.currentTime() + 10;
                var duration = player.duration();
                player.currentTime(newTime > duration ? duration : newTime);
            }
        });
        videojs.registerComponent('ForwardBtn', ForwardBtn);
        player.getChild('controlBar').addChild('ForwardBtn', {}, 1);
        <?php endif; ?>
    </script>
</body>
</html>
