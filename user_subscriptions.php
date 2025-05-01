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

// Get user subscriptions
$db = Database::getInstance();
$subscriptions = $db->select("
    SELECT s.*, u.username, u.profile_picture, ap.bio, ap.artworks_count, ap.subscribers_count
    FROM subscriptions s
    JOIN artist_profiles ap ON s.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    WHERE s.user_id = :user_id
    ORDER BY s.created_at DESC
", ['user_id' => $user->getId()]);

// Handle unsubscribe action
if (isset($_GET['unsubscribe']) && is_numeric($_GET['unsubscribe'])) {
    $artistId = (int)$_GET['unsubscribe'];
    
    // Check if subscription exists
    $subscription = $db->selectOne(
        "SELECT * FROM subscriptions WHERE user_id = :user_id AND artist_id = :artist_id",
        ['user_id' => $user->getId(), 'artist_id' => $artistId]
    );
    
    if ($subscription) {
        // Delete subscription
        $db->delete('subscriptions', 'subscription_id = :id', ['id' => $subscription['subscription_id']]);
        
        // Update artist subscribers count
        $db->query(
            "UPDATE artist_profiles SET subscribers_count = subscribers_count - 1 WHERE artist_id = :artist_id",
            ['artist_id' => $artistId]
        );
        
        flashMessage('Successfully unsubscribed from artist.', 'success');
        redirect('user_subscriptions.php');
    }
}

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
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
                <a href="user_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="browse.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-paint-brush me-2"></i> Browse Artworks
                </a>
                <a href="user_purchases.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-shopping-bag me-2"></i> My Purchases
                </a>
                <a href="user_subscriptions.php" class="list-group-item list-group-item-action active">
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
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">My Subscriptions</h4>
                    <a href="artists.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Discover Artists
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($subscriptions)): ?>
                        <div class="row">
                            <?php foreach ($subscriptions as $subscription): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-white text-center">
                                            <img src="<?php echo $subscription['profile_picture']; ?>" alt="<?php echo $subscription['username']; ?>" class="rounded-circle img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                                            <h5 class="mt-2 mb-0"><?php echo $subscription['username']; ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-around mb-3">
                                                <div class="text-center">
                                                    <h6 class="mb-0"><?php echo $subscription['artworks_count']; ?></h6>
                                                    <small class="text-muted">Artworks</small>
                                                </div>
                                                <div class="text-center">
                                                    <h6 class="mb-0"><?php echo $subscription['subscribers_count']; ?></h6>
                                                    <small class="text-muted">Subscribers</small>
                                                </div>
                                            </div>
                                            <p class="card-text small mb-3"><?php echo substr($subscription['bio'], 0, 100) . (strlen($subscription['bio']) > 100 ? '...' : ''); ?></p>
                                            <div class="d-flex justify-content-between">
                                                <a href="artist_profile.php?id=<?php echo $subscription['artist_id']; ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                                                <a href="user_subscriptions.php?unsubscribe=<?php echo $subscription['artist_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to unsubscribe from this artist?')">Unsubscribe</a>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white text-muted small">
                                            <i class="fas fa-clock me-1"></i> Subscribed on <?php echo formatDate($subscription['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-star fa-4x text-muted mb-3"></i>
                            <h5>No Subscriptions Yet</h5>
                            <p class="text-muted">You haven't subscribed to any artists yet.</p>
                            <a href="artists.php" class="btn btn-primary mt-3">Discover Artists</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 