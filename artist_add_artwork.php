<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an artist
if (!isLoggedIn() || !isUserType('artist')) {
    flashMessage('You must be logged in as an artist to add artworks.', 'danger');
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $category = isset($_POST['category']) ? sanitizeInput($_POST['category']) : '';
    $dimensions = isset($_POST['dimensions']) ? sanitizeInput($_POST['dimensions']) : '';
    $material = isset($_POST['material']) ? sanitizeInput($_POST['material']) : '';
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if ($price <= 0) {
        $errors[] = 'Price must be greater than 0';
    }
    
    if (empty($category)) {
        $errors[] = 'Category is required';
    }
    
    // Handle file upload
    $imagePath = 'assets/img/default-artwork.jpg'; // Default image
    
    if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/artworks/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['artwork_image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check file type
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $validExtensions)) {
            $errors[] = 'Only JPG, JPEG, PNG, and GIF files are allowed';
        } else {
            // Try to upload file
            if (move_uploaded_file($_FILES['artwork_image']['tmp_name'], $targetFile)) {
                $imagePath = $targetFile;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    } else if ($_FILES['artwork_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // If there's an error other than no file uploaded
        $errors[] = 'Error uploading image: ' . getFileUploadErrorMessage($_FILES['artwork_image']['error']);
    } else {
        $errors[] = 'Artwork image is required';
    }
    
    if (empty($errors)) {
        // Insert artwork into database
        $result = $db->insert('artworks', [
            'artist_id' => $artistId,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'category' => $category,
            'image_path' => $imagePath,
            'dimensions' => $dimensions,
            'material' => $material,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Update artwork count in artist profile
            $db->query("
                UPDATE artist_profiles 
                SET artworks_count = artworks_count + 1 
                WHERE artist_id = :artist_id
            ", ['artist_id' => $artistId]);
            
            flashMessage('Artwork added successfully.', 'success');
            redirect('artist_artworks.php');
        } else {
            flashMessage('Failed to add artwork.', 'danger');
        }
    }
}

// Helper function for file upload errors
function getFileUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
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
                    <h4 class="mb-0">Add New Artwork</h4>
                    <a href="artist_artworks.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Artworks
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($title) ? $title : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="price" name="price" value="<?php echo isset($price) ? $price : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    <option value="Painting" <?php echo (isset($category) && $category === 'Painting') ? 'selected' : ''; ?>>Painting</option>
                                    <option value="Sculpture" <?php echo (isset($category) && $category === 'Sculpture') ? 'selected' : ''; ?>>Sculpture</option>
                                    <option value="Photography" <?php echo (isset($category) && $category === 'Photography') ? 'selected' : ''; ?>>Photography</option>
                                    <option value="Digital Art" <?php echo (isset($category) && $category === 'Digital Art') ? 'selected' : ''; ?>>Digital Art</option>
                                    <option value="Mixed Media" <?php echo (isset($category) && $category === 'Mixed Media') ? 'selected' : ''; ?>>Mixed Media</option>
                                    <option value="Drawing" <?php echo (isset($category) && $category === 'Drawing') ? 'selected' : ''; ?>>Drawing</option>
                                    <option value="Print" <?php echo (isset($category) && $category === 'Print') ? 'selected' : ''; ?>>Print</option>
                                    <option value="Other" <?php echo (isset($category) && $category === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="dimensions" class="form-label">Dimensions</label>
                                <input type="text" class="form-control" id="dimensions" name="dimensions" placeholder="e.g., 24 x 36 inches" value="<?php echo isset($dimensions) ? $dimensions : ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="material" class="form-label">Material</label>
                                <input type="text" class="form-control" id="material" name="material" placeholder="e.g., Oil on canvas" value="<?php echo isset($material) ? $material : ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="artwork_image" class="form-label">Artwork Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="artwork_image" name="artwork_image" accept="image/*" required>
                                <div class="form-text">Upload a high-quality image of your artwork. Max size: 5MB.</div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($description) ? $description : ''; ?></textarea>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary">Add Artwork</button>
                                <a href="artist_artworks.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 