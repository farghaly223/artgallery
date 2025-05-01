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
    SELECT artist_id, balance FROM artist_profiles WHERE user_id = :user_id
", ['user_id' => $artist->getId()]);

if (!$artistProfile) {
    flashMessage('Artist profile not found.', 'danger');
    redirect('artist_dashboard.php');
}

$artistId = $artistProfile['artist_id'];
$balance = $artistProfile['balance'];

// Get filter parameters
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$dateFilter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

// Build the query
$query = "
    SELECT o.*, a.title, a.image_path, a.price as artwork_price, u.username as buyer_name
    FROM orders o
    JOIN artworks a ON o.artwork_id = a.artwork_id
    JOIN users u ON o.user_id = u.user_id
    WHERE a.artist_id = :artist_id
";

$queryParams = ['artist_id' => $artistId];

if (!empty($status)) {
    $query .= " AND o.status = :status";
    $queryParams['status'] = $status;
}

if (!empty($dateFilter)) {
    switch ($dateFilter) {
        case 'today':
            $query .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $query .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $query .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $query .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

$query .= " ORDER BY o.created_at DESC";

// Get all sales
$sales = $db->select($query, $queryParams);

// Calculate total sales and revenue
$totalSales = count($sales);
$totalRevenue = 0;
$pendingSales = 0;
$completedSales = 0;
$cancelledSales = 0;

foreach ($sales as $sale) {
    if ($sale['status'] === 'completed') {
        $totalRevenue += $sale['price'];
        $completedSales++;
    } else if ($sale['status'] === 'pending') {
        $pendingSales++;
    } else if ($sale['status'] === 'cancelled') {
        $cancelledSales++;
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
                <a href="artist_sales.php" class="list-group-item list-group-item-action active">
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
            
            <!-- Sales Summary Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Sales Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted">Total Sales</h6>
                        <h3><?php echo $totalSales; ?></h3>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted">Total Revenue</h6>
                        <h3><?php echo formatPrice($totalRevenue); ?></h3>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted">Available Balance</h6>
                        <h3><?php echo formatPrice($balance); ?></h3>
                        <a href="withdraw_balance.php" class="btn btn-sm btn-outline-primary mt-2">Withdraw Balance</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <div class="badge bg-primary-subtle text-primary p-2 rounded-circle mb-2">
                                <i class="fas fa-wallet fa-lg"></i>
                            </div>
                            <h5><?php echo $completedSales; ?></h5>
                            <p class="text-muted mb-0">Completed Sales</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <div class="badge bg-warning-subtle text-warning p-2 rounded-circle mb-2">
                                <i class="fas fa-hourglass-half fa-lg"></i>
                            </div>
                            <h5><?php echo $pendingSales; ?></h5>
                            <p class="text-muted mb-0">Pending Sales</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <div class="badge bg-danger-subtle text-danger p-2 rounded-circle mb-2">
                                <i class="fas fa-times-circle fa-lg"></i>
                            </div>
                            <h5><?php echo $cancelledSales; ?></h5>
                            <p class="text-muted mb-0">Cancelled Sales</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Sales History</h4>
                        <div>
                            <a href="sales_export.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i> Export Data
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" action="artist_sales.php" class="row g-3">
                                <div class="col-md-5">
                                    <select name="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <select name="date" class="form-select">
                                        <option value="">All Time</option>
                                        <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                                        <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>This Week</option>
                                        <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>This Month</option>
                                        <option value="year" <?php echo $dateFilter === 'year' ? 'selected' : ''; ?>>This Year</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (count($sales) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Artwork</th>
                                        <th>Buyer</th>
                                        <th>Price</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales as $sale): ?>
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
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="order_details.php?id=<?php echo $sale['order_id']; ?>" class="btn btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($sale['status'] === 'pending'): ?>
                                                    <a href="update_order_status.php?id=<?php echo $sale['order_id']; ?>&status=completed" class="btn btn-outline-success" title="Mark as Completed">
                                                        <i class="fas fa-check"></i>
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
                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                            <h5>No Sales Found</h5>
                            <p class="text-muted">You don't have any sales matching your filter criteria.</p>
                            <?php if (!empty($status) || !empty($dateFilter)): ?>
                                <a href="artist_sales.php" class="btn btn-outline-primary mt-2">View All Sales</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 