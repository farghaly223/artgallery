<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isUserType('admin')) {
    flashMessage('You must be logged in as an admin to view this page.', 'danger');
    redirect('login.php');
}

// Get current user
$admin = getCurrentUser();

// Get artworks
$artworks = $admin->manageArtworks();

// Handle artwork actions
if (isset($_GET['action']) && isset($_GET['artwork_id'])) {
    $artworkId = (int)$_GET['artwork_id'];
    $action = $_GET['action'];
    
    if ($action === 'delete') {
        $result = $admin->deleteArtwork($artworkId);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    } else if ($action === 'feature') {
        $db = Database::getInstance();
        $artwork = $db->selectOne(
            "SELECT * FROM artworks WHERE artwork_id = :artwork_id",
            ['artwork_id' => $artworkId]
        );
        
        if ($artwork) {
            $newFeaturedStatus = $artwork['featured'] ? 0 : 1;
            $db->update('artworks', 
                ['featured' => $newFeaturedStatus], 
                'artwork_id = :artwork_id', 
                ['artwork_id' => $artworkId]
            );
            
            $message = $newFeaturedStatus ? 'Artwork marked as featured.' : 'Artwork removed from featured.';
            flashMessage($message, 'success');
        } else {
            flashMessage('Artwork not found.', 'danger');
        }
    }
    
    redirect('admin_artworks.php');
}

// Include header
include 'views/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?php echo $admin->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="mb-0"><?php echo $admin->getUsername(); ?></h5>
                    <p class="text-muted mb-3"><?php echo getUserTypeName($admin->getUserType()); ?></p>
                </div>
            </div>
            
            <div class="list-group mb-4 shadow-sm">
                <a href="admin_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="admin_users.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Manage Users
                </a>
                <a href="admin_artworks.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-palette me-2"></i> Manage Artworks
                </a>
                <a href="admin_reports.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-flag me-2"></i> Reports
                </a>
                <a href="admin_banned.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-ban me-2"></i> Banned Users
                </a>
          
                <a href="admin_register.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-plus me-2"></i> Create Admin
                </a>
        
                <a href="admin_settings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Manage Artworks</h3>
                    <div>
                        <input type="text" id="artworkSearch" class="form-control form-control-sm" placeholder="Search by title, artist, or category...">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($artworks)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-palette fa-4x text-muted mb-3"></i>
                            <h5>No Artworks Found</h5>
                            <p class="text-muted">There are no artworks in the system.</p>
                        </div>
                    <?php else: ?>
                        <div class="row" id="artworksContainer">
                            <?php foreach ($artworks as $artwork): ?>
                                <div class="col-md-6 col-lg-4 col-xl-3 mb-4 artwork-item" 
                                     data-title="<?php echo strtolower($artwork['title']); ?>" 
                                     data-artist="<?php echo strtolower($artwork['artist_name']); ?>" 
                                     data-category="<?php echo strtolower($artwork['category']); ?>">
                                    <div class="card h-100">
                                        <div class="position-relative">
                                            <img src="<?php echo $artwork['image_path']; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>" style="height: 200px; object-fit: cover;">
                                            <?php if ($artwork['featured']): ?>
                                                <span class="position-absolute top-0 start-0 badge bg-warning m-2">
                                                    <i class="fas fa-star me-1"></i> Featured
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($artwork['is_sold']): ?>
                                                <div class="position-absolute top-0 end-0 badge bg-danger m-2">Sold</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $artwork['title']; ?></h5>
                                            <p class="card-text text-muted">By <?php echo $artwork['artist_name']; ?></p>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-info"><?php echo $artwork['category']; ?></span>
                                                <span class="fw-bold"><?php echo formatPrice($artwork['price']); ?></span>
                                            </div>
                                            <p class="card-text small"><?php echo substr($artwork['description'], 0, 100) . (strlen($artwork['description']) > 100 ? '...' : ''); ?></p>
                                        </div>
                                        <div class="card-footer bg-white border-top-0">
                                            <div class="btn-group w-100">
                                                <a href="artwork.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="?action=feature&artwork_id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-sm btn-outline-warning">
                                                    <?php if ($artwork['featured']): ?>
                                                        <i class="fas fa-star-half-alt"></i> Unfeature
                                                    <?php else: ?>
                                                        <i class="fas fa-star"></i> Feature
                                                    <?php endif; ?>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteArtworkModal" 
                                                   data-artwork-id="<?php echo $artwork['artwork_id']; ?>" 
                                                   data-artwork-title="<?php echo $artwork['title']; ?>">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Artwork Modal -->
<div class="modal fade" id="deleteArtworkModal" tabindex="-1" aria-labelledby="deleteArtworkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteArtworkModalLabel">Delete Artwork</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. The artwork will be permanently deleted from the system.
                </div>
                <p>Are you sure you want to delete <strong id="deleteArtworkTitle"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteArtwork" class="btn btn-danger">Delete Artwork</a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for modal and search functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Artwork Modal
    const deleteArtworkModal = document.getElementById('deleteArtworkModal');
    if (deleteArtworkModal) {
        deleteArtworkModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const artworkId = button.getAttribute('data-artwork-id');
            const artworkTitle = button.getAttribute('data-artwork-title');
            
            document.getElementById('deleteArtworkTitle').textContent = artworkTitle;
            document.getElementById('confirmDeleteArtwork').href = `?action=delete&artwork_id=${artworkId}`;
        });
    }
    
    // Search functionality
    const artworkSearch = document.getElementById('artworkSearch');
    if (artworkSearch) {
        artworkSearch.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const artworkItems = document.querySelectorAll('.artwork-item');
            
            artworkItems.forEach(item => {
                const title = item.getAttribute('data-title');
                const artist = item.getAttribute('data-artist');
                const category = item.getAttribute('data-category');
                
                if (title.includes(searchValue) || artist.includes(searchValue) || category.includes(searchValue)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php
// Include footer
include 'views/footer.php';
?> 