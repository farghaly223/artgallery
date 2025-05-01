<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = false;

// Get user type from URL if set
$selectedType = isset($_GET['type']) ? $_GET['type'] : 'user';
if (!in_array($selectedType, ['user', 'artist', 'advisor'])) {
    $selectedType = 'user';
}

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $userType = sanitizeInput($_POST['user_type']);
    
    // Validate inputs
    if (empty($username)) {
        $error = 'Username is required';
    } else if (empty($email)) {
        $error = 'Email is required';
    } else if (!validateEmail($email)) {
        $error = 'Invalid email format';
    } else if (empty($password)) {
        $error = 'Password is required';
    } else if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else if (!in_array($userType, ['user', 'artist', 'advisor'])) {
        $error = 'Invalid user type';
    } else {
        // Register user
        $result = User::register($username, $email, $password, $userType);
        
        if ($result['success']) {
            // Auto-login after registration
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $userType;
            
            // Redirect based on user type
            switch ($userType) {
                case 'user':
                    redirect('user_dashboard.php');
                    break;
                case 'artist':
                    redirect('artist_dashboard.php');
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
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Create an Account</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Registration successful! You can now <a href="login.php">log in</a>.
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-4">
                                <label class="form-label d-block">I am registering as a:</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="user_type" id="type-user" value="user" <?php echo $selectedType == 'user' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="type-user">Art Lover</label>
                                    
                                    <input type="radio" class="btn-check" name="user_type" id="type-artist" value="artist" <?php echo $selectedType == 'artist' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="type-artist">Artist</label>
                                    
                                    <input type="radio" class="btn-check" name="user_type" id="type-advisor" value="advisor" <?php echo $selectedType == 'advisor' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="type-advisor">Art Advisor</label>
                                </div>
                            </div>
                            
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
                                    <div class="form-text">Password must be at least 6 characters long.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Already have an account? <a href="login.php">Log in</a></p>
                        </div>
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