<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Get artist ID
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    flashMessage('Artist not found.', 'danger');
    redirect('artists.php');
}

// Get artist details
$db = Database::getInstance();
$artist = $db->selectOne("
    SELECT u.*, ap.bio, ap.artist_id
    FROM users u
    JOIN artist_profiles ap ON u.user_id = ap.user_id
    WHERE u.user_id = :user_id AND u.user_type = 'artist'
", ['user_id' => $user_id]);

if (!$artist) {
    flashMessage('Artist not found.', 'danger');
    redirect('artists.php');
}

// Get artist's artworks
$artworks = $db->select("
    SELECT *
    FROM artworks
    WHERE artist_id = :artist_id
    ORDER BY created_at DESC
", ['artist_id' => $artist['artist_id']]);

// Check if the user is already subscribed to this artist
$isSubscribed = false;
if (isLoggedIn() && $_SESSION['user_type'] == 'user') {
    $subscription = $db->selectOne("
        SELECT * FROM subscriptions 
        WHERE user_id = :user_id AND artist_id = :artist_id
    ", [
        'user_id' => $_SESSION['user_id'],
        'artist_id' => $artist['artist_id']
    ]);
    
    if ($subscription) {
        $isSubscribed = true;
    }
}

include 'views/header.php';
?>

<div class="container">
    <div class="artist-profile-header py-5">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <img src="<?php echo $artist['profile_picture']; ?>" class="rounded-circle img-fluid mb-3" style="max-width: 150px;" alt="<?php echo $artist['username']; ?>">
            </div>
            <div class="col-md-9">
                <h1><?php echo $artist['username']; ?></h1>
                <p class="lead"><?php echo !empty($artist['bio']) ? $artist['bio'] : 'No biography available.'; ?></p>
                
                <?php if (isLoggedIn() && $_SESSION['user_type'] == 'user'): ?>
                    <div class="mt-3">
                        <?php if ($isSubscribed): ?>
                            <a href="user_subscriptions.php?unsubscribe=<?php echo $artist['artist_id']; ?>" class="btn btn-outline-danger me-2" onclick="return confirm('Are you sure you want to unsubscribe from this artist?')">Unsubscribe</a>
                        <?php else: ?>
                            <a href="subscribe.php?artist_id=<?php echo $artist['user_id']; ?>" class="btn btn-primary me-2">Subscribe</a>
                        <?php endif; ?>
                        <a href="#" class="btn btn-outline-secondary">Contact Artist</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <h2 class="my-4">Artworks by <?php echo $artist['username']; ?></h2>
    
    <div class="row">
        <?php if (count($artworks) > 0): ?>
            <?php foreach ($artworks as $artwork): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo $artwork['image_path']; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $artwork['title']; ?></h5>
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
                <p class="text-muted">This artist has not uploaded any artworks yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'views/footer.php';
?> 