<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an advisor
if (!isLoggedIn() || !isUserType('advisor')) {
    flashMessage('You must be logged in as an art advisor to view this page.', 'danger');
    redirect('login.php');
}

// Get current user
$advisor = getCurrentUser();
$dashboardData = $advisor->getDashboardData();

// Process assign request
if (isset($_GET['assign_request'])) {
    $requestId = (int)$_GET['assign_request'];
    $result = $advisor->assignToRequest($requestId);
    flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    redirect('advisor_dashboard.php');
}

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?php echo $advisor->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <h5 class="mb-0"><?php echo $advisor->getUsername(); ?></h5>
                    <p class="text-muted mb-3"><?php echo getUserTypeName($advisor->getUserType()); ?></p>
                    <p class="mb-1"><strong>Specialization:</strong> <?php echo $advisor->getSpecialization() ?: 'Not specified'; ?></p>
                    <p><strong>Experience:</strong> <?php echo $advisor->getExperienceYears() ?: '0'; ?> years</p>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                </div>
            </div>
            
            <div class="list-group mb-4 shadow-sm">
                <a href="advisor_dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="advisor_requests.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-clipboard-list me-2"></i> Guidance Requests
                </a>
                <a href="advisor_browse_art.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-paint-brush me-2"></i> Browse Artworks
                </a>
                <a href="advisor_recommendations.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-star me-2"></i> My Recommendations
                </a>
                <a href="advisor_completed.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-check-circle me-2"></i> Completed Requests
                </a>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h2>Welcome, <?php echo $advisor->getUsername(); ?>!</h2>
                    <p class="text-muted">Here's what's happening with your art advisor account.</p>
                </div>
            </div>
            
            <!-- Pending Requests Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">New Guidance Requests</h5>
                    <a href="advisor_requests.php" class="btn btn-sm btn-outline-primary">View All Requests</a>
                </div>
                <div class="card-body">
                    <?php
                    $pendingRequests = [];
                    foreach ($dashboardData['pendingRequests'] as $request) {
                        if ($request['advisor_id'] === null) {
                            $pendingRequests[] = $request;
                        }
                    }
                    ?>
                    
                    <?php if (!empty($pendingRequests)): ?>
                        <div class="list-group">
                            <?php foreach ($pendingRequests as $request): ?>
                                <div class="list-group-item list-group-item-action flex-column align-items-start">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Request from <?php echo $request['username']; ?></h5>
                                        <small><?php echo formatDate($request['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>Requirements:</strong> <?php echo substr($request['requirements'], 0, 150) . (strlen($request['requirements']) > 150 ? '...' : ''); ?>
                                    </p>
                                    <div class="mt-2">
                                        <span class="badge bg-info me-2">Wall size: <?php echo $request['wall_dimensions']; ?></span>
                                        <span class="badge bg-primary me-2">Style: <?php echo $request['preferred_style']; ?></span>
                                        <span class="badge bg-success">Budget: <?php echo $request['budget_range']; ?></span>
                                    </div>
                                    <div class="mt-3">
                                        <a href="?assign_request=<?php echo $request['request_id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to take this request?')">Take This Request</a>
                                        <a href="advisor_request_details.php?id=<?php echo $request['request_id']; ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No new guidance requests at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- In Progress Requests Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">My In-Progress Requests</h5>
                </div>
                <div class="card-body">
                    <?php
                    $inProgressRequests = [];
                    foreach ($dashboardData['pendingRequests'] as $request) {
                        if ($request['advisor_id'] == $advisor->getAdvisorId() && $request['status'] == 'in_progress') {
                            $inProgressRequests[] = $request;
                        }
                    }
                    ?>
                    
                    <?php if (!empty($inProgressRequests)): ?>
                        <div class="list-group">
                            <?php foreach ($inProgressRequests as $request): ?>
                                <div class="list-group-item list-group-item-action flex-column align-items-start">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Request from <?php echo $request['username']; ?></h5>
                                        <small><?php echo formatDate($request['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>Requirements:</strong> <?php echo substr($request['requirements'], 0, 150) . (strlen($request['requirements']) > 150 ? '...' : ''); ?>
                                    </p>
                                    <div class="mt-2">
                                        <span class="badge bg-info me-2">Wall size: <?php echo $request['wall_dimensions']; ?></span>
                                        <span class="badge bg-primary me-2">Style: <?php echo $request['preferred_style']; ?></span>
                                        <span class="badge bg-success">Budget: <?php echo $request['budget_range']; ?></span>
                                    </div>
                                    <div class="mt-3">
                                        <a href="advisor_add_recommendation.php?request_id=<?php echo $request['request_id']; ?>" class="btn btn-primary btn-sm">Add Recommendations</a>
                                        <a href="advisor_request_details.php?id=<?php echo $request['request_id']; ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You don't have any requests in progress.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Recommendations Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Recommendations</h5>
                    <a href="advisor_recommendations.php" class="btn btn-sm btn-outline-primary">View All Recommendations</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['recommendedArtworks'])): ?>
                        <div class="row">
                            <?php foreach (array_slice($dashboardData['recommendedArtworks'], 0, 3) as $recommendation): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <img src="<?php echo $recommendation['image_path']; ?>" class="card-img-top" alt="<?php echo $recommendation['title']; ?>" style="height: 180px; object-fit: cover;">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo $recommendation['title']; ?></h6>
                                            <p class="card-text small"><?php echo substr($recommendation['comment'], 0, 100) . (strlen($recommendation['comment']) > 100 ? '...' : ''); ?></p>
                                            <a href="artwork.php?id=<?php echo $recommendation['artwork_id']; ?>" class="btn btn-sm btn-outline-primary">View Artwork</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You haven't made any recommendations yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Completed Requests Section -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recently Completed Requests</h5>
                    <a href="advisor_completed.php" class="btn btn-sm btn-outline-primary">View All Completed</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['completedRequests'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Requirements</th>
                                        <th>Recommendations</th>
                                        <th>Completed Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($dashboardData['completedRequests'], 0, 5) as $request): ?>
                                        <tr>
                                            <td><?php echo $request['username']; ?></td>
                                            <td><?php echo substr($request['requirements'], 0, 100) . (strlen($request['requirements']) > 100 ? '...' : ''); ?></td>
                                            <td>
                                                <?php
                                                // Count recommendations
                                                $count = 0;
                                                foreach ($dashboardData['recommendedArtworks'] as $recommendation) {
                                                    if ($recommendation['request_id'] == $request['request_id']) {
                                                        $count++;
                                                    }
                                                }
                                                echo $count;
                                                ?>
                                            </td>
                                            <td><?php echo formatDate($request['updated_at'] ?? $request['created_at']); ?></td>
                                            <td>
                                                <a href="advisor_request_details.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You haven't completed any requests yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'views/footer.php';
?> 