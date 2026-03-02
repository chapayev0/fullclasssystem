<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Settings Update
// Handle Finance Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    if (isset($_POST['teacher_share_percentage'])) {
        $teacher_share = intval($_POST['teacher_share_percentage']);
        if ($teacher_share < 0 || $teacher_share > 100) {
            $error_msg = "Percentage must be between 0 and 100.";
        } else {
            $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'teacher_share_percentage'");
            $stmt->bind_param("s", $teacher_share);
            $stmt->execute();
            $success_msg = "Finance settings updated successfully!";
        }
    }
}

// Handle General Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_general_settings'])) {
    $inst_name = $_POST['institute_name'];
    $site_phone = $_POST['site_phone'];
    $site_email = $_POST['site_email'];
    $site_address = $_POST['site_address'];

    // Update text settings
    $text_settings = [
        'institute_name' => $inst_name,
        'site_phone' => $site_phone,
        'site_email' => $site_email,
        'site_address' => $site_address
    ];

    foreach ($text_settings as $key => $val) {
        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $val, $key);
        $stmt->execute();
    }

    // Handle Logo Upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/site/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $file_name = 'logo_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target_file)) {
            $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'site_logo'");
            $stmt->bind_param("s", $target_file);
            $stmt->execute();
        }
    }

    $success_msg = "General settings updated successfully!";
}

// Handle About Page Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_about_settings'])) {
    $about_title = $_POST['about_section_title'];
    $about_subtitle = $_POST['about_section_subtitle'];
    $about_name = $_POST['about_manager_name'];
    $about_manager_title = $_POST['about_manager_title'];
    $about_desc = $_POST['about_description'];
    $about_points = $_POST['about_points'];

    $about_data = [
        'about_section_title' => $about_title,
        'about_section_subtitle' => $about_subtitle,
        'about_manager_name' => $about_name,
        'about_manager_title' => $about_manager_title,
        'about_description' => $about_desc,
        'about_points' => $about_points
    ];

    foreach ($about_data as $key => $val) {
        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $val, $key);
        $stmt->execute();
        
        // If row doesn't exist, insert it
        if ($stmt->affected_rows === 0) {
            $check = $conn->prepare("SELECT setting_key FROM site_settings WHERE setting_key = ?");
            $check->bind_param("s", $key);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                $ins->bind_param("ss", $key, $val);
                $ins->execute();
            }
        }
    }

    // Handle About Manager Image Upload
    if (isset($_FILES['about_manager_image']) && $_FILES['about_manager_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/site/';
        $file_ext = pathinfo($_FILES['about_manager_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'about_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['about_manager_image']['tmp_name'], $target_file)) {
            $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'about_manager_image'");
            $stmt->bind_param("s", $target_file);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                $ins = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('about_manager_image', ?)");
                $ins->bind_param("s", $target_file);
                $ins->execute();
            }
        }
    }

    $success_msg = "About page settings updated successfully!";
}

