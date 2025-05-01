<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Get search parameter
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Get all virtual galleries
$db = Database::getInstance();
$sql = "
    SELECT vg.*, u.username, u.profile_picture
    FROM virtual_galleries vg
    JOIN artist_profiles ap ON vg.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    WHERE u.is_banned = 0
";

$params = [];
if (!empty($search)) {
    $sql .= " AND (vg.title LIKE :search OR vg.description LIKE :search OR u.username LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY vg.created_at DESC";
$galleries = $db->select($sql, $params);

include 'views/header.php';
?>

<div class="container">
    <h1 class="my-4">Virtual Galleries</h1>
    
    <!-- Search -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="galleries.php" method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search galleries by title, description or artist..." value="<?php echo $search; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Galleries List -->
    <div class="row">
        <?php if (count($galleries) > 0): ?>
            <?php foreach ($galleries as $gallery): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title"><?php echo $gallery['title']; ?></h4>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $gallery['profile_picture']; ?>" class="rounded-circle me-2" width="40" height="40" alt="<?php echo $gallery['username']; ?>">
                                <span>Created by <a href="artist_profile.php?id=<?php echo $gallery['artist_id']; ?>"><?php echo $gallery['username']; ?></a></span>
                            </div>
                            <p class="card-text">
                                <?php 
                                $description = !empty($gallery['description']) ? $gallery['description'] : 'No description available.';
                                echo strlen($description) > 200 ? substr($description, 0, 200) . '...' : $description;
                                ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted">
                                    <i class="fas fa-image me-1"></i> Gallery
                                </div>
                                <div class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i> <?php echo formatDate($gallery['created_at']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="view_gallery.php?id=<?php echo $gallery['gallery_id']; ?>" class="btn btn-primary w-100">Explore Gallery</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No virtual galleries found matching your search.</p>
                <a href="galleries.php" class="btn btn-outline-primary">Clear search</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (isLoggedIn() && $_SESSION['user_type'] == 'artist'): ?>
        <div class="mt-4 text-center">
            <a href="create_gallery.php" class="btn btn-success btn-lg">Create Your Own Gallery</a>
        </div>
    <?php endif; ?>
</div>

<?php
include 'views/footer.php';
?> 