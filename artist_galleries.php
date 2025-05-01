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

// Handle gallery deletion if requested
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $galleryId = (int)$_GET['id'];
    
    // Check if gallery belongs to current artist
    $gallery = $db->selectOne("
        SELECT * FROM virtual_galleries WHERE gallery_id = :gallery_id AND artist_id = :artist_id
    ", [
        'gallery_id' => $galleryId,
        'artist_id' => $artistId
    ]);
    
    if ($gallery) {
        // Delete gallery artworks first (due to foreign key constraints)
        $db->delete('gallery_artworks', 'gallery_id = :gallery_id', ['gallery_id' => $galleryId]);
        
        // Then delete the gallery itself
        $result = $db->delete('virtual_galleries', 'gallery_id = :gallery_id', ['gallery_id' => $galleryId]);
        
        if ($result) {
            flashMessage('Gallery deleted successfully.', 'success');
        } else {
            flashMessage('Failed to delete gallery.', 'danger');
        }
        
        redirect('artist_galleries.php');
    }
}

// Get all galleries by the artist
$galleries = $db->select("
    SELECT vg.*, 
        (SELECT COUNT(*) FROM gallery_artworks WHERE gallery_id = vg.gallery_id) AS artwork_count
    FROM virtual_galleries vg
    WHERE vg.artist_id = :artist_id
    ORDER BY vg.created_at DESC
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
                <a href="artist_subscribers.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> My Subscribers
                </a>
                <a href="artist_galleries.php" class="list-group-item list-group-item-action active">
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
                    <h4 class="mb-0">My Virtual Galleries</h4>
                    <a href="artist_create_gallery.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create New Gallery
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($galleries) > 0): ?>
                        <div class="row">
                            <?php foreach ($galleries as $gallery): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $gallery['title']; ?></h5>
                                            <p class="text-muted small mb-3">
                                                <i class="fas fa-calendar-alt me-1"></i> Created: <?php echo formatDate($gallery['created_at']); ?>
                                            </p>
                                            <p class="card-text"><?php echo substr($gallery['description'], 0, 100) . (strlen($gallery['description']) > 100 ? '...' : ''); ?></p>
                                            <p class="text-muted small">
                                                <i class="fas fa-image me-1"></i> <?php echo $gallery['artwork_count']; ?> artworks
                                            </p>
                                        </div>
                                        <div class="card-footer bg-white border-top-0">
                                            <div class="btn-group w-100">
                                                <a href="view_gallery.php?id=<?php echo $gallery['gallery_id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                                <a href="artist_edit_gallery.php?id=<?php echo $gallery['gallery_id']; ?>" class="btn btn-outline-secondary">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                                <a href="artist_galleries.php?action=delete&id=<?php echo $gallery['gallery_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this gallery?')">
                                                    <i class="fas fa-trash-alt me-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-images fa-4x text-muted mb-3"></i>
                            <h5>No Galleries Yet</h5>
                            <p class="text-muted">You haven't created any virtual galleries yet.</p>
                            <a href="artist_create_gallery.php" class="btn btn-primary mt-3">Create Your First Gallery</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Virtual Gallery Benefits Section -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Why Create a Virtual Gallery?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary-subtle text-primary rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-globe fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5>Global Reach</h5>
                                    <p class="text-muted">Share your art with viewers from around the world without geographical limitations.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-success-subtle text-success rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-brush fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5>Artistic Control</h5>
                                    <p class="text-muted">Curate and arrange your artwork exactly how you want it to be experienced.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-info-subtle text-info rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-link fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5>Easy Sharing</h5>
                                    <p class="text-muted">Share your virtual gallery on social media or with collectors via a simple link.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning-subtle text-warning rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-chart-line fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5>Track Engagement</h5>
                                    <p class="text-muted">See which artworks get the most views and interest from visitors.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 