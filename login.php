<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username)) {
        $error = 'Username/email is required';
    } else if (empty($password)) {
        $error = 'Password is required';
    } else {
        $result = User::login($username, $password);
        
        if ($result['success']) {
            // Redirect based on user type
            switch ($_SESSION['user_type']) {
                case 'user':
                    redirect('user_dashboard.php');
                    break;
                case 'artist':
                    redirect('artist_dashboard.php');
                    break;
                case 'admin':
                    redirect('admin_dashboard.php');
                    break;
                case 'advisor':
                    redirect('advisor_dashboard.php');
                    break;
                default:
                    redirect('index.php');
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Include header
include 'views/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Login to ArtConnect</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p>Don't have an account? <a href="register.php">Sign up</a></p>
                        <p><a href="forgot_password.php">Forgot your password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'views/footer.php';
?> 