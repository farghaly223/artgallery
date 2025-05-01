<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Get search parameter
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Get all artists
$db = Database::getInstance();
$sql = "
    SELECT u.user_id, u.username, u.profile_picture, ap.bio
    FROM users u
    JOIN artist_profiles ap ON u.user_id = ap.user_id
    WHERE u.user_type = 'artist' AND u.is_banned = 0
";

$params = [];
if (!empty($search)) {
    $sql .= " AND (u.username LIKE :search OR ap.bio LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY u.username";
$artists = $db->select($sql, $params);

include 'views/header.php';
?>

<div class="container">
    <h1 class="my-4">Discover Artists</h1>
    
    <!-- Search -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="artists.php" method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search artists by name or bio..." value="<?php echo $search; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Artists List -->
    <div class="row">
        <?php if (count($artists) > 0): ?>
            <?php foreach ($artists as $artist): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $artist['profile_picture']; ?>" class="rounded-circle me-3" width="60" height="60" alt="<?php echo $artist['username']; ?>">
                                <div>
                                    <h5 class="card-title mb-0"><?php echo $artist['username']; ?></h5>
                                    <div class="text-muted small">Artist</div>
                                </div>
                            </div>
                            <p class="card-text mb-3">
                                <?php 
                                $bio = !empty($artist['bio']) ? $artist['bio'] : 'No biography available.';
                                echo strlen($bio) > 150 ? substr($bio, 0, 150) . '...' : $bio;
                                ?>
                            </p>
                            <div class="d-grid gap-2">
                                <a href="artist_profile.php?id=<?php echo $artist['user_id']; ?>" class="btn btn-outline-primary">View Profile</a>
                                <?php if (isLoggedIn() && $_SESSION['user_type'] == 'user'): ?>
                                    <a href="subscribe.php?artist_id=<?php echo $artist['user_id']; ?>" class="btn btn-outline-secondary">Subscribe</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No artists found matching your search.</p>
                <a href="artists.php" class="btn btn-outline-primary">Clear search</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'views/footer.php';
?> 