// Handle Hero Slider Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_hero_settings'])) {
    $hero_data = [];
    for ($i = 1; $i <= 3; $i++) {
        $hero_data["hero_slide_{$i}_title"] = $_POST["hero_slide_{$i}_title"];
        $hero_data["hero_slide_{$i}_desc"] = $_POST["hero_slide_{$i}_desc"];
        $hero_data["hero_slide_{$i}_btn_text"] = $_POST["hero_slide_{$i}_btn_text"];
        $hero_data["hero_slide_{$i}_btn_action"] = $_POST["hero_slide_{$i}_btn_action"];
        $hero_data["hero_slide_{$i}_btn_enabled"] = isset($_POST["hero_slide_{$i}_btn_enabled"]) ? '1' : '0';
        $hero_data["hero_slide_{$i}_overlay_color"] = $_POST["hero_slide_{$i}_overlay_color"];
        $hero_data["hero_slide_{$i}_overlay_opacity"] = $_POST["hero_slide_{$i}_overlay_opacity"];
        $hero_data["hero_slide_{$i}_title_color"] = $_POST["hero_slide_{$i}_title_color"];
        $hero_data["hero_slide_{$i}_title_size"] = $_POST["hero_slide_{$i}_title_size"];
        $hero_data["hero_slide_{$i}_desc_color"] = $_POST["hero_slide_{$i}_desc_color"];
        $hero_data["hero_slide_{$i}_desc_size"] = $_POST["hero_slide_{$i}_desc_size"];
    }

    foreach ($hero_data as $key => $val) {
        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $val, $key);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            $check = $conn->prepare("SELECT setting_key FROM site_settings WHERE setting_key = ?");
            $check->bind_param("s", $key);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                $ins->bind_param("ss", $key, $val);
                $ins->execute();
            }
        }
    }

    // Handle Hero Slide Background Images
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_FILES["hero_slide_{$i}_bg"]) && $_FILES["hero_slide_{$i}_bg"]['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/site/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES["hero_slide_{$i}_bg"]['name'], PATHINFO_EXTENSION);
            $file_name = "hero_bg_{$i}_" . time() . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES["hero_slide_{$i}_bg"]['tmp_name'], $target_file)) {
                $key = "hero_slide_{$i}_bg";
                $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->bind_param("ss", $target_file, $key);
                $stmt->execute();
                if ($stmt->affected_rows === 0) {
                    $ins = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                    $ins->bind_param("ss", $key, $target_file);
                    $ins->execute();
                }
            }
        }
    }

    $success_msg = "Hero slider settings updated successfully!";
}

