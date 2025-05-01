<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isUserType('admin')) {
    flashMessage('You must be logged in as an admin to view this page.', 'danger');
    redirect('login.php');
}

// Get current user
$admin = getCurrentUser();
$dashboardData = $admin->getDashboardData();

// Process report actions
if (isset($_GET['resolve_report']) && isset($_GET['action'])) {
    $reportId = (int)$_GET['resolve_report'];
    $action = $_GET['action'];
    
    if ($action == 'ban' || $action == 'dismiss') {
        $result = $admin->resolveReport($reportId, $action);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    }
    
    redirect('admin_dashboard.php');
}

// Include header
include 'views/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-2">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?php echo $admin->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="mb-0"><?php echo $admin->getUsername(); ?></h5>
                    <p class="text-muted mb-3"><?php echo getUserTypeName($admin->getUserType()); ?></p>
                </div>
            </div>
            
            <div class="list-group mb-4 shadow-sm">
                <a href="admin_dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="admin_users.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Manage Users
                </a>
                <a href="admin_artworks.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-palette me-2"></i> Manage Artworks
                </a>
                <a href="admin_reports.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-flag me-2"></i> Reports
                </a>
                <a href="admin_banned.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-ban me-2"></i> Banned Users
                </a>
            
                <a href="admin_register.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-plus me-2"></i> Create Admin
                </a>
            
                <a href="admin_settings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
            </div>
        </div>
        
        <div class="col-lg-10">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h2>Admin Dashboard</h2>
                    <p class="text-muted">Welcome to the ArtConnect administrative panel.</p>
                </div>
            </div>
            
            <!-- Stats Cards Section -->
            <div class="row mb-4 dashboard-stats">
                <div class="col-md-3 mb-3">
                    <div class="card card-primary h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h5 class="mb-0"><?php echo $dashboardData['userStats']['viewer_count']; ?></h5>
                                    <p class="text-muted mb-0">Users</p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-user fa-2x text-primary"></i>
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
                                    <h5 class="mb-0"><?php echo $dashboardData['userStats']['artist_count']; ?></h5>
                                    <p class="text-muted mb-0">Artists</p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-paint-brush fa-2x text-success"></i>
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
                                    <h5 class="mb-0"><?php echo $dashboardData['artworkStats']['total_artworks']; ?></h5>
                                    <p class="text-muted mb-0">Artworks</p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-palette fa-2x text-info"></i>
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
                                    <h5 class="mb-0"><?php echo formatPrice($dashboardData['artworkStats']['total_sales']); ?></h5>
                                    <p class="text-muted mb-0">Total Sales</p>
                                </div>
                                <div class="col-4 text-end">
                                    <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Reports Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pending Reports</h5>
                    <a href="admin_reports.php" class="btn btn-sm btn-outline-primary">View All Reports</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['pendingReports'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Reporter</th>
                                        <th>Reported User</th>
                                        <th>Reason</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['pendingReports'] as $report): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?php echo $report['reporter_name']; ?></span>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($report['reporter_type']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?php echo $report['reported_name']; ?></span>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($report['reported_type']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo substr($report['reason'], 0, 100) . (strlen($report['reason']) > 100 ? '...' : ''); ?></td>
                                            <td><?php echo formatDate($report['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?resolve_report=<?php echo $report['report_id']; ?>&action=ban" class="btn btn-danger" onclick="return confirm('Are you sure you want to ban this user?')">Ban User</a>
                                                    <a href="?resolve_report=<?php echo $report['report_id']; ?>&action=dismiss" class="btn btn-secondary" onclick="return confirm('Are you sure you want to dismiss this report?')">Dismiss</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No pending reports at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Banned Users Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Banned Users</h5>
                    <a href="admin_banned.php" class="btn btn-sm btn-outline-primary">View All Banned Users</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['bannedUsers'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>User Type</th>
                                        <th>Ban Reason</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($dashboardData['bannedUsers'], 0, 5) as $user): ?>
                                        <tr>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><span class="badge bg-secondary"><?php echo ucfirst($user['user_type']); ?></span></td>
                                            <td><?php echo substr($user['ban_reason'], 0, 100) . (strlen($user['ban_reason']) > 100 ? '...' : ''); ?></td>
                                            <td>
                                                <a href="admin_unban.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to unban this user?')">Unban</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No banned users at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Users Section -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="admin_users.php" class="btn btn-sm btn-outline-primary">View All Users</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboardData['recentUsers'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>User Type</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['recentUsers'] as $user): ?>
                                        <tr>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><span class="badge bg-secondary"><?php echo ucfirst($user['user_type']); ?></span></td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="admin_user_details.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-outline-primary">View</a>
                                                    <?php if ($user['is_banned'] == 0): ?>
                                                        <a href="admin_ban.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to ban this user?')">Ban</a>
                                                    <?php else: ?>
                                                        <a href="admin_unban.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-outline-success" onclick="return confirm('Are you sure you want to unban this user?')">Unban</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No users found.</p>
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