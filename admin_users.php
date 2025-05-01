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

// Get all users
$db = Database::getInstance();
$users = $db->select("
    SELECT * FROM users
    ORDER BY created_at DESC
");

// Handle user actions
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    $action = $_GET['action'];
    
    // Prevent actions on own account
    if ($userId == $admin->getId()) {
        flashMessage('You cannot perform this action on your own account.', 'danger');
        redirect('admin_users.php');
    }
    
    if ($action === 'ban') {
        $reason = "Banned by administrator";
        if (isset($_GET['reason']) && !empty($_GET['reason'])) {
            $reason = sanitizeInput($_GET['reason']);
        }
        
        $result = $admin->banUser($userId, $reason);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    } else if ($action === 'unban') {
        $result = $admin->unbanUser($userId);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    } else if ($action === 'delete') {
        // Delete user
        $result = $db->delete('users', 'user_id = :user_id', ['user_id' => $userId]);
        
        if ($result) {
            flashMessage('User deleted successfully.', 'success');
        } else {
            flashMessage('Failed to delete user.', 'danger');
        }
    }
    
    redirect('admin_users.php');
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
                <a href="admin_users.php" class="list-group-item list-group-item-action active">
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
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Manage Users</h3>
                    <div>
                        <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" id="userSearch" class="form-control" placeholder="Search users by username, email or type...">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>User Type</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
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
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <?php if ($user['is_banned']): ?>
                                                <span class="badge bg-danger">Banned</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="admin_user_details.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($user['user_id'] != $admin->getId()): ?>
                                                    <?php if ($user['is_banned']): ?>
                                                        <a href="?action=unban&user_id=<?php echo $user['user_id']; ?>" class="btn btn-outline-success" onclick="return confirm('Are you sure you want to unban this user?')">
                                                            <i class="fas fa-user-check"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="#" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#banModal" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo $user['username']; ?>">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="#" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo $user['username']; ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ban User Modal -->
<div class="modal fade" id="banModal" tabindex="-1" aria-labelledby="banModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="banModalLabel">Ban User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to ban <strong id="banUsername"></strong>?</p>
                <div class="mb-3">
                    <label for="banReason" class="form-label">Reason for banning:</label>
                    <textarea class="form-control" id="banReason" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmBan" class="btn btn-danger">Ban User</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. All data associated with this user will be permanently deleted.
                </div>
                <p>Are you sure you want to delete <strong id="deleteUsername"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete User</a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for modals and search functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ban Modal
    const banModal = document.getElementById('banModal');
    if (banModal) {
        banModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            
            document.getElementById('banUsername').textContent = username;
            
            const confirmBanLink = document.getElementById('confirmBan');
            confirmBanLink.addEventListener('click', function() {
                const reason = document.getElementById('banReason').value;
                window.location.href = `?action=ban&user_id=${userId}&reason=${encodeURIComponent(reason)}`;
            });
        });
    }
    
    // Delete Modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            
            document.getElementById('deleteUsername').textContent = username;
            
            const confirmDeleteLink = document.getElementById('confirmDelete');
            confirmDeleteLink.href = `?action=delete&user_id=${userId}`;
        });
    }
    
    // Search functionality
    const userSearch = document.getElementById('userSearch');
    if (userSearch) {
        userSearch.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const username = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const email = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                const userType = rows[i].getElementsByTagName('td')[3].textContent.toLowerCase();
                
                if (username.includes(searchValue) || email.includes(searchValue) || userType.includes(searchValue)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    }
});
</script>

<?php
// Include footer
include 'views/footer.php';
?> 