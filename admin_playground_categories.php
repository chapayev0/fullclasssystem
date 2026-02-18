<?php
session_start();
include 'db_connect.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);
    $is_grade_based = isset($_POST['is_grade_based']) ? 1 : 0;
    $grade_level = $_POST['grade_level'] ?? NULL;
    $display_order = intval($_POST['display_order']) ?? 0;

    if (empty($name)) {
        $error_msg = "Category name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO playground_categories (name, description, icon, is_grade_based, grade_level, display_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisii", $name, $description, $icon, $is_grade_based, $grade_level, $display_order);
        
        if ($stmt->execute()) {
            $success_msg = "Category added successfully!";
        } else {
            if (strpos($conn->error, 'Duplicate entry') !== false) {
                $error_msg = "Category name already exists.";
            } else {
                $error_msg = "Error adding category: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = intval($_POST['category_id']);
    
    $stmt = $conn->prepare("DELETE FROM playground_categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    
    if ($stmt->execute()) {
        $success_msg = "Category deleted successfully!";
    } else {
        $error_msg = "Error deleting category: " . $conn->error;
    }
    $stmt->close();
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = intval($_POST['category_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);
    $is_grade_based = isset($_POST['is_grade_based']) ? 1 : 0;
    $grade_level = $_POST['grade_level'] ?? NULL;
    $display_order = intval($_POST['display_order']) ?? 0;
    $status = $_POST['status'];

    if (empty($name)) {
        $error_msg = "Category name is required.";
    } else {
        $stmt = $conn->prepare("UPDATE playground_categories SET name = ?, description = ?, icon = ?, is_grade_based = ?, grade_level = ?, display_order = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssisisi", $name, $description, $icon, $is_grade_based, $grade_level, $display_order, $status, $category_id);
        
        if ($stmt->execute()) {
            $success_msg = "Category updated successfully!";
        } else {
            $error_msg = "Error updating category: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Status Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $category_id = intval($_POST['category_id']);
    
    $stmt = $conn->prepare("SELECT status FROM playground_categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cat = $result->fetch_assoc();
    $stmt->close();
    
    if ($cat) {
        $new_status = $cat['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE playground_categories SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $category_id);
        
        if ($stmt->execute()) {
            $success_msg = "Category status updated successfully!";
        } else {
            $error_msg = "Error updating status: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all categories
$categories_result = $conn->query("SELECT * FROM playground_categories ORDER BY display_order ASC, created_at DESC");

// Get edit category data if editing
$edit_category = NULL;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM playground_categories WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_category = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Playground Categories | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #7C3AED;
            --dark: #0F172A;
            --light: #F8FAFC;
            --gray: #64748B;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--light);
            color: var(--dark);
        }

        .main-content {
            flex: 1;
            padding: 3rem;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--dark);
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #0052CC;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #6D28D9;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #D97706;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #FEE2E2;
            color: #7F1D1D;
            border-left: 4px solid var(--danger);
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .form-section h2 {
            margin-bottom: 1.5rem;
            color: var(--dark);
            font-size: 1.3rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .grade-select {
            display: none;
        }

        .grade-select.show {
            display: block;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .form-buttons .btn {
            flex: 1;
        }

        .categories-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            grid-column: 1 / -1;
        }

        .categories-section h2 {
            margin-bottom: 1.5rem;
            color: var(--dark);
            font-size: 1.3rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--light);
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #E5E7EB;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #E5E7EB;
        }

        tr:hover {
            background: #FAFBFC;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-inactive {
            background: #FEE2E2;
            color: #7F1D1D;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .icon-select {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .icon-option {
            padding: 0.5rem;
            border: 2px solid #E5E7EB;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .icon-option.selected {
            border-color: var(--primary);
            background: rgba(0, 102, 255, 0.1);
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }

            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-gamepad"></i> Playground Categories</h1>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <div class="container">
            <div class="form-section">
                <h2><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
                
                <form method="POST">
                    <?php if ($edit_category): ?>
                        <input type="hidden" name="edit_category" value="1">
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="add_category" value="1">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" id="name" name="name" required value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Select Icon</label>
                        <div class="icon-select">
                            <div class="icon-option <?php echo ($edit_category && $edit_category['icon'] === 'fa-book') ? 'selected' : ''; ?>" data-icon="fa-book">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="icon-option <?php echo ($edit_category && $edit_category['icon'] === 'fa-code') ? 'selected' : ''; ?>" data-icon="fa-code">
                                <i class="fas fa-code"></i>
                            </div>
                            <div class="icon-option <?php echo ($edit_category && $edit_category['icon'] === 'fa-gamepad') ? 'selected' : ''; ?>" data-icon="fa-gamepad">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <div class="icon-option <?php echo ($edit_category && $edit_category['icon'] === 'fa-calculator') ? 'selected' : ''; ?>" data-icon="fa-calculator">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div class="icon-option <?php echo ($edit_category && $edit_category['icon'] === 'fa-flask') ? 'selected' : ''; ?>" data-icon="fa-flask">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div class="icon-option <?php echo ($edit_category && $edit_category['icon'] === 'fa-brain') ? 'selected' : ''; ?>" data-icon="fa-brain">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div class="icon-option <?php echo ($edit_category && $edit_category['icon'] === 'fa-font') ? 'selected' : ''; ?>" data-icon="fa-font">
                                <i class="fas fa-font"></i>
                            </div>
                        </div>
                        <input type="hidden" id="icon" name="icon" value="<?php echo $edit_category ? htmlspecialchars($edit_category['icon']) : 'fa-gamepad'; ?>">
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_grade_based" name="is_grade_based" <?php echo ($edit_category && $edit_category['is_grade_based']) ? 'checked' : ''; ?>>
                            <label for="is_grade_based" style="margin-bottom: 0;">Is Grade-Based Category?</label>
                        </div>
                    </div>

                    <div class="form-group grade-select <?php echo ($edit_category && $edit_category['is_grade_based']) ? 'show' : ''; ?>">
                        <label for="grade_level">Grade Level</label>
                        <select id="grade_level" name="grade_level">
                            <option value="">Select Grade</option>
                            <?php for ($g = 6; $g <= 13; $g++): ?>
                                <option value="<?php echo $g; ?>" <?php echo ($edit_category && $edit_category['grade_level'] == $g) ? 'selected' : ''; ?>>Grade <?php echo $g; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?php echo $edit_category ? intval($edit_category['display_order']) : 0; ?>">
                    </div>

                    <?php if ($edit_category): ?>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $edit_category['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_category['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                        </button>
                        <?php if ($edit_category): ?>
                            <a href="admin_playground_categories.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="categories-section">
                <h2>All Categories (<?php echo $categories_result->num_rows; ?>)</h2>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cat = $categories_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                            <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $cat['is_grade_based'] ? 'Grade ' . $cat['grade_level'] : 'General'; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $cat['status']; ?>">
                                            <?php echo ucfirst($cat['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-secondary btn-small">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="delete_category" value="1">
                                                <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Icon selection
        document.querySelectorAll('.icon-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('icon').value = this.dataset.icon;
            });
        });

        // Grade-based toggle
        document.getElementById('is_grade_based').addEventListener('change', function() {
            document.querySelector('.grade-select').classList.toggle('show');
        });
    </script>
</body>
</html>
