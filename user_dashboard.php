<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a viewer
if (!isLoggedIn() || !isUserType('user')) {
    flashMessage('You must be logged in as a user to view this page.', 'danger');
    redirect('login.php');
}

// Get current user
$user = getCurrentUser();
$dashboardData = $user->getDashboardData();

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?php echo $user->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <h5 class="mb-0"><?php echo $user->getUsername(); ?></h5>
                    <p class="text-muted mb-3"><?php echo getUserTypeName($user->getUserType()); ?></p>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                </div>
            </div>
            
            <div class="list-group mb-4 shadow-sm">
                <a href="user_dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="browse.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-paint-brush me-2"></i> Browse Artworks
                </a>
                <a href="user_purchases.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-shopping-bag me-2"></i> My Purchases
                </a>
                <a href="user_subscriptions.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-star me-2"></i> My Subscriptions
                </a>
                <a href="user_virtual_room.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-vr-cardboard me-2"></i> Virtual Room View
                </a>
                <a href="user_request_guidance.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-question-circle me-2"></i> Request Guidance
                </a>
                <a href="user_friends.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Friends
                </a>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h2>Welcome, <?php echo $user->getUsername(); ?>!</h2>
                    <p class="text-muted">Here's what's happening with your ArtConnect account.</p>
                </div>
            </div>
            
            <!-- Featured Artworks Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Featured Artworks</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (!empty($dashboardData['recentArtworks'])): ?>
                            <?php foreach (array_slice($dashboardData['recentArtworks'], 0, 3) as $artwork): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <img src="<?php echo $artwork['image_path']; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>" style="height: 180px; object-fit: cover;">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo $artwork['title']; ?></h6>
                                            <p class="card-text text-muted small">By <?php echo $artwork['artist_name']; ?></p>
                                            <p class="card-text fw-bold"><?php echo formatPrice($artwork['price']); ?></p>
                                            <a href="artwork.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-muted">No featured artworks available at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-white text-end">
                    <a href="browse.php" class="btn btn-link text-decoration-none">View all artworks</a>
                </div>
            </div>
            
            <!-- Subscriptions Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">My Subscriptions</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['subscriptions'])): ?>
                        <div class="row">
                            <?php foreach (array_slice($dashboardData['subscriptions'], 0, 3) as $subscription): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo $subscription['username']; ?></h6>
                                            <p class="card-text small"><?php echo substr($subscription['bio'], 0, 100) . (strlen($subscription['bio']) > 100 ? '...' : ''); ?></p>
                                            <a href="artist_profile.php?id=<?php echo $subscription['artist_id']; ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You haven't subscribed to any artists yet.</p>
                        <a href="artists.php" class="btn btn-primary">Discover Artists</a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($dashboardData['subscriptions'])): ?>
                    <div class="card-footer bg-white text-end">
                        <a href="user_subscriptions.php" class="btn btn-link text-decoration-none">View all subscriptions</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Orders Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Purchases</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['recentOrders'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Artwork</th>
                                        <th>Artist</th>
                                        <th>Price</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['recentOrders'] as $order): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $order['image_path']; ?>" alt="<?php echo $order['title']; ?>" class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <span><?php echo $order['title']; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo $order['artist_name']; ?></td>
                                            <td><?php echo formatPrice($order['price']); ?></td>
                                            <td><?php echo formatDate($order['created_at']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You haven't made any purchases yet.</p>
                        <a href="browse.php" class="btn btn-primary">Browse Artworks</a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($dashboardData['recentOrders'])): ?>
                    <div class="card-footer bg-white text-end">
                        <a href="user_purchases.php" class="btn btn-link text-decoration-none">View all purchases</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions Section -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 col-md-3 mb-3">
                            <a href="user_virtual_room.php" class="text-decoration-none">
                                <div class="p-3 rounded bg-light">
                                    <i class="fas fa-vr-cardboard fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Virtual Room</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="user_request_guidance.php" class="text-decoration-none">
                                <div class="p-3 rounded bg-light">
                                    <i class="fas fa-question-circle fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Art Guidance</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="user_buy_egift.php" class="text-decoration-none">
                                <div class="p-3 rounded bg-light">
                                    <i class="fas fa-gift fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Buy eGift</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="user_invite.php" class="text-decoration-none">
                                <div class="p-3 rounded bg-light">
                                    <i class="fas fa-user-plus fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Invite Friends</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'views/footer.php';
?> 