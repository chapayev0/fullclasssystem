<?php
session_start();
include 'db_connect.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$product_id) {
    header("Location: admin_products.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    // Update basic info
    $stmt = $conn->prepare("UPDATE store_products SET name=?, description=?, price=? WHERE id=?");
    $stmt->bind_param("ssdi", $name, $description, $price, $product_id);

    if ($stmt->execute()) {
        $success_msg = "Product updated successfully!";
        
        // Handle New Images
        $upload_dir = 'assest/images/products/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $new_images = [];
         
        // 1. File Uploads
        if (isset($_FILES['image_upload'])) {
            $total = count($_FILES['image_upload']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['image_upload']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['image_upload']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $new_name = uniqid('product_') . '_' . $i . '.' . $ext;
                        if (move_uploaded_file($_FILES['image_upload']['tmp_name'][$i], $upload_dir . $new_name)) {
                            $new_images[] = $upload_dir . $new_name;
                        }
                    }
                }
            }
        }
        
        // 2. URL Inputs
        if (!empty($_POST['new_image_url'])) {
             $url = trim($_POST['new_image_url']);
             if (!empty($url)) $new_images[] = $url;
        }

        if (!empty($new_images)) {
            $stmt_ins = $conn->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
            foreach ($new_images as $img) {
                $stmt_ins->bind_param("is", $product_id, $img);
                $stmt_ins->execute();
            }
            $stmt_ins->close();
        }

    } else {
        $error_msg = "Error updating product: " . $conn->error;
    }
    $stmt->close();
}

// Handle Delete Image
if (isset($_POST['delete_image'])) {
    $img_id = intval($_POST['image_id']);
    $stmt = $conn->prepare("DELETE FROM product_images WHERE id=? AND product_id=?");
    $stmt->bind_param("ii", $img_id, $product_id);
    if ($stmt->execute()) {
        $success_msg = "Image deleted.";
    }
    $stmt->close();
}

// Handle Set Primary
if (isset($_POST['set_primary'])) {
    $img_id = intval($_POST['image_id']);
    // Reset all to 0
    $conn->query("UPDATE product_images SET is_primary=0 WHERE product_id=$product_id");
    // Set selected to 1
    $stmt = $conn->prepare("UPDATE product_images SET is_primary=1 WHERE id=? AND product_id=?");
    $stmt->bind_param("ii", $img_id, $product_id);
    $stmt->execute();
    $stmt->close();
    $success_msg = "Primary image updated.";
}

// Fetch product data
$stmt = $conn->prepare("SELECT * FROM store_products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

// Fetch images
$product_images = [];
$img_res = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, id ASC");
while ($row = $img_res->fetch_assoc()) {
    $product_images[] = $row;
}

if (!$product) {
    echo "Product not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            margin: 0;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background: var(--dark);
            color: white;
            min-height: 100vh;
            padding: 2rem;
            position: fixed;
            left: 0;
            top: 0;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 3rem;
            color: var(--primary);
        }
        .nav-link {
            display: block;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 1rem 0;
            transition: color 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            font-weight: 600;
        }
        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 290px;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
        }
        .btn {
            background: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            margin-left: 1rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
        }
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1>Edit Product</h1>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['image_url']); ?>">
                
                <div class="form-group">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Price (Rs.)</label>
                    <input type="number" name="price" class="form-control" value="<?php echo htmlspecialchars($product['price']); ?>" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Manage Images</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <?php foreach ($product_images as $img): ?>
                            <div style="border: 1px solid #ddd; border-radius: 8px; padding: 0.5rem; text-align: center; background: #fff;">
                                <div style="height: 100px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 0.5rem;">
                                    <?php if (filter_var($img['image_url'], FILTER_VALIDATE_URL) || file_exists($img['image_url']) || strpos($img['image_url'], 'assest/') === 0): ?>
                                        <img src="<?php echo htmlspecialchars($img['image_url']); ?>" style="max-height: 100%; max-width: 100%;">
                                    <?php else: ?>
                                        <span style="font-size: 2rem;"><?php echo htmlspecialchars($img['image_url']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <?php if ($img['is_primary']): ?>
                                        <span style="font-size: 0.8rem; background: var(--success); color: white; padding: 2px 6px; border-radius: 4px;">Main</span>
                                    <?php else: ?>
                                        <button type="submit" name="set_primary" value="1" onclick="this.form.querySelector('[name=image_id]').value='<?php echo $img['id']; ?>'" style="border: none; background: none; color: var(--primary); cursor: pointer; font-size: 0.8rem;">Set Main</button>
                                    <?php endif; ?>
                                    <button type="submit" name="delete_image" value="1" onclick="if(!confirm('Delete this image?')) return false; this.form.querySelector('[name=image_id]').value='<?php echo $img['id']; ?>'" style="border: none; background: none; color: var(--danger); cursor: pointer; font-size: 0.8rem;">Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <input type="hidden" name="image_id" value="">
                    
                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px dashed #cbd5e1;">
                        <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--dark);">Add New Images</h4>
                        <div style="margin-bottom: 1rem;">
                            <label style="font-size: 0.9rem;">Upload (Multiple):</label>
                            <input type="file" name="image_upload[]" class="form-control" accept="image/*" multiple>
                        </div>
                         <div style="text-align: center; margin: 0.5rem 0; color: var(--gray); font-size: 0.8rem;">- OR -</div>
                        <div>
                            <label style="font-size: 0.9rem;">Image URL:</label>
                            <input type="text" name="new_image_url" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <button type="submit" name="update_product" class="btn">Update Product</button>
                <a href="admin_products.php" class="btn btn-outline">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
