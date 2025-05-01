<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn() || $_SESSION['user_type'] !== 'user') {
    flashMessage('You must be logged in as a user to use the virtual room feature.', 'danger');
    redirect('login.php');
}

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

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>View Artwork in Room</h1>
            <p class="lead text-muted">See how this artwork will look in different room settings.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="artwork.php?id=<?php echo $artwork_id; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Artwork
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $artwork['title']; ?></h5>
                    <h6 class="text-muted">By <?php echo $artwork['artist_name']; ?></h6>
                    <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>" class="img-fluid rounded mt-3">
                    
                    <div class="mt-3">
                        <p class="h4 text-primary"><?php echo formatPrice($artwork['price']); ?></p>
                        <p><span class="badge bg-secondary"><?php echo $artwork['category']; ?></span></p>
                    </div>
                    
                    <?php if (!$artwork['is_sold']): ?>
                        <div class="d-grid mt-3">
                            <a href="purchase.php?id=<?php echo $artwork_id; ?>" class="btn btn-primary">Purchase Artwork</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mt-3">
                            <strong>This artwork has been sold.</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Virtual Room Preview</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="roomTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="living-tab" data-bs-toggle="tab" data-bs-target="#living" type="button" role="tab" aria-controls="living" aria-selected="true">Living Room</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dining-tab" data-bs-toggle="tab" data-bs-target="#dining" type="button" role="tab" aria-controls="dining" aria-selected="false">Dining Room</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bedroom-tab" data-bs-toggle="tab" data-bs-target="#bedroom" type="button" role="tab" aria-controls="bedroom" aria-selected="false">Bedroom</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="office-tab" data-bs-toggle="tab" data-bs-target="#office" type="button" role="tab" aria-controls="office" aria-selected="false">Office</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="roomTabContent">
                        <div class="tab-pane fade show active" id="living" role="tabpanel" aria-labelledby="living-tab">
                            <div class="virtual-room-container position-relative">
                                <img src="assets/img/rooms/living-room.jpg" class="img-fluid rounded" alt="Living Room">
                                <div class="artwork-overlay">
                                    <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="dining" role="tabpanel" aria-labelledby="dining-tab">
                            <div class="virtual-room-container position-relative">
                                <img src="assets/img/rooms/dining-room.jpg" class="img-fluid rounded" alt="Dining Room">
                                <div class="artwork-overlay" style="top: 30%; left: 35%; width: 25%;">
                                    <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="bedroom" role="tabpanel" aria-labelledby="bedroom-tab">
                            <div class="virtual-room-container position-relative">
                                <img src="assets/img/rooms/bedroom.jpg" class="img-fluid rounded" alt="Bedroom">
                                <div class="artwork-overlay" style="top: 25%; left: 60%; width: 22%;">
                                    <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="office" role="tabpanel" aria-labelledby="office-tab">
                            <div class="virtual-room-container position-relative">
                                <img src="assets/img/rooms/office.jpg" class="img-fluid rounded" alt="Office">
                                <div class="artwork-overlay" style="top: 20%; left: 25%; width: 20%;">
                                    <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Artwork Size Controls</h6>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label for="artworkSize" class="form-label">Size:</label>
                                <input type="range" class="form-range" id="artworkSize" min="10" max="50" value="25">
                            </div>
                            <div class="col-md-8">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="moveLeft">
                                        <i class="fas fa-arrow-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="moveUp">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="moveDown">
                                        <i class="fas fa-arrow-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="moveRight">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Tips for Room Display</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6><i class="fas fa-tape me-2 text-primary"></i> Proper Hanging Height</h6>
                                <p class="small text-muted">For the best visual impact, hang artwork at eye level, typically 57-60 inches from the floor to the center of the piece.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6><i class="fas fa-lightbulb me-2 text-primary"></i> Lighting Considerations</h6>
                                <p class="small text-muted">Proper lighting can enhance your artwork. Consider track lighting or picture lights to highlight the piece.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6><i class="fas fa-ruler-combined me-2 text-primary"></i> Proportional Sizing</h6>
                                <p class="small text-muted">Artwork should be proportional to the wall space. A general rule is that art should take up 2/3 to 3/4 of the wall space above furniture.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <h6><i class="fas fa-palette me-2 text-primary"></i> Color Coordination</h6>
                                <p class="small text-muted">Consider how the colors in the artwork complement or contrast with your room's color scheme for maximum impact.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.virtual-room-container {
    width: 100%;
    margin-bottom: 20px;
}

.artwork-overlay {
    position: absolute;
    top: 25%;
    left: 40%;
    width: 25%;
    height: auto;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.artwork-overlay img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sizeSlider = document.getElementById('artworkSize');
    const moveLeft = document.getElementById('moveLeft');
    const moveRight = document.getElementById('moveRight');
    const moveUp = document.getElementById('moveUp');
    const moveDown = document.getElementById('moveDown');
    const artworkOverlays = document.querySelectorAll('.artwork-overlay');
    
    // Size control
    sizeSlider.addEventListener('input', function() {
        const size = this.value;
        artworkOverlays.forEach(overlay => {
            overlay.style.width = size + '%';
        });
    });
    
    // Position controls
    moveLeft.addEventListener('click', function() {
        artworkOverlays.forEach(overlay => {
            const currentLeft = parseFloat(overlay.style.left || getComputedStyle(overlay).left);
            overlay.style.left = (currentLeft - 5) + '%';
        });
    });
    
    moveRight.addEventListener('click', function() {
        artworkOverlays.forEach(overlay => {
            const currentLeft = parseFloat(overlay.style.left || getComputedStyle(overlay).left);
            overlay.style.left = (currentLeft + 5) + '%';
        });
    });
    
    moveUp.addEventListener('click', function() {
        artworkOverlays.forEach(overlay => {
            const currentTop = parseFloat(overlay.style.top || getComputedStyle(overlay).top);
            overlay.style.top = (currentTop - 5) + '%';
        });
    });
    
    moveDown.addEventListener('click', function() {
        artworkOverlays.forEach(overlay => {
            const currentTop = parseFloat(overlay.style.top || getComputedStyle(overlay).top);
            overlay.style.top = (currentTop + 5) + '%';
        });
    });
    
    // Handle tab changes to ensure the overlay displays correctly
    const roomTabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
    roomTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            const size = sizeSlider.value;
            const activePane = document.querySelector(this.dataset.bsTarget);
            const overlay = activePane.querySelector('.artwork-overlay');
            overlay.style.width = size + '%';
        });
    });
});
</script>

<?php include 'views/footer.php'; ?> 