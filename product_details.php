<?php
include 'db_connect.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM store_products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
}

// Redirect if product not found
if (!$product) {
    header("Location: index.php#store");
    exit;
}

// Get all images
$images = [];
$img_sql = "SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, id ASC";
$img_res = $conn->query($img_sql);
if ($img_res->num_rows > 0) {
    while ($row = $img_res->fetch_assoc()) {
        $images[] = $row['image_url'];
    }
} else {
    // If no images in new table, check old column just in case or use placeholder
    if (!empty($product['image_url'])) {
        $images[] = $product['image_url'];
    } else {
        $images[] = '📦';
    }
}
$main_image = $images[0]; // First one is primary or first added
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - ICT with Dilhara Academy</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/style.css">
    <style>
        /* Product Details Section Specifics */
        .product-details-section {
            padding: 8rem 2rem 4rem; /* Top padding to account for fixed navbar */
            max-width: 1200px;
            margin: 0 auto;
            min-height: 80vh;
        }

        .breadcrumb {
            margin-bottom: 2rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: start;
        }

        .product-image-large {
            background: var(--secondary);
            border-radius: 16px;
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10rem;
            color: var(--dark);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .product-image-large img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: opacity 0.3s ease;
        }

        .product-thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            justify-content: center;
        }

        .thumbnail {
            width: 60px;
            height: 60px;
            border: 2px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.2s;
        }
        
        .thumbnail.active {
            border-color: var(--primary);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info-large h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--dark);
            line-height: 1.2;
        }

        .product-price-large {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .product-description-large {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 3rem;
            line-height: 1.8;
        }

        .action-buttons {
            display: flex;
            gap: 1.5rem;
        }

        .btn-large {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary-large {
            background: var(--primary);
            color: var(--light);
        }

        .btn-primary-large:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary-large {
            background: var(--secondary);
            color: var(--dark);
            border: 1px solid var(--border);
        }

        .btn-secondary-large:hover {
            background: var(--border);
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .product-image-large {
                font-size: 6rem;
            }

            .product-info-large h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <section class="product-details-section">
        <div class="breadcrumb">
            <a href="index.php">Home</a> / <a href="index.php#store">Store</a> / <?php echo htmlspecialchars($product['name']); ?>
        </div>
        
        <div class="product-container">
            <div class="product-gallery-container">
                <div class="product-image-large" id="mainImageContainer">
                    <?php if (filter_var($main_image, FILTER_VALIDATE_URL) || file_exists($main_image) || strpos($main_image, 'assest/') === 0): ?>
                        <img src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="mainImage">
                    <?php else: ?>
                        <span id="mainEmoji" style="font-size: 5rem;"><?php echo htmlspecialchars($main_image); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="product-thumbnails">
                    <?php foreach ($images as $index => $img): ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($img); ?>', this)">
                            <?php if (filter_var($img, FILTER_VALIDATE_URL) || file_exists($img) || strpos($img, 'assest/') === 0): ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Thumbnail">
                            <?php else: ?>
                                <span style="font-size: 2rem; display: flex; align-items: center; justify-content: center; height: 100%;"><?php echo htmlspecialchars($img); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

<script>
    let autoRotateInterval;
    const images = <?php echo json_encode($images); ?>;
    let currentIndex = 0;

    function changeImage(src, thumbnail) {
        // Update Main Image
        const mainContainer = document.getElementById('mainImageContainer');
        const mainImage = document.getElementById('mainImage');
        const mainEmoji = document.getElementById('mainEmoji');

        // Check if src is image or emoji/text
        const isImage = (src.match(/\.(jpeg|jpg|gif|png|webp)$/) != null) || src.startsWith('http') || src.startsWith('assest/');

        if (isImage) {
            if (mainImage) {
                mainImage.style.opacity = 0;
                setTimeout(() => {
                    mainImage.src = src;
                    mainImage.style.opacity = 1;
                }, 200);
            } else {
                // If it was emoji before, we might need to recreate img tag structure but for simplicity assuming consistent types
                 mainContainer.innerHTML = `<img src="${src}" id="mainImage" style="width:100%; height:100%; object-fit:contain; transition: opacity 0.3s ease;">`;
            }
        } else {
             mainContainer.innerHTML = `<span id="mainEmoji" style="font-size: 5rem;">${src}</span>`;
        }

        // Update Thumbnails
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        if (thumbnail) {
            thumbnail.classList.add('active');
            // Find index of this thumbnail
            const thumbs = Array.from(document.querySelectorAll('.thumbnail'));
            currentIndex = thumbs.indexOf(thumbnail);
        }

        // Reset Timer on manual click
        stopAutoRotate();
        startAutoRotate();
    }

    function startAutoRotate() {
        if (images.length <= 1) return;
        
        autoRotateInterval = setInterval(() => {
            currentIndex = (currentIndex + 1) % images.length;
            const thumbnails = document.querySelectorAll('.thumbnail');
            if (thumbnails[currentIndex]) {
                const img = images[currentIndex];
                changeImage(img, thumbnails[currentIndex]);
            }
        }, 3000); // Change every 3 seconds
    }

    function stopAutoRotate() {
        clearInterval(autoRotateInterval);
    }

    // Start on load
    document.addEventListener('DOMContentLoaded', () => {
        startAutoRotate();
    });
</script>
            <div class="product-info-large">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-price-large">Rs. <?php echo number_format($product['price'], 0); ?></div>
                <p class="product-description-large"><?php echo htmlspecialchars($product['description']); ?></p>
                <div class="action-buttons">
                    <button class="btn-large btn-primary-large">Buy Now</button>
                    <button class="btn-large btn-secondary-large">Add to Cart</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Use common partials -->
    <?php include 'ict_section.php'; ?>
    <?php include 'footer.php'; ?>

    <script src="assest/js/main.js"></script>
</body>
</html>
