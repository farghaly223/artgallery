<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Check if any admin users exist in the database
$adminExists = $db->selectOne(
    "SELECT COUNT(*) as admin_count FROM users WHERE user_type = 'admin'"
);

$firstAdminSetup = ($adminExists['admin_count'] == 0);

// Only allow access if user is admin or if no admin exists yet
if (!$firstAdminSetup && (!isLoggedIn() || !isUserType('admin'))) {
    flashMessage('You must be logged in as an administrator to create new admin accounts.', 'danger');
    redirect('login.php');
}

$error = '';
$success = false;

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($username)) {
        $error = 'Username is required';
    } else if (empty($email)) {
        $error = 'Email is required';
    } else if (!validateEmail($email)) {
        $error = 'Invalid email format';
    } else if (empty($password)) {
        $error = 'Password is required';
    } else if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Register user as admin
        
        // Check if username or email already exists
        $existingUser = $db->selectOne(
            "SELECT * FROM users WHERE username = :username OR email = :email",
            ['username' => $username, 'email' => $email]
        );
        
        if ($existingUser) {
            if ($existingUser['username'] == $username) {
                $error = 'Username already exists.';
            } else {
                $error = 'Email already exists.';
            }
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert the new admin user
            $userId = $db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'user_type' => 'admin'
            ]);
            
            if ($userId) {
                $success = true;
                flashMessage('New admin account created successfully!', 'success');
                
                // If it's the first admin, auto-login
                if ($firstAdminSetup) {
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_type'] = 'admin';
                }
            } else {
                $error = 'Failed to create admin account.';
            }
        }
    }
}

// Include header
include 'views/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo $firstAdminSetup ? 'Create First Admin Account' : 'Create New Admin Account'; ?></h4>
                </div>
                <div class="card-body p-5">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php if ($firstAdminSetup): ?>
                                First admin account created successfully! You have been automatically logged in.
                            <?php else: ?>
                                New admin account created successfully!
                            <?php endif; ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="admin_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <?php if (!$firstAdminSetup): ?>
                                <a href="admin_register.php" class="btn btn-outline-secondary ms-2">Create Another Admin</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($firstAdminSetup): ?>
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                No administrator accounts found. Use this form to create the first admin account for your website.
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <?php if (!$firstAdminSetup): ?>
                                <div class="mb-4">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Important:</strong> Admin accounts have full access to the system. Create them only when necessary.
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <?php if (!$firstAdminSetup): ?>
                                    <a href="admin_dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                <?php else: ?>
                                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $firstAdminSetup ? 'Create Admin Account' : 'Create Admin Account'; ?>
                                </button>
                            </div>
                        </form>
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