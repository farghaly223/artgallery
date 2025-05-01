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

// Get existing guidance requests
$db = Database::getInstance();
$guidanceRequests = $db->select("
    SELECT gr.*, u.username as advisor_name, u.profile_picture as advisor_picture
    FROM guidance_requests gr
    LEFT JOIN advisor_profiles ap ON gr.advisor_id = ap.advisor_id
    LEFT JOIN users u ON ap.user_id = u.user_id
    WHERE gr.user_id = :user_id
    ORDER BY gr.created_at DESC
", ['user_id' => $user->getId()]);

// Get recommendations for requests
if (!empty($guidanceRequests)) {
    foreach ($guidanceRequests as $key => $request) {
        $recommendations = $db->select("
            SELECT r.*, a.title, a.image_path, a.price, ap.artist_id, u.username as artist_name
            FROM recommendations r
            JOIN artworks a ON r.artwork_id = a.artwork_id
            JOIN artist_profiles ap ON a.artist_id = ap.artist_id
            JOIN users u ON ap.user_id = u.user_id
            WHERE r.request_id = :request_id
            ORDER BY r.created_at DESC
        ", ['request_id' => $request['request_id']]);
        
        $guidanceRequests[$key]['recommendations'] = $recommendations;
    }
}

// Get available advisors for selection
$advisors = $db->select("
    SELECT ap.advisor_id, u.username, ap.specialization, ap.experience_years
    FROM advisor_profiles ap
    JOIN users u ON ap.user_id = u.user_id
    WHERE u.is_banned = 0
    ORDER BY ap.experience_years DESC
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requirements = sanitizeInput($_POST['requirements']);
    $wallDimensions = sanitizeInput($_POST['wall_dimensions']);
    $preferredStyle = sanitizeInput($_POST['preferred_style']);
    $budgetRange = sanitizeInput($_POST['budget_range']);
    $advisorId = isset($_POST['advisor_id']) ? (int)$_POST['advisor_id'] : null;
    
    // Validate input
    $errors = [];
    
    if (empty($requirements)) {
        $errors[] = "Please describe your requirements.";
    }
    
    // If no errors, submit guidance request
    if (empty($errors)) {
        $requestData = [
            'user_id' => $user->getId(),
            'requirements' => $requirements,
            'wall_dimensions' => $wallDimensions,
            'preferred_style' => $preferredStyle,
            'budget_range' => $budgetRange,
            'status' => 'pending'
        ];
        
        if ($advisorId) {
            $requestData['advisor_id'] = $advisorId;
        }
        
        $result = $user->requestGuidance(
            $requirements,
            $wallDimensions,
            $preferredStyle,
            $budgetRange
        );
        
        if ($result['success']) {
            flashMessage('Your guidance request has been submitted successfully!', 'success');
            redirect('user_request_guidance.php');
        } else {
            flashMessage('Failed to submit guidance request. Please try again.', 'danger');
        }
    } else {
        // Set error messages
        foreach ($errors as $error) {
            flashMessage($error, 'danger');
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
                <a href="user_virtual_room.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-vr-cardboard me-2"></i> Virtual Room View
                </a>
                <a href="user_request_guidance.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-question-circle me-2"></i> Request Guidance
                </a>
                <a href="user_friends.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Friends
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Request Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Request Art Guidance</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Get personalized recommendations from our art advisors. Fill out the form below with your requirements and preferences, and an advisor will curate a selection of artworks tailored to your needs.
                    </p>
                    
                    <form action="user_request_guidance.php" method="POST">
                        <div class="mb-3">
                            <label for="requirements" class="form-label">Tell us what you're looking for</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="4" placeholder="Describe what kind of art you're looking for, the space it will go in, any themes you like, etc." required></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="wall_dimensions" class="form-label">Wall Dimensions (Optional)</label>
                                <input type="text" class="form-control" id="wall_dimensions" name="wall_dimensions" placeholder="e.g., 6ft x 8ft">
                            </div>
                            <div class="col-md-4">
                                <label for="preferred_style" class="form-label">Preferred Style (Optional)</label>
                                <input type="text" class="form-control" id="preferred_style" name="preferred_style" placeholder="e.g., Abstract, Contemporary">
                            </div>
                            <div class="col-md-4">
                                <label for="budget_range" class="form-label">Budget Range (Optional)</label>
                                <input type="text" class="form-control" id="budget_range" name="budget_range" placeholder="e.g., $500-$1000">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="advisor_id" class="form-label">Choose an Advisor (Optional)</label>
                            <select class="form-select" id="advisor_id" name="advisor_id">
                                <option value="">Any available advisor</option>
                                <?php foreach ($advisors as $advisor): ?>
                                    <option value="<?php echo $advisor['advisor_id']; ?>">
                                        <?php echo $advisor['username']; ?> - <?php echo $advisor['specialization']; ?> (<?php echo $advisor['experience_years']; ?> years experience)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Submit Guidance Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Existing Requests -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Your Guidance Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($guidanceRequests)): ?>
                        <div class="accordion" id="requestsAccordion">
                            <?php foreach ($guidanceRequests as $index => $request): ?>
                                <div class="accordion-item mb-3 border rounded">
                                    <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                        <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0">Request #<?php echo $request['request_id']; ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo formatDate($request['created_at']); ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-<?php echo $request['status'] === 'completed' ? 'success' : ($request['status'] === 'in_progress' ? 'warning' : 'secondary'); ?> ms-2">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#requestsAccordion">
                                        <div class="accordion-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <h6>Your Requirements:</h6>
                                                    <p><?php echo $request['requirements']; ?></p>
                                                    
                                                    <?php if (!empty($request['wall_dimensions']) || !empty($request['preferred_style']) || !empty($request['budget_range'])): ?>
                                                        <div class="row">
                                                            <?php if (!empty($request['wall_dimensions'])): ?>
                                                                <div class="col-md-4">
                                                                    <small class="text-muted d-block">Wall Dimensions:</small>
                                                                    <span><?php echo $request['wall_dimensions']; ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($request['preferred_style'])): ?>
                                                                <div class="col-md-4">
                                                                    <small class="text-muted d-block">Preferred Style:</small>
                                                                    <span><?php echo $request['preferred_style']; ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($request['budget_range'])): ?>
                                                                <div class="col-md-4">
                                                                    <small class="text-muted d-block">Budget Range:</small>
                                                                    <span><?php echo $request['budget_range']; ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <?php if ($request['advisor_id'] && !empty($request['advisor_name'])): ?>
                                                        <h6>Assigned Advisor:</h6>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo $request['advisor_picture']; ?>" alt="<?php echo $request['advisor_name']; ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                            <div>
                                                                <span><?php echo $request['advisor_name']; ?></span>
                                                                <small class="text-muted d-block">Art Advisor</small>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-info mb-0">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            <?php if ($request['status'] === 'pending'): ?>
                                                                Waiting for an advisor to be assigned to your request.
                                                            <?php else: ?>
                                                                Your request is being handled by our advisors team.
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($request['recommendations'])): ?>
                                                <h6 class="mt-4 mb-3">Recommendations:</h6>
                                                <div class="row">
                                                    <?php foreach ($request['recommendations'] as $recommendation): ?>
                                                        <div class="col-md-6 col-lg-4 mb-3">
                                                            <div class="card h-100">
                                                                <img src="<?php echo $recommendation['image_path']; ?>" class="card-img-top" alt="<?php echo $recommendation['title']; ?>" style="height: 180px; object-fit: cover;">
                                                                <div class="card-body">
                                                                    <h6 class="card-title"><?php echo $recommendation['title']; ?></h6>
                                                                    <p class="card-text text-muted">By <?php echo $recommendation['artist_name']; ?></p>
                                                                    <p class="card-text fw-bold"><?php echo formatPrice($recommendation['price']); ?></p>
                                                                    <?php if (!empty($recommendation['comment'])): ?>
                                                                        <p class="card-text small"><i class="fas fa-comment me-1"></i> <?php echo $recommendation['comment']; ?></p>
                                                                    <?php endif; ?>
                                                                    <a href="artwork.php?id=<?php echo $recommendation['artwork_id']; ?>" class="btn btn-sm btn-outline-primary w-100">View Details</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php elseif ($request['status'] !== 'pending'): ?>
                                                <div class="alert alert-warning mt-3">
                                                    <i class="fas fa-hourglass-half me-2"></i>
                                                    Our advisor is currently working on your recommendations. Please check back soon.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                            <h5>No Guidance Requests Yet</h5>
                            <p class="text-muted">Submit a request above to get personalized art recommendations from our advisors.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 