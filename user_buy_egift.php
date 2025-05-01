<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a viewer
if (!isLoggedIn() || !isUserType('user')) {
    flashMessage('You must be logged in as a user to purchase e-gift cards.', 'danger');
    redirect('login.php');
}

// Get current user
$user = getCurrentUser();

// Handle form submission for e-gift purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientEmail = isset($_POST['recipient_email']) ? sanitizeInput($_POST['recipient_email']) : '';
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
    
    // Validate input
    $errors = [];
    
    if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid recipient email address';
    }
    
    if ($amount <= 0 || $amount > 1000) {
        $errors[] = 'Amount must be between 1 and 1000';
    }
    
    if (empty($errors)) {
        // In a real-world scenario, you would process the payment here
        
        // For now, just simulate a successful gift card purchase
        $db = Database::getInstance();
        
        $result = $db->insert('gift_cards', [
            'sender_id' => $user->getId(),
            'recipient_email' => $recipientEmail,
            'amount' => $amount,
            'message' => $message,
            'code' => generateGiftCardCode(),
            'is_redeemed' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // In a real application, you would send an email to the recipient
            flashMessage('E-Gift card has been successfully purchased and sent to ' . $recipientEmail, 'success');
            redirect('user_dashboard.php');
        } else {
            flashMessage('Failed to process your e-gift card purchase. Please try again.', 'danger');
        }
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
                        <img src="<?php echo $user->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <h5 class="mb-0"><?php echo $user->getUsername(); ?></h5>
                    <p class="text-muted mb-3"><?php echo getUserTypeName($user->getUserType()); ?></p>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                </div>
            </div>
            
            <div class="list-group mb-4 shadow-sm">
                <a href="user_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="browse.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-paint-brush me-2"></i> Browse Artworks
                </a>
                <a href="user_purchases.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-shopping-bag me-2"></i> My Purchases
                </a>
                <a href="user_subscriptions.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-star me-2"></i> My Subscriptions
                </a>
                <a href="user_virtual_room.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-vr-cardboard me-2"></i> Virtual Room View
                </a>
                <a href="user_request_guidance.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-question-circle me-2"></i> Request Guidance
                </a>
                <a href="user_friends.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Friends
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Buy E-Gift Card</h4>
                    <a href="user_dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-7">
                            <form method="POST" action="user_buy_egift.php">
                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo $error; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="recipient_email" class="form-label">Recipient Email</label>
                                    <input type="email" class="form-control" id="recipient_email" name="recipient_email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Gift Amount ($)</label>
                                    <select class="form-select" id="amount" name="amount" required>
                                        <option value="">Select an amount</option>
                                        <option value="25">$25</option>
                                        <option value="50">$50</option>
                                        <option value="100">$100</option>
                                        <option value="250">$250</option>
                                        <option value="500">$500</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="message" class="form-label">Personal Message (Optional)</label>
                                    <textarea class="form-control" id="message" name="message" rows="3" placeholder="Add a personal message to the recipient"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">Purchase Gift Card</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-lg-5">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">About E-Gift Cards</h5>
                                    <p class="card-text">Our e-gift cards are the perfect gift for art lovers. Recipients can use them to purchase any artwork on our platform.</p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i> Instantly delivered via email</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Valid for 12 months</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Can be used for any artwork</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Personalized message option</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Function to generate a unique gift card code
function generateGiftCardCode() {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 16));
}

// Include footer
include 'views/footer.php';
?> 