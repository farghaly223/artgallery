<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an artist
if (!isLoggedIn() || !isUserType('artist')) {
    flashMessage('You must be logged in as an artist to view this page.', 'danger');
    redirect('login.php');
}

// Get current user
$artist = getCurrentUser();

// Get artist ID from artist_profiles
$db = Database::getInstance();
$artistProfile = $db->selectOne("
    SELECT artist_id FROM artist_profiles WHERE user_id = :user_id
", ['user_id' => $artist->getId()]);

if (!$artistProfile) {
    flashMessage('Artist profile not found.', 'danger');
    redirect('artist_dashboard.php');
}

$artistId = $artistProfile['artist_id'];

// Get all subscribers
$subscribers = $db->select("
    SELECT s.*, u.username, u.email, u.profile_picture, u.created_at as user_since
    FROM subscriptions s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.artist_id = :artist_id
    ORDER BY s.created_at DESC
", ['artist_id' => $artistId]);

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
                        <img src="<?php echo $artist->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <h5 class="mb-0"><?php echo $artist->getUsername(); ?></h5>
                    <p class="text-muted mb-3"><?php echo getUserTypeName($artist->getUserType()); ?></p>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                </div>
            </div>
            
            <div class="list-group mb-4 shadow-sm">
                <a href="artist_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="artist_artworks.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-palette me-2"></i> My Artworks
                </a>
                <a href="artist_sales.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-line me-2"></i> Sales
                </a>
                <a href="artist_subscribers.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-users me-2"></i> My Subscribers
                </a>
                <a href="artist_galleries.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-images me-2"></i> Virtual Galleries
                </a>
                <a href="artist_reviews.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-star me-2"></i> Reviews
                </a>
                <a href="artist_art_fairs.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-map-marker-alt me-2"></i> Art Fairs
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">My Subscribers</h4>
                    <div>
                        <span class="badge bg-primary"><?php echo count($subscribers); ?> Subscribers</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($subscribers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subscriber</th>
                                        <th>Email</th>
                                        <th>User Since</th>
                                        <th>Subscribed Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscribers as $subscriber): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $subscriber['profile_picture']; ?>" alt="<?php echo $subscriber['username']; ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <span><?php echo $subscriber['username']; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo $subscriber['email']; ?></td>
                                            <td><?php echo formatDate($subscriber['user_since']); ?></td>
                                            <td><?php echo formatDate($subscriber['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="mailto:<?php echo $subscriber['email']; ?>" class="btn btn-outline-primary" title="Contact">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                    <a href="user_profile.php?id=<?php echo $subscriber['user_id']; ?>" class="btn btn-outline-secondary" title="View Profile">
                                                        <i class="fas fa-user"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h5>No Subscribers Yet</h5>
                            <p class="text-muted">You don't have any subscribers yet. As you grow your presence, art enthusiasts will subscribe to your profile.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (count($subscribers) > 0): ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Contact Your Subscribers</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="artist_contact_subscribers.php">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                            <div class="form-text">Your message will be sent to all your subscribers.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 