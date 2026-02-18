<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publish'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Security Check Failed: Invalid CSRF Token.");
    }

    $title = stripslashes($_POST['title']);
    $content = stripslashes($_POST['content']);
    $student_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($content)) {
        // Handle Featured Image Upload
        $image_path = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['featured_image']['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];

            if (array_key_exists($mimeType, $allowedMimes)) {
                $uploadDir = 'uploads/wall_thumbnails/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $ext = $allowedMimes[$mimeType];
                $fileName = uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
                    $image_path = $targetPath;
                }
            }
        }

        if (!$image_path) {
            // Auto-detect first image from content
            preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
            if (isset($matches[1])) {
                $image_path = $matches[1];
            }
        }

        $stmt = $conn->prepare("INSERT INTO wall_posts (student_id, title, content, image_path, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("isss", $student_id, $title, $content, $image_path);
        if ($stmt->execute()) {
            // Redirect with success message (maybe add query param)
            header("Location: wall_of_talent.php?msg=pending");
            exit();
        } else {
            $error = "Failed to publish post.";
        }
    } else {
        $error = "Title and content are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post | Wall of Talent</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Summernote Lite CSS/JS (No Bootstrap required) -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <style>
        :root { --primary: #0066FF; --secondary: #7C3AED; --dark: #0F172A; --light: #F8FAFC; --gray: #64748B; }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 250px; }
        
        .card { background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 1.5rem; }
        .label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark); }
        .input-text { width: 100%; padding: 0.8rem; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 1rem; box-sizing: border-box; }
        
        .btn-publish {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            width: 100%;
        }
        .btn-publish:hover { opacity: 0.9; }

        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1.5rem; padding-top: 5rem; } }
        
        /* Summernote Overrides to match theme */
        .note-editor.note-frame { border: 2px solid #e2e8f0; border-radius: 8px; box-shadow: none; }
        .note-toolbar { border-bottom: 1px solid #e2e8f0; background: #f8fafc; border-radius: 8px 8px 0 0; }
        .note-statusbar { border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <?php include 'student_sidebar.php'; ?>
    <div class="main-content">
        <div class="card">
            <h1 style="margin-top: 0;">Create New Post</h1>
            <p style="color: var(--gray); margin-bottom: 2rem;">Posts will be reviewed by admin before appearing on the public wall.</p>
            
            <form method="POST" id="postForm" enctype="multipart/form-data">
                <?php csrf_input(); ?>
                <div class="form-group">
                    <label class="label">Title</label>
                    <input type="text" name="title" class="input-text" required placeholder="Enter post title...">
                </div>

                <div class="form-group">
                    <label class="label">Featured Image (Thumbnail)</label>
                    <input type="file" name="featured_image" class="input-text" accept="image/*">
                    <small style="color: var(--gray);">This image will be shown on the Wall card. If left empty, the first image in your content will be used.</small>
                </div>

                <div class="form-group">
                    <label class="label">Content</label>
                    <textarea id="summernote" name="content"></textarea>
                </div>

                <button type="submit" name="publish" class="btn-publish">Publish Post</button>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                placeholder: 'Write your story here...',
                tabsize: 2,
                height: 400,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onImageUpload: function(files) {
                        for (let i = 0; i < files.length; i++) {
                            uploadImage(files[i]);
                        }
                    }
                }
            });
        });

        function uploadImage(file) {
            let data = new FormData();
            data.append("file", file);
            $.ajax({
                data: data,
                type: "POST",
                url: "upload_image.php",
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    try {
                        let res = JSON.parse(response);
                        if (res.location) {
                            $('#summernote').summernote('insertImage', res.location);
                        } else {
                            alert('Image upload failed: ' + (res.error || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error("Invalid JSON response:", response);
                        alert('Error uploading image.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error(textStatus + " " + errorThrown);
                    alert('Error uploading image.');
                }
            });
        }
    </script>
</body>
</html>
