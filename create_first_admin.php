<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

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
                flashMessage('Admin account created successfully!', 'success');
                
                // Auto-login with new admin account
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = 'admin';
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
                    <h4 class="mb-0">Create Admin Account</h4>
                </div>
                <div class="card-body p-5">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Admin account created successfully! You have been automatically logged in.
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Would you like to delete the default admin account?</strong><br>
                            If the default admin account exists, we recommend deleting it for security reasons.
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="delete_default_admin.php" class="btn btn-danger me-2">Delete Default Admin</a>
                            <a href="admin_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Welcome!</strong> Use this form to create a new administrator account for your website.
                        </div>
                        
                        <form method="post" action="">
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
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Create Admin Account</button>
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