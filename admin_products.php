<?php
session_start();
include 'db_connect.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Add Product
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    // Insert Product first to get ID
    $stmt = $conn->prepare("INSERT INTO store_products (name, description, price) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $description, $price);

    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;
        $success_msg = "Product added successfully! ";
        
        // Handle Images
        $upload_dir = 'assest/images/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $image_urls = [];
        
        // 1. Handle File Uploads (Multiple)
        if (isset($_FILES['image_upload'])) {
            $total_files = count($_FILES['image_upload']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['image_upload']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($_FILES['image_upload']['name'][$i], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_ext, $allowed)) {
                        $new_filename = uniqid('product_') . '_' . $i . '.' . $file_ext;
                        $path = $upload_dir . $new_filename;
                        if (move_uploaded_file($_FILES['image_upload']['tmp_name'][$i], $path)) {
                            $image_urls[] = $path;
                        }
                    }
                }
            }
        }
        
        // 2. Handle Text URLs (Split by new line or comma)
        if (!empty($_POST['image_urls_text'])) {
            $urls = preg_split('/[\r\n,]+/', $_POST['image_urls_text']);
            foreach ($urls as $url) {
                $url = trim($url);
                if (!empty($url)) {
                    $image_urls[] = $url;
                }
            }
        }
        
        // Insert images into DB
        if (!empty($image_urls)) {
            $stmt_img = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
            foreach ($image_urls as $index => $url) {
                $is_primary = ($index === 0) ? 1 : 0;
                $stmt_img->bind_param("isi", $product_id, $url, $is_primary);
                $stmt_img->execute();
            }
            $stmt_img->close();
            $success_msg .= count($image_urls) . " images uploaded.";
        } else {
            // Default placeholder if none
            $default_img = '📦'; 
            $conn->query("INSERT INTO product_images (product_id, image_url, is_primary) VALUES ($product_id, '$default_img', 1)");
        }

    } else {
        $error_msg = "Error adding product: " . $conn->error;
    }
    $stmt->close();
}

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM store_products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        $success_msg = "Product deleted successfully!";
    } else {
        $error_msg = "Error deleting product: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all products with primary image
$products = [];
$sql = "SELECT p.*, 
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image 
        FROM store_products p 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Fallback if no primary image found but records exist (shouldn't happen with migration but safe)
        if (empty($row['primary_image'])) {
            $img_res = $conn->query("SELECT image_url FROM product_images WHERE product_id = " . $row['id'] . " LIMIT 1");
            if ($img_row = $img_res->fetch_assoc()) {
                $row['primary_image'] = $img_row['image_url'];
            } else {
                 $row['primary_image'] = '📦';
            }
        }
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Store | ICT with Dilhara Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --danger: #EF4444;
            --success: #10B981;
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
        .header {
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .tab-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            transition: all 0.3s;
        }
        .tab-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Form */
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
        .btn:hover {
            opacity: 0.9;
        }
        .btn-danger {
            background: var(--danger);
        }
        .btn-edit {
            background: var(--secondary);
            margin-right: 0.5rem;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            color: var(--gray);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
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
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Store</h1>
            <p style="color: var(--gray);">Add, edit, or delete products.</p>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('view')">View Products</button>
            <button class="tab-btn" onclick="switchTab('add')">Add New Product</button>
        </div>
        
        <!-- View Products Tab -->
        <div id="view-tab">
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--gray);">No products found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div style="width: 50px; height: 50px; background: #eee; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;">
                                                    <?php 
                                                    $img = $product['primary_image'];
                                                    if (filter_var($img, FILTER_VALIDATE_URL) || file_exists($img) || strpos($img, 'assest/') === 0): ?>
                                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="Img" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <span style="font-size: 1.5rem;"><?php echo htmlspecialchars($img); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></div>
                                            </div>
                                        </td>
                                        <td>Rs. <?php echo number_format($product['price'], 0); ?></td>
                                        <td style="max-width: 300px; color: var(--gray); font-size: 0.9rem;"><?php echo htmlspecialchars($product['description']); ?></td>
                                        <td>
                                            <div style="display: flex;">
                                                <a href="admin_edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-edit">Edit</a>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add Product Tab -->
        <div id="add-tab" class="hidden">
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div style="max-width: 600px;">
                        <div class="form-group">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. O/L ICT Guide" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Price (Rs.)</label>
                            <input type="number" name="price" class="form-control" placeholder="e.g. 1500" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Product Images (Max 5)</label>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: var(--gray);">Upload Files:</label>
                                <input type="file" name="image_upload[]" class="form-control" accept="image/*" multiple>
                                <small style="color: var(--gray);">You can select multiple files.</small>
                            </div>
                            
                            <div>
                                <label style="font-size: 0.9rem; color: var(--gray);">Or Image URLs (one per line):</label>
                                <textarea name="image_urls_text" class="form-control" rows="3" placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.png"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Product description..."></textarea>
                        </div>
                        
                        <button type="submit" name="add_product" class="btn">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            document.getElementById('view-tab').classList.add('hidden');
            document.getElementById('add-tab').classList.add('hidden');
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
