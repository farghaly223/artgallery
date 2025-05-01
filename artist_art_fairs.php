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

// Get artist's art fairs
$artFairs = $db->select("
    SELECT * FROM art_fairs
    WHERE artist_id = :artist_id
    ORDER BY start_date DESC
", ['artist_id' => $artistId]);

// Get all upcoming public art fairs
$upcomingFairs = $db->select("
    SELECT * FROM art_fairs
    WHERE start_date > NOW() AND artist_id IS NULL
    ORDER BY start_date ASC
    LIMIT 5
");

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
            
            <!-- Upcoming Public Art Fairs -->
            <?php if (count($upcomingFairs) > 0): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Upcoming Art Fairs</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingFairs as $fair): ?>
                            <div class="list-group-item px-0">
                                <h6 class="mb-1"><?php echo $fair['name']; ?></h6>
                                <p class="small mb-1">
                                    <i class="fas fa-map-marker-alt me-1 text-muted"></i> <?php echo $fair['location']; ?>
                                </p>
                                <p class="small mb-2">
                                    <i class="fas fa-calendar me-1 text-muted"></i> 
                                    <?php echo formatDate($fair['start_date']); ?> - <?php echo formatDate($fair['end_date']); ?>
                                </p>
                                <a href="artist_register_fair.php?fair_id=<?php echo $fair['fair_id']; ?>" class="btn btn-sm btn-outline-primary">Register</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="public_art_fairs.php" class="text-decoration-none">View All Upcoming Art Fairs</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">My Art Fairs</h4>
                    <a href="artist_register_fair.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Register for Art Fair
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($artFairs) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Art Fair</th>
                                        <th>Location</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($artFairs as $fair): ?>
                                        <tr>
                                            <td><?php echo $fair['name']; ?></td>
                                            <td><?php echo $fair['location']; ?></td>
                                            <td>
                                                <?php echo formatDate($fair['start_date']); ?> -<br>
                                                <?php echo formatDate($fair['end_date']); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $now = new DateTime();
                                                $startDate = new DateTime($fair['start_date']);
                                                $endDate = new DateTime($fair['end_date']);
                                                
                                                if ($now < $startDate) {
                                                    echo '<span class="badge bg-primary">Upcoming</span>';
                                                } else if ($now >= $startDate && $now <= $endDate) {
                                                    echo '<span class="badge bg-success">Active</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">Past</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="art_fair_details.php?id=<?php echo $fair['fair_id']; ?>" class="btn btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (new DateTime() < new DateTime($fair['start_date'])): ?>
                                                    <a href="edit_art_fair_registration.php?id=<?php echo $fair['fair_id']; ?>" class="btn btn-outline-secondary" title="Edit Registration">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="cancel_art_fair.php?id=<?php echo $fair['fair_id']; ?>" class="btn btn-outline-danger" title="Cancel Registration" onclick="return confirm('Are you sure you want to cancel your registration for this art fair?')">
                                                        <i class="fas fa-times"></i>
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
                            <i class="fas fa-map-marker-alt fa-4x text-muted mb-3"></i>
                            <h5>No Art Fairs Yet</h5>
                            <p class="text-muted">You haven't registered for any art fairs yet.</p>
                            <div class="mt-3">
                                <a href="artist_register_fair.php" class="btn btn-primary">Register for Art Fair</a>
                                <a href="public_art_fairs.php" class="btn btn-outline-secondary ms-2">Browse Upcoming Art Fairs</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Art Fair Benefits Section -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Benefits of Art Fairs</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="feature-icon bg-primary-subtle text-primary rounded-circle p-3 me-3">
                                        <i class="fas fa-users fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5>Expand Your Network</h5>
                                    <p class="text-muted">Connect with collectors, gallery owners, and fellow artists from around the world.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="feature-icon bg-success-subtle text-success rounded-circle p-3 me-3">
                                        <i class="fas fa-dollar-sign fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5>Direct Sales</h5>
                                    <p class="text-muted">Sell your artwork directly to buyers without gallery commissions.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="feature-icon bg-info-subtle text-info rounded-circle p-3 me-3">
                                        <i class="fas fa-star fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5>Build Your Reputation</h5>
                                    <p class="text-muted">Gain recognition and establish yourself in the art community.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="feature-icon bg-warning-subtle text-warning rounded-circle p-3 me-3">
                                        <i class="fas fa-lightbulb fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5>Get Inspired</h5>
                                    <p class="text-muted">Discover new trends, techniques, and ideas for your future artwork.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.feature-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<?php include 'views/footer.php'; ?> 