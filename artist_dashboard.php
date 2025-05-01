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
$dashboardData = $artist->getDashboardData();

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row">
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
                <a href="artist_dashboard.php" class="list-group-item list-group-item-action active">
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
                <a href="artist_art_fairs.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-map-marker-alt me-2"></i> Art Fairs
                </a>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h2>Welcome, <?php echo $artist->getUsername(); ?>!</h2>
                    <p class="text-muted">Here's what's happening with your artist account.</p>
                </div>
            </div>
            
            <!-- Stats Cards Section -->
            <div class="row mb-4 dashboard-stats">
                <div class="col-md-3 mb-3">
                    <div class="card card-primary h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h5 class="mb-0"><?php echo $dashboardData['artworksCount']; ?></h5>
                                    <p class="text-muted mb-0">Artworks</p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-palette fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-success h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h5 class="mb-0"><?php echo $dashboardData['subscribersCount']; ?></h5>
                                    <p class="text-muted mb-0">Subscribers</p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-users fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-info h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h5 class="mb-0">
                                        <?php 
                                        $soldCount = 0;
                                        foreach ($dashboardData['artworks'] as $artwork) {
                                            if ($artwork['is_sold'] == 1) {
                                                $soldCount++;
                                            }
                                        }
                                        echo $soldCount;
                                        ?>
                                    </h5>
                                    <p class="text-muted mb-0">Sold Artworks</p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-shopping-cart fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-warning h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h5 class="mb-0"><?php echo formatPrice($dashboardData['balance']); ?></h5>
                                    <p class="text-muted mb-0">Balance</p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Chart Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Sales Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Recent Sales Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Sales</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['recentSales'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Artwork</th>
                                        <th>Buyer</th>
                                        <th>Price</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['recentSales'] as $sale): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $sale['image_path']; ?>" alt="<?php echo $sale['title']; ?>" class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <span><?php echo $sale['title']; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo $sale['buyer_name']; ?></td>
                                            <td><?php echo formatPrice($sale['price']); ?></td>
                                            <td><?php echo formatDate($sale['created_at']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $sale['status'] == 'completed' ? 'success' : ($sale['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($sale['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You haven't made any sales yet.</p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($dashboardData['recentSales'])): ?>
                    <div class="card-footer bg-white text-end">
                        <a href="artist_sales.php" class="btn btn-link text-decoration-none">View all sales</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions Section -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 col-md-3 mb-3">
                            <a href="artist_add_artwork.php" class="text-decoration-none">
                                <div class="p-3 rounded bg-light">
                                    <i class="fas fa-plus-circle fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Add Artwork</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="artist_create_gallery.php" class="text-decoration-none">
                                <div class="p-3 rounded bg-light">
                                    <i class="fas fa-images fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Create Gallery</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="artist_register_fair.php" class="text-decoration-none">
                                <div class="p-3 rounded bg-light">
                                    <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Register Art Fair</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <a href="withdraw_balance.php" class="text-decoration-none">
                                <div class="p-3 rounded bg-light">
                                    <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Withdraw Balance</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales chart
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Sales',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: 'rgba(78, 115, 223, 0.2)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php
// Include footer
include 'views/footer.php';
?> 