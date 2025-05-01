<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    flashMessage('You must be logged in to edit your profile.', 'danger');
    redirect('login.php');
}

// Get current user
$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!validateEmail($email)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Check if username or email already exists (skip current user)
    $db = Database::getInstance();
    $existingUser = $db->selectOne(
        "SELECT * FROM users WHERE (username = :username OR email = :email) AND user_id != :user_id",
        ['username' => $username, 'email' => $email, 'user_id' => $user->getId()]
    );
    
    if ($existingUser) {
        if ($existingUser['username'] == $username) {
            $errors[] = "Username already exists.";
        } else {
            $errors[] = "Email already exists.";
        }
    }
    
    // Handle profile picture upload
    $profile_picture = $user->getProfilePicture();
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
        if (isImage($_FILES['profile_picture'])) {
            $uploadPath = uploadImage($_FILES['profile_picture'], 'assets/img/profiles/');
            if ($uploadPath) {
                $profile_picture = $uploadPath;
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        } else {
            $errors[] = "Please upload a valid image file (JPEG, PNG, GIF).";
        }
    }
    
    // Update password if provided
    $data = [
        'username' => $username,
        'email' => $email,
        'profile_picture' => $profile_picture
    ];
    
    if (!empty($_POST['new_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $userData = $db->selectOne(
            "SELECT password FROM users WHERE user_id = :user_id",
            ['user_id' => $user->getId()]
        );
        
        if (!password_verify($current_password, $userData['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        } else {
            $data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $user->updateProfile($data);
        flashMessage('Profile updated successfully!', 'success');
        redirect('profile.php');
    } else {
        // Set error messages
        foreach ($errors as $error) {
            flashMessage($error, 'danger');
        }
    }
}

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-4 text-center">
                            <img src="<?php echo $user->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <div>
                                <label for="profile_picture" class="btn btn-sm btn-outline-primary">
                                    Change Profile Picture
                                </label>
                                <input type="file" id="profile_picture" name="profile_picture" class="d-none">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo $user->getUsername(); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user->getEmail(); ?>" required>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Change Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <div class="form-text">Leave password fields empty if you don't want to change it.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo $_SERVER['HTTP_REFERER'] ?? 'user_dashboard.php'; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 