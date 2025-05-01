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

// Handle artwork deletion if requested
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $artworkId = (int)$_GET['id'];
    
    // Check if artwork belongs to current artist
    $artwork = $db->selectOne("
        SELECT * FROM artworks WHERE artwork_id = :artwork_id AND artist_id = :artist_id
    ", [
        'artwork_id' => $artworkId,
        'artist_id' => $artistId
    ]);
    
    if ($artwork) {
        // Delete artwork
        $result = $db->delete('artworks', 'artwork_id = :artwork_id', ['artwork_id' => $artworkId]);
        
        if ($result) {
            // Update artwork count in artist profile
            $db->query("
                UPDATE artist_profiles 
                SET artworks_count = artworks_count - 1 
                WHERE artist_id = :artist_id
            ", ['artist_id' => $artistId]);
            
            flashMessage('Artwork deleted successfully.', 'success');
        } else {
            flashMessage('Failed to delete artwork.', 'danger');
        }
        
        redirect('artist_artworks.php');
    }
}

// Get all artworks by the artist
$artworks = $db->select("
    SELECT * FROM artworks 
    WHERE artist_id = :artist_id 
    ORDER BY created_at DESC
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
                <a href="artist_artworks.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-palette me-2"></i> My Artworks
                </a>
                <a href="artist_sales.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-line me-2"></i> Sales
                </a>
                <a href="artist_subscribers.php" class="list-group-item list-group-item-action">
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
                    <h4 class="mb-0">My Artworks</h4>
                    <a href="artist_add_artwork.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New Artwork
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($artworks) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Price</th>
                                        <th>Category</th>
                                        <th>Date Added</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($artworks as $artwork): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                            </td>
                                            <td><?php echo $artwork['title']; ?></td>
                                            <td><?php echo formatPrice($artwork['price']); ?></td>
                                            <td><?php echo $artwork['category']; ?></td>
                                            <td><?php echo formatDate($artwork['created_at']); ?></td>
                                            <td>
                                                <?php if ($artwork['is_sold']): ?>
                                                    <span class="badge bg-success">Sold</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Available</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($artwork['featured']): ?>
                                                    <span class="badge bg-warning">Featured</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="artwork.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="artist_edit_artwork.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (!$artwork['is_sold']): ?>
                                                        <a href="artist_artworks.php?action=delete&id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this artwork?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-palette fa-4x text-muted mb-3"></i>
                            <h5>No Artworks Yet</h5>
                            <p class="text-muted">You haven't uploaded any artworks yet.</p>
                            <a href="artist_add_artwork.php" class="btn btn-primary mt-3">Add Your First Artwork</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 