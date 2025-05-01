<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a viewer
if (!isLoggedIn() || !isUserType('user')) {
    flashMessage('You must be logged in as a user to view this page.', 'danger');
    redirect('login.php');
}

// Get current user
$user = getCurrentUser();

// Get friends list
$friends = $user->getFriends();

// Handle friend request action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = Database::getInstance();
    
    if ($_POST['action'] === 'send_request' && isset($_POST['friend_username'])) {
        $friendUsername = sanitizeInput($_POST['friend_username']);
        
        // Get user ID by username
        $friendUser = $db->selectOne(
            "SELECT user_id FROM users WHERE username = :username",
            ['username' => $friendUsername]
        );
        
        if ($friendUser) {
            $friendId = $friendUser['user_id'];
            
            // Check if it's the user themselves
            if ($friendId == $user->getId()) {
                flashMessage('You cannot send a friend request to yourself.', 'danger');
            } else {
                // Send friend request
                $result = $user->createFriendRequest($friendId);
                
                if ($result['success']) {
                    flashMessage($result['message'], 'success');
                } else {
                    flashMessage($result['message'], 'danger');
                }
            }
        } else {
            flashMessage('User not found.', 'danger');
        }
        
        redirect('user_friends.php');
    } elseif ($_POST['action'] === 'accept' && isset($_POST['friendship_id'])) {
        $friendshipId = (int)$_POST['friendship_id'];
        
        // Check if friendship exists and user is the recipient
        $friendship = $db->selectOne(
            "SELECT * FROM friends WHERE friendship_id = :id AND friend_id = :user_id AND status = 'pending'",
            ['id' => $friendshipId, 'user_id' => $user->getId()]
        );
        
        if ($friendship) {
            // Accept friend request
            $db->update('friends', 
                ['status' => 'accepted'], 
                'friendship_id = :id', 
                ['id' => $friendshipId]
            );
            
            flashMessage('Friend request accepted.', 'success');
        } else {
            flashMessage('Invalid friend request.', 'danger');
        }
        
        redirect('user_friends.php');
    } elseif ($_POST['action'] === 'decline' && isset($_POST['friendship_id'])) {
        $friendshipId = (int)$_POST['friendship_id'];
        
        // Check if friendship exists and user is the recipient
        $friendship = $db->selectOne(
            "SELECT * FROM friends WHERE friendship_id = :id AND friend_id = :user_id AND status = 'pending'",
            ['id' => $friendshipId, 'user_id' => $user->getId()]
        );
        
        if ($friendship) {
            // Decline friend request
            $db->update('friends', 
                ['status' => 'declined'], 
                'friendship_id = :id', 
                ['id' => $friendshipId]
            );
            
            flashMessage('Friend request declined.', 'success');
        } else {
            flashMessage('Invalid friend request.', 'danger');
        }
        
        redirect('user_friends.php');
    } elseif ($_POST['action'] === 'remove' && isset($_POST['friendship_id'])) {
        $friendshipId = (int)$_POST['friendship_id'];
        
        // Check if friendship exists and involves the current user
        $friendship = $db->selectOne(
            "SELECT * FROM friends WHERE friendship_id = :id AND (user_id = :user_id OR friend_id = :user_id)",
            ['id' => $friendshipId, 'user_id' => $user->getId()]
        );
        
        if ($friendship) {
            // Remove friendship
            $db->delete('friends', 'friendship_id = :id', ['id' => $friendshipId]);
            
            flashMessage('Friend removed successfully.', 'success');
        } else {
            flashMessage('Invalid friendship.', 'danger');
        }
        
        redirect('user_friends.php');
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
                <a href="user_friends.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-users me-2"></i> Friends
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Add Friend Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Add Friend</h5>
                </div>
                <div class="card-body">
                    <form action="user_friends.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="send_request">
                        <div class="col-md-8">
                            <label for="friend_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="friend_username" name="friend_username" placeholder="Enter username" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-user-plus me-2"></i> Send Friend Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Friend Requests -->
            <?php
            $pendingRequests = array_filter($friends, function($friend) {
                return $friend['status'] === 'pending';
            });
            
            if (!empty($pendingRequests)):
            ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Friend Requests</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($pendingRequests as $request): ?>
                            <?php if ($request['user_id'] != $user->getId()): // Show only incoming requests ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <img src="<?php echo $request['profile_picture']; ?>" alt="<?php echo $request['username']; ?>" class="rounded-circle me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0"><?php echo $request['username']; ?></h6>
                                                    <small class="text-muted">Wants to be your friend</small>
                                                </div>
                                            </div>
                                            <form action="user_friends.php" method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="friendship_id" value="<?php echo $request['friendship_id']; ?>">
                                                <button type="submit" name="action" value="accept" class="btn btn-sm btn-success flex-grow-1">
                                                    <i class="fas fa-check me-1"></i> Accept
                                                </button>
                                                <button type="submit" name="action" value="decline" class="btn btn-sm btn-outline-danger flex-grow-1">
                                                    <i class="fas fa-times me-1"></i> Decline
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php
                        // Check if there are any outgoing requests
                        $outgoingRequests = array_filter($pendingRequests, function($request) use ($user) {
                            return $request['user_id'] == $user->getId();
                        });
                        
                        if (!empty($outgoingRequests)):
                        ?>
                            <div class="col-12 mt-3">
                                <h6>Sent Requests</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Sent On</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($outgoingRequests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo $request['profile_picture']; ?>" alt="<?php echo $request['username']; ?>" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                                                            <?php echo $request['username']; ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo formatDate($request['created_at']); ?></td>
                                                    <td>
                                                        <form action="user_friends.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="friendship_id" value="<?php echo $request['friendship_id']; ?>">
                                                            <button type="submit" name="action" value="remove" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-times me-1"></i> Cancel
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Friends List -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Friends</h5>
                    <span class="badge bg-primary rounded-pill">
                        <?php 
                        $acceptedFriends = array_filter($friends, function($friend) {
                            return $friend['status'] === 'accepted';
                        });
                        echo count($acceptedFriends);
                        ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (!empty($acceptedFriends)): ?>
                        <div class="row">
                            <?php foreach ($acceptedFriends as $friend): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <img src="<?php echo $friend['profile_picture']; ?>" alt="<?php echo $friend['username']; ?>" class="rounded-circle me-2" style="width: 60px; height: 60px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0"><?php echo $friend['username']; ?></h6>
                                                    <small class="text-muted">Friends since <?php echo formatDate($friend['created_at'], 'M d, Y'); ?></small>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <a href="#" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-comment me-1"></i> Message
                                                </a>
                                                <form action="user_friends.php" method="POST">
                                                    <input type="hidden" name="friendship_id" value="<?php echo $friend['friendship_id']; ?>">
                                                    <button type="submit" name="action" value="remove" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove this friend?')">
                                                        <i class="fas fa-user-minus me-1"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h5>No Friends Yet</h5>
                            <p class="text-muted">Connect with other art enthusiasts by sending friend requests.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 