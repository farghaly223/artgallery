<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    flashMessage('You must be logged in to view order details.', 'danger');
    redirect('login.php');
}

// Get order ID
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    flashMessage('Order not found.', 'danger');
    
    if (isUserType('user')) {
        redirect('user_purchases.php');
    } else if (isUserType('artist')) {
        redirect('artist_sales.php');
    } else {
        redirect('index.php');
    }
}

// Get order details
$db = Database::getInstance();

// Different query based on user type
if (isUserType('user')) {
    // For regular users, only show their own orders
    $order = $db->selectOne("
        SELECT o.*, a.title, a.description, a.image_path, a.category, a.dimensions, a.material,
            u.username as artist_name, ap.artist_id
        FROM orders o
        JOIN artworks a ON o.artwork_id = a.artwork_id
        JOIN artist_profiles ap ON a.artist_id = ap.artist_id
        JOIN users u ON ap.user_id = u.user_id
        WHERE o.order_id = :order_id AND o.user_id = :user_id
    ", [
        'order_id' => $order_id,
        'user_id' => $_SESSION['user_id']
    ]);
} else if (isUserType('artist')) {
    // For artists, only show orders for their artworks
    $artistProfile = $db->selectOne("
        SELECT artist_id FROM artist_profiles WHERE user_id = :user_id
    ", ['user_id' => $_SESSION['user_id']]);
    
    if (!$artistProfile) {
        flashMessage('Artist profile not found.', 'danger');
        redirect('artist_dashboard.php');
    }
    
    $order = $db->selectOne("
        SELECT o.*, a.title, a.description, a.image_path, a.category, a.dimensions, a.material,
            u2.username as buyer_name, u2.email as buyer_email
        FROM orders o
        JOIN artworks a ON o.artwork_id = a.artwork_id
        JOIN users u2 ON o.user_id = u2.user_id
        WHERE o.order_id = :order_id AND a.artist_id = :artist_id
    ", [
        'order_id' => $order_id,
        'artist_id' => $artistProfile['artist_id']
    ]);
} else if (isUserType('admin')) {
    // For admins, show all orders
    $order = $db->selectOne("
        SELECT o.*, a.title, a.description, a.image_path, a.category, a.dimensions, a.material,
            u.username as artist_name, ap.artist_id, u2.username as buyer_name, u2.email as buyer_email
        FROM orders o
        JOIN artworks a ON o.artwork_id = a.artwork_id
        JOIN artist_profiles ap ON a.artist_id = ap.artist_id
        JOIN users u ON ap.user_id = u.user_id
        JOIN users u2 ON o.user_id = u2.user_id
        WHERE o.order_id = :order_id
    ", ['order_id' => $order_id]);
}

if (!$order) {
    flashMessage('Order not found or you do not have permission to view it.', 'danger');
    
    if (isUserType('user')) {
        redirect('user_purchases.php');
    } else if (isUserType('artist')) {
        redirect('artist_sales.php');
    } else {
        redirect('index.php');
    }
}

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Order Details</h3>
                    <div>
                        <?php if (isUserType('user')): ?>
                            <a href="user_purchases.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Purchases
                            </a>
                        <?php elseif (isUserType('artist')): ?>
                            <a href="artist_sales.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Sales
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-<?php echo $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'danger'); ?> mb-4">
                        Order Status: <strong><?php echo ucfirst($order['status']); ?></strong>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <img src="<?php echo $order['image_path']; ?>" alt="<?php echo $order['title']; ?>" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                        </div>
                        <div class="col-md-8">
                            <h4><?php echo $order['title']; ?></h4>
                            
                            <?php if (isUserType('user')): ?>
                                <h6 class="text-muted">By <?php echo $order['artist_name']; ?></h6>
                            <?php elseif (isUserType('artist') || isUserType('admin')): ?>
                                <h6 class="text-muted">Purchased by <?php echo $order['buyer_name']; ?></h6>
                            <?php endif; ?>
                            
                            <p class="h3 text-primary mt-3"><?php echo formatPrice($order['price']); ?></p>
                            <p><span class="badge bg-secondary"><?php echo $order['category']; ?></span></p>
                            
                            <div class="mt-3">
                                <p class="mb-1"><strong>Order Date:</strong> <?php echo formatDate($order['created_at']); ?></p>
                                <p class="mb-1"><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
                                <p class="mb-0"><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Artwork Details</h5>
                            <p><?php echo nl2br($order['description']); ?></p>
                            
                            <div class="row">
                                <?php if (!empty($order['dimensions'])): ?>
                                    <div class="col-md-6">
                                        <p><strong>Dimensions:</strong> <?php echo $order['dimensions']; ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['material'])): ?>
                                    <div class="col-md-6">
                                        <p><strong>Material:</strong> <?php echo $order['material']; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Shipping Information</h5>
                            <p><?php echo $order['shipping_address']; ?></p>
                        </div>
                    </div>
                    
                    <?php if (isUserType('artist') && $order['status'] === 'pending'): ?>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="d-grid gap-2">
                                    <a href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=completed" class="btn btn-success">
                                        <i class="fas fa-check me-1"></i> Mark as Completed
                                    </a>
                                    <a href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=cancelled" class="btn btn-danger">
                                        <i class="fas fa-times me-1"></i> Cancel Order
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isUserType('user') && $order['status'] === 'completed'): ?>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="d-grid">
                                    <a href="review_artwork.php?artwork_id=<?php echo $order['artwork_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-star me-1"></i> Write a Review
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 