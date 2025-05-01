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

// Get banned users
$db = Database::getInstance();
$bannedUsers = $db->select("
    SELECT *
    FROM users
    WHERE is_banned = 1
    ORDER BY created_at DESC
");

// Handle unban action
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    $action = $_GET['action'];
    
    // Prevent actions on own account
    if ($userId == $admin->getId()) {
        flashMessage('You cannot perform this action on your own account.', 'danger');
        redirect('admin_banned.php');
    }
    
    if ($action === 'unban') {
        $result = $admin->unbanUser($userId);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    }
    
    redirect('admin_banned.php');
}

// Include header
include 'views/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
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
                <a href="admin_dashboard.php" class="list-group-item list-group-item-action">
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
                <a href="admin_banned.php" class="list-group-item list-group-item-action active">
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
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Banned Users</h3>
                    <a href="admin_users.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to All Users
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($bannedUsers)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-ban fa-4x text-muted mb-3"></i>
                            <h5>No Banned Users</h5>
                            <p class="text-muted">There are no banned users in the system.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>User Type</th>
                                        <th>Ban Reason</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bannedUsers as $user): ?>
                                        <tr>
                                            <td><?php echo $user['user_id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $user['profile_picture']; ?>" alt="Profile" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <?php echo $user['username']; ?>
                                                </div>
                                            </td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $user['user_type'] == 'admin' ? 'danger' : 
                                                        ($user['user_type'] == 'artist' ? 'success' : 
                                                        ($user['user_type'] == 'advisor' ? 'info' : 'primary')); 
                                                ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-link p-0" data-bs-toggle="tooltip" data-bs-html="true" title="<?php echo htmlspecialchars($user['ban_reason']); ?>">
                                                    <?php echo substr($user['ban_reason'], 0, 50) . (strlen($user['ban_reason']) > 50 ? '...' : ''); ?>
                                                </button>
                                            </td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="admin_user_details.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?action=unban&user_id=<?php echo $user['user_id']; ?>" class="btn btn-outline-success" onclick="return confirm('Are you sure you want to unban <?php echo $user['username']; ?>?')">
                                                        <i class="fas fa-user-check"></i> Unban
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Initialize tooltips -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
// Include footer
include 'views/footer.php';
?> 