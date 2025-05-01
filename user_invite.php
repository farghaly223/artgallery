<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a viewer
if (!isLoggedIn() || !isUserType('user')) {
    flashMessage('You must be logged in as a user to invite friends.', 'danger');
    redirect('login.php');
}

// Get current user
$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emails = isset($_POST['emails']) ? sanitizeInput($_POST['emails']) : '';
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
    
    // Validate email addresses
    $emailList = explode(',', $emails);
    $validEmails = [];
    $invalidEmails = [];
    
    foreach ($emailList as $email) {
        $email = trim($email);
        if (!empty($email)) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            } else {
                $invalidEmails[] = $email;
            }
        }
    }
    
    if (!empty($invalidEmails)) {
        flashMessage('The following email addresses are invalid: ' . implode(', ', $invalidEmails), 'danger');
    } else if (empty($validEmails)) {
        flashMessage('Please enter at least one valid email address.', 'danger');
    } else {
        // In a real application, you would send invitation emails here
        // For now, just log the invitations
        $db = Database::getInstance();
        
        foreach ($validEmails as $email) {
            $result = $db->insert('invitations', [
                'user_id' => $user->getId(),
                'email' => $email,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        flashMessage('Invitations have been sent to ' . implode(', ', $validEmails), 'success');
        redirect('user_dashboard.php');
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
                    <h4 class="mb-0">Invite Friends</h4>
                    <a href="user_dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-7">
                            <form method="POST" action="user_invite.php">
                                <div class="mb-4">
                                    <label for="emails" class="form-label">Friend's Email Addresses</label>
                                    <textarea class="form-control" id="emails" name="emails" rows="3" placeholder="Enter email addresses separated by commas" required></textarea>
                                    <div class="form-text">You can enter multiple email addresses separated by commas.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="message" class="form-label">Personal Message (Optional)</label>
                                    <textarea class="form-control" id="message" name="message" rows="3" placeholder="Add a personal message to your invitation"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">Send Invitations</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-lg-5">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Invite Benefits</h5>
                                    <p class="card-text">Share your love for art with friends and family and receive benefits when they join.</p>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-gift text-success me-2"></i> 
                                            <strong>Get $10 credit</strong> when a friend makes their first purchase
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-ticket-alt text-success me-2"></i> 
                                            <strong>Earn exclusive access</strong> to premium art exhibitions
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-award text-success me-2"></i> 
                                            <strong>Become an Art Ambassador</strong> after 5 successful referrals
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="card bg-primary text-white mt-3">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Share Your Referral Link</h5>
                                    <p class="card-text">Copy and share the link below on social media:</p>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" value="https://artconnect.com/refer/<?php echo $user->getUsername(); ?>" id="referralLink" readonly>
                                        <button class="btn btn-light" type="button" onclick="copyReferralLink()">Copy</button>
                                    </div>
                                    <div class="mt-3">
                                        <a href="#" class="btn btn-sm btn-light me-2"><i class="fab fa-facebook-f"></i></a>
                                        <a href="#" class="btn btn-sm btn-light me-2"><i class="fab fa-twitter"></i></a>
                                        <a href="#" class="btn btn-sm btn-light me-2"><i class="fab fa-instagram"></i></a>
                                        <a href="#" class="btn btn-sm btn-light"><i class="fab fa-pinterest-p"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyReferralLink() {
    var copyText = document.getElementById("referralLink");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    
    alert("Referral link copied to clipboard!");
}
</script>

<?php
// Include footer
include 'views/footer.php';
?> 