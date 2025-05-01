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

// Get recent artworks for selection
$db = Database::getInstance();
$artworks = $db->select("
    SELECT a.*, u.username as artist_name
    FROM artworks a
    JOIN artist_profiles ap ON a.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    ORDER BY a.created_at DESC
    LIMIT 20
");

// Get user's saved screenshots
$screenshots = $db->select("
    SELECT * FROM screenshots 
    WHERE user_id = :user_id
    ORDER BY created_at DESC
", ['user_id' => $user->getId()]);

// Handle screenshot save action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_screenshot'])) {
    if (isset($_POST['image_data'])) {
        $imageData = $_POST['image_data'];
        // Remove the data URL prefix
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        // Decode base64 to binary
        $imageBinary = base64_decode($imageData);
        
        // Generate a unique filename
        $filename = 'virtual_room_' . uniqid() . '.png';
        $directory = 'assets/img/screenshots/';
        
        // Ensure directory exists
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        
        $filePath = $directory . $filename;
        
        // Write the file to disk
        if (file_put_contents($filePath, $imageBinary) !== false) {
            // Save screenshot to database
            $db->insert('screenshots', [
                'user_id' => $user->getId(),
                'image_path' => $filePath
            ]);
            
            flashMessage('Screenshot saved successfully!', 'success');
        } else {
            flashMessage('Failed to save screenshot.', 'danger');
        }
        
        redirect('user_virtual_room.php');
    }
}

// Handle delete screenshot action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $screenshotId = (int)$_GET['delete'];
    
    // Get screenshot details
    $screenshot = $db->selectOne(
        "SELECT * FROM screenshots WHERE screenshot_id = :id AND user_id = :user_id",
        ['id' => $screenshotId, 'user_id' => $user->getId()]
    );
    
    if ($screenshot) {
        // Delete file from disk if it exists
        if (file_exists($screenshot['image_path'])) {
            unlink($screenshot['image_path']);
        }
        
        // Delete from database
        $db->delete('screenshots', 'screenshot_id = :id', ['id' => $screenshotId]);
        
        flashMessage('Screenshot deleted successfully.', 'success');
        redirect('user_virtual_room.php');
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
                <a href="user_subscriptions.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-star me-2"></i> My Subscriptions
                </a>
                <a href="user_virtual_room.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-vr-cardboard me-2"></i> Virtual Room View
                </a>
                <a href="user_request_guidance.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-question-circle me-2"></i> Request Guidance
                </a>
                <a href="user_friends.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Friends
                </a>
            </div>
            
            <!-- Artwork Selection -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Select Artwork</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" id="artworkSearch" class="form-control" placeholder="Search artworks...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="artwork-selection" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($artworks as $artwork): ?>
                            <div class="artwork-item mb-2 p-2 border rounded" data-title="<?php echo $artwork['title']; ?>" data-artist="<?php echo $artwork['artist_name']; ?>" onclick="selectArtwork('<?php echo $artwork['image_path']; ?>', '<?php echo $artwork['title']; ?>', <?php echo $artwork['artwork_id']; ?>)">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>" class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0"><?php echo $artwork['title']; ?></h6>
                                        <small class="text-muted">By <?php echo $artwork['artist_name']; ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Virtual Room Viewer -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Virtual Room View</h4>
                    <div>
                        <button id="zoomIn" class="btn btn-sm btn-outline-secondary me-1">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button id="zoomOut" class="btn btn-sm btn-outline-secondary me-1">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <button id="resetView" class="btn btn-sm btn-outline-secondary me-3">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button id="takeScreenshot" class="btn btn-sm btn-primary">
                            <i class="fas fa-camera me-1"></i> Save Screenshot
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="virtualRoom" class="position-relative" style="background-image: url('assets/img/virtual-room.jpg'); background-size: cover; background-position: center; height: 500px;">
                        <div id="artworkDisplay" class="position-absolute" style="display: none; top: 150px; left: 300px; cursor: move;">
                            <img id="selectedArtwork" src="" alt="Selected Artwork" style="box-shadow: 0 4px 8px rgba(0,0,0,0.2); max-width: 300px; max-height: 300px;">
                        </div>
                    </div>
                    
                    <div id="controls" class="bg-light p-3 text-center">
                        <p id="noArtworkMessage" class="mb-0">Select an artwork from the list to view it in this room.</p>
                        
                        <div id="artworkInfo" style="display: none;">
                            <h5 id="artworkTitle" class="mb-1"></h5>
                            <p class="mb-2">Drag the artwork to position it on the wall.</p>
                            <div class="d-flex justify-content-center">
                                <a id="artworkLink" href="#" class="btn btn-sm btn-outline-primary me-2">View Details</a>
                                <button id="removeArtwork" class="btn btn-sm btn-outline-danger">Remove Artwork</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Saved Screenshots -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Saved Screenshots</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($screenshots)): ?>
                        <div class="row">
                            <?php foreach ($screenshots as $screenshot): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card">
                                        <img src="<?php echo $screenshot['image_path']; ?>" class="card-img-top" alt="Screenshot">
                                        <div class="card-body text-center">
                                            <p class="card-text small text-muted">Taken on <?php echo formatDate($screenshot['created_at']); ?></p>
                                            <div class="btn-group">
                                                <a href="<?php echo $screenshot['image_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">View Full Size</a>
                                                <a href="user_virtual_room.php?delete=<?php echo $screenshot['screenshot_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this screenshot?')">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-camera fa-3x text-muted mb-3"></i>
                            <h5>No Screenshots Yet</h5>
                            <p class="text-muted">Your saved screenshots will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for saving screenshots -->
<form id="screenshotForm" action="user_virtual_room.php" method="POST" style="display: none;">
    <input type="hidden" name="save_screenshot" value="1">
    <input type="hidden" name="image_data" id="imageData">
</form>

<!-- JavaScript for Virtual Room functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    const artworkDisplay = document.getElementById('artworkDisplay');
    const selectedArtwork = document.getElementById('selectedArtwork');
    const noArtworkMessage = document.getElementById('noArtworkMessage');
    const artworkInfo = document.getElementById('artworkInfo');
    const artworkTitle = document.getElementById('artworkTitle');
    const artworkLink = document.getElementById('artworkLink');
    const removeArtworkBtn = document.getElementById('removeArtwork');
    const virtualRoom = document.getElementById('virtualRoom');
    const zoomInBtn = document.getElementById('zoomIn');
    const zoomOutBtn = document.getElementById('zoomOut');
    const resetViewBtn = document.getElementById('resetView');
    const takeScreenshotBtn = document.getElementById('takeScreenshot');
    const screenshotForm = document.getElementById('screenshotForm');
    const imageDataInput = document.getElementById('imageData');
    const artworkSearch = document.getElementById('artworkSearch');
    const clearSearchBtn = document.getElementById('clearSearch');
    const artworkItems = document.querySelectorAll('.artwork-item');
    
    let currentArtworkId = null;
    let scale = 1;
    let isDragging = false;
    let dragOffsetX = 0;
    let dragOffsetY = 0;
    
    // Make artwork draggable
    artworkDisplay.addEventListener('mousedown', startDrag);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', endDrag);
    
    // Search functionality
    artworkSearch.addEventListener('input', filterArtworks);
    clearSearchBtn.addEventListener('click', clearSearch);
    
    // Buttons functionality
    zoomInBtn.addEventListener('click', zoomIn);
    zoomOutBtn.addEventListener('click', zoomOut);
    resetViewBtn.addEventListener('click', resetView);
    removeArtworkBtn.addEventListener('click', removeArtwork);
    takeScreenshotBtn.addEventListener('click', takeScreenshot);
    
    // Functions
    function selectArtwork(imagePath, title, artworkId) {
        selectedArtwork.src = imagePath;
        artworkTitle.textContent = title;
        artworkLink.href = `artwork.php?id=${artworkId}`;
        currentArtworkId = artworkId;
        
        artworkDisplay.style.display = 'block';
        noArtworkMessage.style.display = 'none';
        artworkInfo.style.display = 'block';
        
        // Reset position
        artworkDisplay.style.top = '150px';
        artworkDisplay.style.left = '300px';
        
        // Reset scale
        scale = 1;
        artworkDisplay.style.transform = `scale(${scale})`;
    }
    
    function startDrag(e) {
        isDragging = true;
        const rect = artworkDisplay.getBoundingClientRect();
        dragOffsetX = e.clientX - rect.left;
        dragOffsetY = e.clientY - rect.top;
        artworkDisplay.style.cursor = 'grabbing';
        e.preventDefault();
    }
    
    function drag(e) {
        if (!isDragging) return;
        
        const roomRect = virtualRoom.getBoundingClientRect();
        let newLeft = e.clientX - roomRect.left - dragOffsetX;
        let newTop = e.clientY - roomRect.top - dragOffsetY;
        
        // Keep artwork within room boundaries
        const artworkRect = artworkDisplay.getBoundingClientRect();
        const artworkWidth = artworkRect.width;
        const artworkHeight = artworkRect.height;
        
        newLeft = Math.max(0, Math.min(roomRect.width - artworkWidth, newLeft));
        newTop = Math.max(0, Math.min(roomRect.height - artworkHeight, newTop));
        
        artworkDisplay.style.left = newLeft + 'px';
        artworkDisplay.style.top = newTop + 'px';
    }
    
    function endDrag() {
        isDragging = false;
        artworkDisplay.style.cursor = 'move';
    }
    
    function zoomIn() {
        scale += 0.1;
        if (scale > 2) scale = 2; // Max zoom
        artworkDisplay.style.transform = `scale(${scale})`;
    }
    
    function zoomOut() {
        scale -= 0.1;
        if (scale < 0.5) scale = 0.5; // Min zoom
        artworkDisplay.style.transform = `scale(${scale})`;
    }
    
    function resetView() {
        scale = 1;
        artworkDisplay.style.transform = `scale(${scale})`;
        artworkDisplay.style.top = '150px';
        artworkDisplay.style.left = '300px';
    }
    
    function removeArtwork() {
        artworkDisplay.style.display = 'none';
        noArtworkMessage.style.display = 'block';
        artworkInfo.style.display = 'none';
        currentArtworkId = null;
    }
    
    function takeScreenshot() {
        if (!currentArtworkId) {
            alert('Please select an artwork first.');
            return;
        }
        
        // Use html2canvas library to capture the virtual room
        html2canvas(virtualRoom).then(canvas => {
            // Convert canvas to base64 image data
            const imageData = canvas.toDataURL('image/png');
            imageDataInput.value = imageData;
            screenshotForm.submit();
        });
    }
    
    function filterArtworks() {
        const searchTerm = artworkSearch.value.toLowerCase();
        artworkItems.forEach(item => {
            const title = item.getAttribute('data-title').toLowerCase();
            const artist = item.getAttribute('data-artist').toLowerCase();
            
            if (title.includes(searchTerm) || artist.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    function clearSearch() {
        artworkSearch.value = '';
        artworkItems.forEach(item => {
            item.style.display = 'block';
        });
    }
});
</script>

<!-- Include html2canvas library for screenshot functionality -->
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

<?php include 'views/footer.php'; ?> 