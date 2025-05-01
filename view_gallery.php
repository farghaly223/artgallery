<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Get gallery ID
$gallery_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$gallery_id) {
    flashMessage('Gallery not found.', 'danger');
    redirect('galleries.php');
}

// Get gallery details
$db = Database::getInstance();
$gallery = $db->selectOne("
    SELECT vg.*, u.username, u.profile_picture
    FROM virtual_galleries vg
    JOIN artist_profiles ap ON vg.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    WHERE vg.gallery_id = :gallery_id
", ['gallery_id' => $gallery_id]);

if (!$gallery) {
    flashMessage('Gallery not found.', 'danger');
    redirect('galleries.php');
}

// Get gallery artworks
$artworks = $db->select("
    SELECT ga.*, a.*
    FROM gallery_artworks ga
    JOIN artworks a ON ga.artwork_id = a.artwork_id
    WHERE ga.gallery_id = :gallery_id
", ['gallery_id' => $gallery_id]);

include 'views/header.php';
?>

<div class="container">
    <div class="my-4">
        <h1><?php echo $gallery['title']; ?></h1>
        <div class="d-flex align-items-center mb-3">
            <img src="<?php echo $gallery['profile_picture']; ?>" class="rounded-circle me-2" width="40" height="40" alt="<?php echo $gallery['username']; ?>">
            <span>Created by <a href="artist_profile.php?id=<?php echo $gallery['artist_id']; ?>"><?php echo $gallery['username']; ?></a></span>
        </div>
        <p class="lead"><?php echo !empty($gallery['description']) ? $gallery['description'] : 'No gallery description available.'; ?></p>
    </div>
    
    <div class="card mb-5">
        <div class="card-body p-5 text-center">
            <h5 class="mb-4">Virtual Gallery Viewer</h5>
            <?php if (count($artworks) > 0): ?>
                <div class="bg-light p-5 text-center" style="min-height: 400px;">
                    <p>Virtual gallery viewer would be displayed here.</p>
                    <p class="text-muted">This gallery contains <?php echo count($artworks); ?> artwork(s).</p>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    This gallery doesn't have any artworks yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <h3 class="mb-4">Artworks in this Gallery</h3>
    <div class="row">
        <?php if (count($artworks) > 0): ?>
            <?php foreach ($artworks as $artwork): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo $artwork['image_path']; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $artwork['title']; ?></h5>
                            <p class="artwork-price"><?php echo formatPrice($artwork['price']); ?></p>
                            <a href="artwork.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No artworks in this gallery yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'views/footer.php';
?> 