// Fetch Current Settings
$settings = [];
$res = $conn->query("SELECT * FROM site_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$teacher_share = isset($settings['teacher_share_percentage']) ? intval($settings['teacher_share_percentage']) : 80;
$institute_share = 100 - $teacher_share;
$inst_name = $settings['institute_name'] ?? '';
$site_logo = $settings['site_logo'] ?? '';
$site_phone = $settings['site_phone'] ?? '';
$site_email = $settings['site_email'] ?? '';
$site_address = $settings['site_address'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --success: #10B981;
            --danger: #EF4444;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 3rem; margin-left: 290px; }
        .card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 800px; margin-bottom: 2rem; }
        .header { margin-bottom: 2rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark); }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .btn { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }
        .section-title { margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #f1f5f9; color: var(--dark); }
        .logo-preview { width: 150px; height: auto; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Site Settings</h1>
            <p style="color: var(--gray);">Manage your institute details, logo, and finance configurations.</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 class="section-title">General Settings</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Institute Name</label>
                    <input type="text" name="institute_name" class="form-control" value="<?php echo htmlspecialchars($inst_name); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Site Logo</label>
                    <?php if ($site_logo): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="Current Logo" class="logo-preview">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="site_logo" class="form-control" accept="image/*">
                    <small style="color: var(--gray);">Recommended: 300x100px PNG with transparency.</small>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($site_phone); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($site_email); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Physical Address</label>
                    <textarea name="site_address" class="form-control" rows="3" required><?php echo htmlspecialchars($site_address); ?></textarea>
                </div>

                <button type="submit" name="update_general_settings" class="btn">Save General Settings</button>
            </form>
        </div>

        <div class="card">
            <h2 class="section-title">About Page Settings</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" name="about_section_title" class="form-control" value="<?php echo htmlspecialchars($settings['about_section_title'] ?? 'About Institute'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Section Subtitle</label>
                        <input type="text" name="about_section_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['about_section_subtitle'] ?? 'Dedicated to empowering the next generation of tech leaders'); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Manager/Founder Photo</label>
                    <?php if (isset($settings['about_manager_image'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo htmlspecialchars($settings['about_manager_image']); ?>" alt="Current Photo" class="logo-preview">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="about_manager_image" class="form-control" accept="image/*">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Manager Name</label>
                        <input type="text" name="about_manager_name" class="form-control" value="<?php echo htmlspecialchars($settings['about_manager_name'] ?? 'Shashika Dilhara'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Manager Title</label>
                        <input type="text" name="about_manager_title" class="form-control" value="<?php echo htmlspecialchars($settings['about_manager_title'] ?? 'Lead Instructor & Founder'); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bio / History Description</label>
                    <textarea name="about_description" class="form-control" rows="5" required><?php echo htmlspecialchars($settings['about_description'] ?? 'With over 6 years of experience in ICT education, I am passionate about simplifying complex technology concepts for students.'); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Key Points / Qualifications (One per line)</label>
                    <textarea name="about_points" class="form-control" rows="5" required><?php echo htmlspecialchars($settings['about_points'] ?? "BICT (Hons) in Software System Technology (UOK)\nDiploma in Digital Marketing (SLIM)\nWeb Master at Maxibot\n6+ Years of Experience Software Development"); ?></textarea>
                </div>

                <button type="submit" name="update_about_settings" class="btn">Save About Settings</button>
            </form>
        </div>

        <div class="card" style="max-width: 1000px;">
            <h2 class="section-title">Hero Section Slider</h2>
            <form method="POST" enctype="multipart/form-data">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #e2e8f0;">
                        <h3 style="margin-top: 0; color: var(--primary); border-bottom: 2px solid var(--primary); display: inline-block; padding-bottom: 5px;">Slide <?php echo $i; ?></h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Background Image</label>
                                <?php if (isset($settings["hero_slide_{$i}_bg"])): ?>
                                    <img src="<?php echo htmlspecialchars($settings["hero_slide_{$i}_bg"]); ?>" class="logo-preview" style="width: 100%; height: 100px; object-fit: cover;">
                                <?php endif; ?>
                                <input type="file" name="hero_slide_<?php echo $i; ?>_bg" class="form-control" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Overlay & Opacity</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" name="hero_slide_<?php echo $i; ?>_overlay_color" class="form-control" style="width: 50px; padding: 2px; height: 40px;" value="<?php echo htmlspecialchars($settings["hero_slide_{$i}_overlay_color"] ?? '#ffffff'); ?>">
                                    <input type="range" name="hero_slide_<?php echo $i; ?>_overlay_opacity" min="0" max="1" step="0.1" class="form-control" style="flex: 1;" value="<?php echo htmlspecialchars($settings["hero_slide_{$i}_overlay_opacity"] ?? '0.9'); ?>">
                                </div>
                                <small>Adjust overlay color and transparency</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Title Text</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="hero_slide_<?php echo $i; ?>_title" class="form-control" style="flex: 3;" value="<?php 
                                    $default_titles = [1 => 'Master ICT Skills for the Digital Future', 2 => 'Learn from Industry Experts', 3 => 'Flexible Online & Physical Classes'];
                                    echo htmlspecialchars($settings["hero_slide_{$i}_title"] ?? $default_titles[$i]); 
                                ?>" required>
                                <input type="color" name="hero_slide_<?php echo $i; ?>_title_color" class="form-control" style="width: 50px; padding: 2px; height: 44px;" value="<?php echo htmlspecialchars($settings["hero_slide_{$i}_title_color"] ?? '#000000'); ?>">
                                <input type="text" name="hero_slide_<?php echo $i; ?>_title_size" class="form-control" style="width: 80px;" value="<?php echo htmlspecialchars($settings["hero_slide_{$i}_title_size"] ?? '4rem'); ?>" placeholder="Size (e.g. 4rem)">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description Text</label>
                            <div style="display: flex; gap: 10px; align-items: flex-start;">
                                <textarea name="hero_slide_<?php echo $i; ?>_desc" class="form-control" style="flex: 3;" rows="2" required><?php 
                                    $default_descs = [
                                        1 => 'Join Sri Lanka\'s premier ICT academy and unlock your potential with expert guidance and comprehensive curriculum',
                                        2 => 'Our experienced instructors bring real-world knowledge to help you excel in O/L ICT examinations',
                                        3 => 'Choose your learning path with our hybrid model - attend in person or join from anywhere in Sri Lanka'
                                    ];
                                    echo htmlspecialchars($settings["hero_slide_{$i}_desc"] ?? $default_descs[$i]); 
                                ?></textarea>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <input type="color" name="hero_slide_<?php echo $i; ?>_desc_color" class="form-control" style="width: 50px; padding: 2px; height: 40px;" value="<?php echo htmlspecialchars($settings["hero_slide_{$i}_desc_color"] ?? '#6B7280'); ?>">
                                    <input type="text" name="hero_slide_<?php echo $i; ?>_desc_size" class="form-control" style="width: 80px;" value="<?php echo htmlspecialchars($settings["hero_slide_{$i}_desc_size"] ?? '1.3rem'); ?>" placeholder="Size (e.g. 1.3rem)">
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Button Settings</label>
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                    <input type="checkbox" name="hero_slide_<?php echo $i; ?>_btn_enabled" value="1" <?php echo ($settings["hero_slide_{$i}_btn_enabled"] ?? '1') == '1' ? 'checked' : ''; ?>>
                                    <span>Enable Button</span>
                                </div>
                                <input type="text" name="hero_slide_<?php echo $i; ?>_btn_text" class="form-control" value="<?php 
                                    $default_btns = [1 => 'Explore Classes', 2 => 'Meet Our Team', 3 => 'Join Online'];
                                    echo htmlspecialchars($settings["hero_slide_{$i}_btn_text"] ?? $default_btns[$i]); 
                                ?>" placeholder="Button Text">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Button Action</label>
                                <div style="height: 32px;"></div> <!-- Spacer -->
                                <input type="text" name="hero_slide_<?php echo $i; ?>_btn_action" class="form-control" value="<?php 
                                    $default_actions = [1 => '#classes', 2 => 'teachers.php', 3 => 'javascript:openJoinModal()'];
                                    echo htmlspecialchars($settings["hero_slide_{$i}_btn_action"] ?? $default_actions[$i]); 
                                ?>" placeholder="Link or JS">
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>

                <button type="submit" name="update_hero_settings" class="btn" style="width: 100%; padding: 1.2rem; font-size: 1.1rem; margin-top: 1rem;">Save Advanced Hero Settings</button>
            </form>
        </div>

        <div class="card">
            <h2 class="section-title">Finance Settings</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Teacher Share Percentage (%)</label>
                    <input type="number" name="teacher_share_percentage" id="teacherShareInput" class="form-control" value="<?php echo $teacher_share; ?>" min="0" max="100" required oninput="updatePreview()">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Institute Share Percentage (%)</label>
                    <input type="number" id="instituteShareInput" class="form-control" value="<?php echo $institute_share; ?>" disabled>
                    <small style="color: var(--gray);">Automatically calculated as 100% - Teacher Share.</small>
                </div>

                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; padding: 1rem; background: #f1f5f9; border-radius: 8px; font-weight: 600;">
                    <div style="color: var(--primary);">Teacher: <span id="teacherSpan"><?php echo $teacher_share; ?></span>%</div>
                    <div style="color: var(--dark);">Institute: <span id="instituteSpan"><?php echo $institute_share; ?></span>%</div>
                </div>

                <button type="submit" name="update_settings" class="btn">Save Finance Settings</button>
            </form>
        </div>
    </div>

    <script>
        function updatePreview() {
            const teacherShare = parseInt(document.getElementById('teacherShareInput').value) || 0;
            const instShare = Math.max(0, 100 - teacherShare);
            
            document.getElementById('instituteShareInput').value = instShare;
            document.getElementById('teacherSpan').innerText = teacherShare;
            document.getElementById('instituteSpan').innerText = instShare;
        }
    </script>
</body>
</html>
