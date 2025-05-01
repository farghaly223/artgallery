<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an artist
if (!isLoggedIn() || !isUserType('artist')) {
    flashMessage('You must be logged in as an artist to create galleries.', 'danger');
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

// Get all of the artist's artworks for selection
$artworks = $db->select("
    SELECT * FROM artworks 
    WHERE artist_id = :artist_id 
    ORDER BY created_at DESC
", ['artist_id' => $artistId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    $selectedArtworks = isset($_POST['artworks']) ? $_POST['artworks'] : [];
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Gallery title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Gallery description is required';
    }
    
    if (empty($selectedArtworks)) {
        $errors[] = 'Please select at least one artwork for your gallery';
    }
    
    if (empty($errors)) {
        // Insert gallery into database
        $galleryId = $db->insert('virtual_galleries', [
            'artist_id' => $artistId,
            'title' => $title,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($galleryId) {
            // Add artworks to gallery with default positions
            $positionX = 10;
            $positionY = 10;
            
            foreach ($selectedArtworks as $artworkId) {
                $db->insert('gallery_artworks', [
                    'gallery_id' => $galleryId,
                    'artwork_id' => (int)$artworkId,
                    'position_x' => $positionX,
                    'position_y' => $positionY
                ]);
                
                // Increment position for next artwork
                $positionX += 100;
                
                // If position gets too far to the right, move to next row
                if ($positionX > 800) {
                    $positionX = 10;
                    $positionY += 100;
                }
            }
            
            flashMessage('Gallery created successfully.', 'success');
            redirect('artist_edit_gallery.php?id=' . $galleryId);
        } else {
            flashMessage('Failed to create gallery.', 'danger');
        }
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
                    <h4 class="mb-0">Create New Gallery</h4>
                    <a href="artist_galleries.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Galleries
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($artworks)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You need to have artworks to create a gallery. Please add some artworks first.
                        </div>
                        <div class="text-center mt-3">
                            <a href="artist_add_artwork.php" class="btn btn-primary">Add Artwork</a>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Gallery Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($title) ? $title : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Gallery Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo isset($description) ? $description : ''; ?></textarea>
                                <div class="form-text">Provide a brief description of your gallery theme or concept.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Select Artworks <span class="text-danger">*</span></label>
                                <div class="form-text mb-2">Choose artworks to include in your gallery. You can rearrange them later.</div>
                                
                                <div class="row">
                                    <?php foreach ($artworks as $artwork): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100">
                                                <img src="<?php echo $artwork['image_path']; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>" style="height: 150px; object-fit: cover;">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="artworks[]" value="<?php echo $artwork['artwork_id']; ?>" id="artwork-<?php echo $artwork['artwork_id']; ?>" <?php echo (isset($selectedArtworks) && in_array($artwork['artwork_id'], $selectedArtworks)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="artwork-<?php echo $artwork['artwork_id']; ?>">
                                                            <?php echo $artwork['title']; ?>
                                                        </label>
                                                    </div>
                                                    <p class="card-text small text-muted mt-2"><?php echo formatPrice($artwork['price']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Create Gallery</button>
                                <a href="artist_galleries.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 