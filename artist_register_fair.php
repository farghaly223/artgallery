<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an artist
if (!isLoggedIn() || !isUserType('artist')) {
    flashMessage('You must be logged in as an artist to register for art fairs.', 'danger');
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

// Check if a specific fair ID was provided
$selectedFairId = null;
$selectedFair = null;

if (isset($_GET['fair_id']) && is_numeric($_GET['fair_id'])) {
    $selectedFairId = (int)$_GET['fair_id'];
    
    // Get fair details
    $selectedFair = $db->selectOne("
        SELECT * FROM art_fairs
        WHERE fair_id = :fair_id AND artist_id IS NULL AND start_date > NOW()
    ", ['fair_id' => $selectedFairId]);
    
    if (!$selectedFair) {
        flashMessage('The selected art fair is not available for registration.', 'danger');
        redirect('artist_art_fairs.php');
    }
}

// Get all upcoming public art fairs
$upcomingFairs = $db->select("
    SELECT * FROM art_fairs
    WHERE start_date > NOW() AND artist_id IS NULL
    ORDER BY start_date ASC
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fairId = isset($_POST['fair_id']) ? (int)$_POST['fair_id'] : 0;
    $customFair = isset($_POST['custom_fair']) && $_POST['custom_fair'] === '1';
    
    $fairName = isset($_POST['fair_name']) ? sanitizeInput($_POST['fair_name']) : '';
    $fairLocation = isset($_POST['fair_location']) ? sanitizeInput($_POST['fair_location']) : '';
    $startDate = isset($_POST['start_date']) ? sanitizeInput($_POST['start_date']) : '';
    $endDate = isset($_POST['end_date']) ? sanitizeInput($_POST['end_date']) : '';
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    $boothNumber = isset($_POST['booth_number']) ? sanitizeInput($_POST['booth_number']) : '';
    
    // Validate input
    $errors = [];
    
    if (!$customFair && $fairId <= 0) {
        $errors[] = 'Please select an art fair or create a custom one';
    }
    
    if ($customFair) {
        if (empty($fairName)) {
            $errors[] = 'Fair name is required';
        }
        
        if (empty($fairLocation)) {
            $errors[] = 'Fair location is required';
        }
        
        if (empty($startDate)) {
            $errors[] = 'Start date is required';
        }
        
        if (empty($endDate)) {
            $errors[] = 'End date is required';
        } else if ($startDate > $endDate) {
            $errors[] = 'End date cannot be before start date';
        }
    }
    
    if (empty($errors)) {
        if ($customFair) {
            // Create a new art fair entry
            $fairId = $db->insert('art_fairs', [
                'artist_id' => $artistId,
                'name' => $fairName,
                'location' => $fairLocation,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'description' => $description,
                'booth_number' => $boothNumber,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($fairId) {
                flashMessage('Custom art fair registered successfully.', 'success');
                redirect('artist_art_fairs.php');
            } else {
                flashMessage('Failed to register for art fair.', 'danger');
            }
        } else {
            // Register for an existing art fair
            // First, verify the fair is still available
            $fair = $db->selectOne("
                SELECT * FROM art_fairs
                WHERE fair_id = :fair_id AND artist_id IS NULL AND start_date > NOW()
            ", ['fair_id' => $fairId]);
            
            if (!$fair) {
                flashMessage('The selected art fair is no longer available for registration.', 'danger');
            } else {
                // Create a copy of the fair with the artist's ID
                $newFairId = $db->insert('art_fairs', [
                    'artist_id' => $artistId,
                    'name' => $fair['name'],
                    'location' => $fair['location'],
                    'start_date' => $fair['start_date'],
                    'end_date' => $fair['end_date'],
                    'description' => $description,
                    'booth_number' => $boothNumber,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($newFairId) {
                    flashMessage('Successfully registered for the art fair.', 'success');
                    redirect('artist_art_fairs.php');
                } else {
                    flashMessage('Failed to register for art fair.', 'danger');
                }
            }
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
                <a href="artist_galleries.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-images me-2"></i> Virtual Galleries
                </a>
                <a href="artist_reviews.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-star me-2"></i> Reviews
                </a>
                <a href="artist_art_fairs.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-map-marker-alt me-2"></i> Art Fairs
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Register for Art Fair</h4>
                    <a href="artist_art_fairs.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Art Fairs
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
                    
                    <form method="POST">
                        <div class="mb-4">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="custom_fair" id="select-existing" value="0" <?php echo (!isset($_POST['custom_fair']) || $_POST['custom_fair'] !== '1') ? 'checked' : ''; ?> onclick="toggleFairType('existing')">
                                <label class="form-check-label" for="select-existing">Select Existing Art Fair</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="custom_fair" id="create-custom" value="1" <?php echo (isset($_POST['custom_fair']) && $_POST['custom_fair'] === '1') ? 'checked' : ''; ?> onclick="toggleFairType('custom')">
                                <label class="form-check-label" for="create-custom">Create Custom Art Fair</label>
                            </div>
                        </div>
                        
                        <!-- Existing Art Fair Selection -->
                        <div id="existing-fair-section" class="mb-4" style="display: <?php echo (!isset($_POST['custom_fair']) || $_POST['custom_fair'] !== '1') ? 'block' : 'none'; ?>;">
                            <?php if (count($upcomingFairs) > 0): ?>
                                <div class="mb-3">
                                    <label for="fair_id" class="form-label">Select Art Fair <span class="text-danger">*</span></label>
                                    <select class="form-select" id="fair_id" name="fair_id">
                                        <option value="">-- Select an Art Fair --</option>
                                        <?php foreach ($upcomingFairs as $fair): ?>
                                            <option value="<?php echo $fair['fair_id']; ?>" <?php echo (isset($selectedFairId) && $selectedFairId === $fair['fair_id']) ? 'selected' : ''; ?>>
                                                <?php echo $fair['name']; ?> - <?php echo $fair['location']; ?> (<?php echo formatDate($fair['start_date']); ?> to <?php echo formatDate($fair['end_date']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i> 
                                        Selecting an existing art fair will register you for that event. You'll be able to add your booth details below.
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    There are no upcoming art fairs available for registration at the moment. You can create a custom art fair instead.
                                </div>
                                <script>
                                    document.getElementById('create-custom').checked = true;
                                    document.getElementById('existing-fair-section').style.display = 'none';
                                    document.getElementById('custom-fair-section').style.display = 'block';
                                </script>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Custom Art Fair Form -->
                        <div id="custom-fair-section" style="display: <?php echo (isset($_POST['custom_fair']) && $_POST['custom_fair'] === '1') ? 'block' : 'none'; ?>;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fair_name" class="form-label">Fair Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="fair_name" name="fair_name" value="<?php echo isset($fairName) ? $fairName : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="fair_location" class="form-label">Location <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="fair_location" name="fair_location" value="<?php echo isset($fairLocation) ? $fairLocation : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($startDate) ? $startDate : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($endDate) ? $endDate : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-text mb-3">
                                <i class="fas fa-info-circle me-1"></i> 
                                Creating a custom art fair is ideal for events you're participating in that aren't listed in our system.
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Common Fields for Both Options -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="booth_number" class="form-label">Booth Number/Location</label>
                                <input type="text" class="form-control" id="booth_number" name="booth_number" value="<?php echo isset($boothNumber) ? $boothNumber : ''; ?>">
                                <div class="form-text">If known, enter your assigned booth number or location at the fair.</div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? $description : ''; ?></textarea>
                                <div class="form-text">Add any special notes about your participation in this art fair.</div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Registering for an art fair in our system helps you keep track of your events and promotes your participation to our community. Make sure to also complete any official registration required by the fair organizers.
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Register for Art Fair</button>
                            <a href="artist_art_fairs.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFairType(type) {
    if (type === 'existing') {
        document.getElementById('existing-fair-section').style.display = 'block';
        document.getElementById('custom-fair-section').style.display = 'none';
    } else {
        document.getElementById('existing-fair-section').style.display = 'none';
        document.getElementById('custom-fair-section').style.display = 'block';
    }
}

// Initialize the form with selected fair if available
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($selectedFairId)): ?>
    document.getElementById('select-existing').checked = true;
    document.getElementById('existing-fair-section').style.display = 'block';
    document.getElementById('custom-fair-section').style.display = 'none';
    <?php endif; ?>
});
</script>

<?php include 'views/footer.php'; ?>