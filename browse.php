<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Get filter parameters
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 10000;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Create filters array
$filters = [
    'category' => $category,
    'min_price' => $min_price,
    'max_price' => $max_price,
    'search' => $search
];

// Get artworks based on filters
$viewer = new Viewer();
$artworks = $viewer->browseArtworks($filters);

// Get all categories for filter dropdown
$db = Database::getInstance();
$categories = $db->select("SELECT DISTINCT category FROM artworks ORDER BY category");

include 'views/header.php';
?>

<div class="container">
    <h1 class="my-4">Browse Artworks</h1>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="browse.php" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category']; ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>><?php echo $cat['category']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="min_price" class="form-label">Min Price</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" value="<?php echo $min_price; ?>" min="0">
                </div>
                <div class="col-md-3">
                    <label for="max_price" class="form-label">Max Price</label>
                    <input type="number" class="form-control" id="max_price" name="max_price" value="<?php echo $max_price; ?>" min="0">
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo $search; ?>">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="browse.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Artworks Display -->
    <div class="row artwork-grid">
        <?php if (count($artworks) > 0): ?>
            <?php foreach ($artworks as $artwork): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo $artwork['image_path']; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $artwork['title']; ?></h5>
                            <p class="card-text text-muted">By <?php echo $artwork['artist_name']; ?></p>
                            <p class="artwork-price"><?php echo formatPrice($artwork['price']); ?></p>
                            <div class="text-muted small mb-2">
                                Category: <?php echo $artwork['category']; ?>
                            </div>
                            <a href="artwork.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No artworks found matching your criteria.</p>
                <a href="browse.php" class="btn btn-outline-primary">Clear filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'views/footer.php';
?> 