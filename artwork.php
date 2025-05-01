<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Get artwork ID
$artwork_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$artwork_id) {
    flashMessage('Artwork not found.', 'danger');
    redirect('browse.php');
}

// Get artwork details
$db = Database::getInstance();
$artwork = $db->selectOne("
    SELECT a.*, u.username as artist_name
    FROM artworks a
    JOIN artist_profiles ap ON a.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    WHERE a.artwork_id = :artwork_id
", ['artwork_id' => $artwork_id]);

if (!$artwork) {
    flashMessage('Artwork not found.', 'danger');
    redirect('browse.php');
}

include 'views/header.php';
?>

<div class="container">
    <div class="row mt-5">
        <div class="col-md-6">
            <img src="<?php echo $artwork['image_path']; ?>" class="img-fluid rounded shadow" alt="<?php echo $artwork['title']; ?>">
        </div>
        <div class="col-md-6">
            <h1><?php echo $artwork['title']; ?></h1>
            <h5 class="text-muted">By <a href="artist_profile.php?id=<?php echo $artwork['artist_id']; ?>"><?php echo $artwork['artist_name']; ?></a></h5>
            
            <div class="my-4">
                <p class="display-6 text-primary"><?php echo formatPrice($artwork['price']); ?></p>
                <p><span class="badge bg-secondary"><?php echo $artwork['category']; ?></span></p>
            </div>
            
            <div class="mb-4">
                <h5>About this artwork</h5>
                <p><?php echo nl2br($artwork['description']); ?></p>
            </div>
            
            <div class="mb-4">
                <h5>Details</h5>
                <ul class="list-unstyled">
                    <?php if (!empty($artwork['dimensions'])): ?>
                        <li><strong>Dimensions:</strong> <?php echo $artwork['dimensions']; ?></li>
                    <?php endif; ?>
                    <?php if (!empty($artwork['material'])): ?>
                        <li><strong>Materials:</strong> <?php echo $artwork['material']; ?></li>
                    <?php endif; ?>
                    <li><strong>Created:</strong> <?php echo formatDate($artwork['created_at'], 'F Y'); ?></li>
                </ul>
            </div>
            
            <?php if (!$artwork['is_sold']): ?>
                <?php if (isLoggedIn() && $_SESSION['user_type'] == 'user'): ?>
                    <a href="purchase.php?id=<?php echo $artwork_id; ?>" class="btn btn-primary btn-lg mb-2 w-100">Purchase Artwork</a>
                    <a href="virtual_room.php?id=<?php echo $artwork_id; ?>" class="btn btn-outline-secondary mb-2 w-100">View In Your Room</a>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="login.php" class="btn btn-primary btn-lg mb-2 w-100">Log in to Purchase</a>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-secondary">
                    <strong>This artwork has been sold.</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include 'views/footer.php';
?> 