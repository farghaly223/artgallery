<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn() || $_SESSION['user_type'] !== 'user') {
    flashMessage('You must be logged in as a user to view your purchases.', 'danger');
    redirect('login.php');
}

// Get current user
$user = getCurrentUser();

// Get user's purchases
$db = Database::getInstance();
$purchases = $db->select("
    SELECT o.*, a.title, a.image_path, a.category, u.username as artist_name 
    FROM orders o
    JOIN artworks a ON o.artwork_id = a.artwork_id
    JOIN artist_profiles ap ON a.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    WHERE o.user_id = :user_id
    ORDER BY o.created_at DESC
", ['user_id' => $user->getId()]);

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h1>My Purchases</h1>
            <p class="lead text-muted">Track your artwork orders and purchases.</p>
        </div>
    </div>
    
    <?php if (count($purchases) > 0): ?>
        <div class="row">
            <?php foreach ($purchases as $purchase): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center bg-white">
                            <span class="badge bg-<?php echo $purchase['status'] == 'completed' ? 'success' : ($purchase['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($purchase['status']); ?>
                            </span>
                            <small class="text-muted"><?php echo formatDate($purchase['created_at']); ?></small>
                        </div>
                        <div class="card-body">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <img src="<?php echo $purchase['image_path']; ?>" alt="<?php echo $purchase['title']; ?>" class="rounded" style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                                <div class="ms-3">
                                    <h5 class="card-title mb-1"><?php echo $purchase['title']; ?></h5>
                                    <p class="text-muted small mb-1">By <?php echo $purchase['artist_name']; ?></p>
                                    <p class="text-primary mb-0"><?php echo formatPrice($purchase['price']); ?></p>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="small text-uppercase text-muted mb-2">Order Details</h6>
                                <ul class="list-unstyled small">
                                    <li><strong>Order ID:</strong> #<?php echo $purchase['order_id']; ?></li>
                                    <li><strong>Category:</strong> <?php echo $purchase['category']; ?></li>
                                    <li><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $purchase['payment_method'])); ?></li>
                                </ul>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="small text-uppercase text-muted mb-2">Shipping Address</h6>
                                <p class="small mb-0"><?php echo $purchase['shipping_address']; ?></p>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-grid gap-2">
                                <a href="order_details.php?id=<?php echo $purchase['order_id']; ?>" class="btn btn-outline-primary btn-sm">View Order Details</a>
                                <?php if ($purchase['status'] === 'completed'): ?>
                                    <a href="review_artwork.php?artwork_id=<?php echo $purchase['artwork_id']; ?>" class="btn btn-outline-secondary btn-sm">Write a Review</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8 mx-auto text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-shopping-bag fa-4x text-muted"></i>
                </div>
                <h3>No Purchases Yet</h3>
                <p class="text-muted">You haven't made any purchases yet. Start exploring our collection of artwork!</p>
                <a href="browse.php" class="btn btn-primary mt-3">Browse Artworks</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'views/footer.php'; ?> 