<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Get search keyword
$keyword = isset($_GET['keyword']) ? sanitizeInput($_GET['keyword']) : '';

// If no keyword is provided, redirect to browse page
if (empty($keyword)) {
    redirect('browse.php');
}

// Get database instance
$db = Database::getInstance();

// Search for artworks
$artworks = $db->select("
    SELECT a.*, u.username as artist_name 
    FROM artworks a
    JOIN artist_profiles ap ON a.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    WHERE a.title LIKE :keyword 
        OR a.description LIKE :keyword 
        OR a.category LIKE :keyword 
        OR u.username LIKE :keyword
    ORDER BY a.created_at DESC
", ['keyword' => '%' . $keyword . '%']);

// Search for artists
$artists = $db->select("
    SELECT u.user_id, u.username, u.profile_picture, ap.bio, ap.artist_id, 
           (SELECT COUNT(*) FROM artworks WHERE artist_id = ap.artist_id) as artwork_count
    FROM users u
    JOIN artist_profiles ap ON u.user_id = ap.user_id
    WHERE u.user_type = 'artist' 
        AND u.is_banned = 0
        AND (u.username LIKE :keyword OR ap.bio LIKE :keyword)
    ORDER BY u.username
", ['keyword' => '%' . $keyword . '%']);

// Search for galleries
$galleries = $db->select("
    SELECT vg.*, u.username, u.profile_picture
    FROM virtual_galleries vg
    JOIN artist_profiles ap ON vg.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    WHERE u.is_banned = 0
        AND (vg.title LIKE :keyword OR vg.description LIKE :keyword)
    ORDER BY vg.created_at DESC
", ['keyword' => '%' . $keyword . '%']);

include 'views/header.php';
?>

<div class="container">
    <h1 class="my-4">Search Results for "<?php echo htmlspecialchars($keyword); ?>"</h1>
    
    <!-- Search Results Overview -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h3><?php echo count($artworks); ?></h3>
                    <p class="text-muted mb-0">Artworks Found</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h3><?php echo count($artists); ?></h3>
                    <p class="text-muted mb-0">Artists Found</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h3><?php echo count($galleries); ?></h3>
                    <p class="text-muted mb-0">Galleries Found</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Artworks Section -->
    <?php if (count($artworks) > 0): ?>
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Artworks</h2>
                <?php if (count($artworks) > 4): ?>
                    <a href="browse.php?search=<?php echo urlencode($keyword); ?>" class="btn btn-outline-primary btn-sm">View All Artworks</a>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <?php 
                // Show only first 4 artworks
                $displayed_artworks = array_slice($artworks, 0, 4);
                foreach ($displayed_artworks as $artwork): 
                ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <img src="<?php echo $artwork['image_path']; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $artwork['title']; ?></h5>
                                <p class="card-text text-muted">By <?php echo $artwork['artist_name']; ?></p>
                                <p class="artwork-price"><?php echo formatPrice($artwork['price']); ?></p>
                                <a href="artwork.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Artists Section -->
    <?php if (count($artists) > 0): ?>
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Artists</h2>
                <?php if (count($artists) > 4): ?>
                    <a href="artists.php?search=<?php echo urlencode($keyword); ?>" class="btn btn-outline-primary btn-sm">View All Artists</a>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <?php 
                // Show only first 4 artists
                $displayed_artists = array_slice($artists, 0, 4);
                foreach ($displayed_artists as $artist): 
                ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <img src="<?php echo $artist['profile_picture']; ?>" alt="<?php echo $artist['username']; ?>" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                                <h5 class="card-title"><?php echo $artist['username']; ?></h5>
                                <p class="text-muted small mb-3"><?php echo $artist['artwork_count']; ?> artworks</p>
                                <p class="card-text small">
                                    <?php echo !empty($artist['bio']) ? substr($artist['bio'], 0, 100) . '...' : 'No bio available'; ?>
                                </p>
                                <a href="artist_profile.php?id=<?php echo $artist['artist_id']; ?>" class="btn btn-outline-primary btn-sm">View Profile</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Galleries Section -->
    <?php if (count($galleries) > 0): ?>
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Virtual Galleries</h2>
                <?php if (count($galleries) > 4): ?>
                    <a href="galleries.php?search=<?php echo urlencode($keyword); ?>" class="btn btn-outline-primary btn-sm">View All Galleries</a>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <?php 
                // Show only first 4 galleries
                $displayed_galleries = array_slice($galleries, 0, 4);
                foreach ($displayed_galleries as $gallery): 
                ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $gallery['title']; ?></h5>
                                <p class="card-text text-muted">By <?php echo $gallery['username']; ?></p>
                                <p class="card-text small">
                                    <?php echo !empty($gallery['description']) ? substr($gallery['description'], 0, 100) . '...' : 'No description available'; ?>
                                </p>
                                <a href="view_gallery.php?id=<?php echo $gallery['gallery_id']; ?>" class="btn btn-outline-primary btn-sm w-100">View Gallery</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- No Results Message -->
    <?php if (count($artworks) === 0 && count($artists) === 0 && count($galleries) === 0): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-4x text-muted mb-3"></i>
            <h3>No Results Found</h3>
            <p class="text-muted">We couldn't find any matches for "<?php echo htmlspecialchars($keyword); ?>".</p>
            <p>Try using different keywords or check out our <a href="browse.php">artwork collection</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'views/footer.php'; ?> 