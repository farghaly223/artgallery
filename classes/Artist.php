<?php
class Artist extends User {
    private $artist_id;
    private $bio;
    private $subscribers_count;
    private $artworks_count;
    private $balance;
    
    public function __construct($id = null) {
        parent::__construct($id);
        
        if ($id) {
            $this->loadArtistData();
        }
    }
    
    private function loadArtistData() {
        $artistData = $this->db->selectOne(
            "SELECT * FROM artist_profiles WHERE user_id = :user_id",
            ['user_id' => $this->id]
        );
        
        if ($artistData) {
            $this->artist_id = $artistData['artist_id'];
            $this->bio = $artistData['bio'];
            $this->subscribers_count = $artistData['subscribers_count'];
            $this->artworks_count = $artistData['artworks_count'];
            $this->balance = $artistData['balance'];
        }
    }
    
    public function getDashboardData() {
        $artworks = $this->db->select("
            SELECT * FROM artworks
            WHERE artist_id = :artist_id
            ORDER BY created_at DESC
        ", ['artist_id' => $this->artist_id]);
        
        $recentSales = $this->db->select("
            SELECT o.*, a.title, a.image_path, u.username as buyer_name
            FROM orders o
            JOIN artworks a ON o.artwork_id = a.artwork_id
            JOIN users u ON o.user_id = u.user_id
            WHERE a.artist_id = :artist_id AND o.status = 'completed'
            ORDER BY o.created_at DESC
            LIMIT 5
        ", ['artist_id' => $this->artist_id]);
        
        $subscribers = $this->db->select("
            SELECT s.*, u.username, u.profile_picture
            FROM subscriptions s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.artist_id = :artist_id
            ORDER BY s.created_at DESC
        ", ['artist_id' => $this->artist_id]);
        
        $reviews = $this->db->select("
            SELECT r.*, u.username, u.profile_picture
            FROM reviews r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.artist_id = :artist_id
            ORDER BY r.created_at DESC
        ", ['artist_id' => $this->artist_id]);
        
        return [
            'artworks' => $artworks,
            'recentSales' => $recentSales,
            'subscribers' => $subscribers,
            'reviews' => $reviews,
            'artworksCount' => $this->artworks_count,
            'subscribersCount' => $this->subscribers_count,
            'balance' => $this->balance
        ];
    }
    
    public function getArtistId() {
        return $this->artist_id;
    }
    
    public function getBio() {
        return $this->bio;
    }
    
    public function getSubscribersCount() {
        return $this->subscribers_count;
    }
    
    public function getArtworksCount() {
        return $this->artworks_count;
    }
    
    public function getBalance() {
        return $this->balance;
    }
    
    public function updateProfile($data) {
        parent::updateProfile($data);
        
        if (isset($data['bio'])) {
            $this->db->update('artist_profiles', 
                ['bio' => $data['bio']], 
                'artist_id = :artist_id', 
                ['artist_id' => $this->artist_id]
            );
            $this->bio = $data['bio'];
        }
    }
    
    public function addArtwork($title, $description, $price, $category, $imagePath, $dimensions, $material) {
        $artworkId = $this->db->insert('artworks', [
            'artist_id' => $this->artist_id,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'category' => $category,
            'image_path' => $imagePath,
            'dimensions' => $dimensions,
            'material' => $material
        ]);
        
        if ($artworkId) {
            // Update artwork count
            $this->db->query(
                "UPDATE artist_profiles SET artworks_count = artworks_count + 1 WHERE artist_id = :artist_id",
                ['artist_id' => $this->artist_id]
            );
            $this->artworks_count++;
            
            return ['success' => true, 'artwork_id' => $artworkId];
        }
        
        return ['success' => false, 'message' => 'Failed to add artwork.'];
    }
    
    public function updateArtwork($artworkId, $data) {
        // Check if artwork belongs to artist
        $artwork = $this->db->selectOne(
            "SELECT * FROM artworks WHERE artwork_id = :artwork_id AND artist_id = :artist_id",
            ['artwork_id' => $artworkId, 'artist_id' => $this->artist_id]
        );
        
        if (!$artwork) {
            return ['success' => false, 'message' => 'Artwork not found or you do not have permission to edit it.'];
        }
        
        $this->db->update('artworks', $data, 'artwork_id = :artwork_id', ['artwork_id' => $artworkId]);
        
        return ['success' => true, 'message' => 'Artwork updated successfully.'];
    }
    
    public function deleteArtwork($artworkId) {
        // Check if artwork belongs to artist
        $artwork = $this->db->selectOne(
            "SELECT * FROM artworks WHERE artwork_id = :artwork_id AND artist_id = :artist_id",
            ['artwork_id' => $artworkId, 'artist_id' => $this->artist_id]
        );
        
        if (!$artwork) {
            return ['success' => false, 'message' => 'Artwork not found or you do not have permission to delete it.'];
        }
        
        $this->db->delete('artworks', 'artwork_id = :artwork_id', ['artwork_id' => $artworkId]);
        
        // Update artwork count
        $this->db->query(
            "UPDATE artist_profiles SET artworks_count = artworks_count - 1 WHERE artist_id = :artist_id",
            ['artist_id' => $this->artist_id]
        );
        $this->artworks_count--;
        
        return ['success' => true, 'message' => 'Artwork deleted successfully.'];
    }
    
    public function createVirtualGallery($title, $description) {
        $galleryId = $this->db->insert('virtual_galleries', [
            'artist_id' => $this->artist_id,
            'title' => $title,
            'description' => $description
        ]);
        
        return ['success' => true, 'gallery_id' => $galleryId];
    }
    
    public function addArtworkToGallery($galleryId, $artworkId, $positionX, $positionY) {
        // Check if gallery belongs to artist
        $gallery = $this->db->selectOne(
            "SELECT * FROM virtual_galleries WHERE gallery_id = :gallery_id AND artist_id = :artist_id",
            ['gallery_id' => $galleryId, 'artist_id' => $this->artist_id]
        );
        
        if (!$gallery) {
            return ['success' => false, 'message' => 'Gallery not found or you do not have permission to edit it.'];
        }
        
        // Check if artwork belongs to artist
        $artwork = $this->db->selectOne(
            "SELECT * FROM artworks WHERE artwork_id = :artwork_id AND artist_id = :artist_id",
            ['artwork_id' => $artworkId, 'artist_id' => $this->artist_id]
        );
        
        if (!$artwork) {
            return ['success' => false, 'message' => 'Artwork not found or you do not have permission to add it.'];
        }
        
        $this->db->insert('gallery_artworks', [
            'gallery_id' => $galleryId,
            'artwork_id' => $artworkId,
            'position_x' => $positionX,
            'position_y' => $positionY
        ]);
        
        return ['success' => true, 'message' => 'Artwork added to gallery successfully.'];
    }
    
    public function registerArtFair($name, $location, $startDate, $endDate, $description) {
        $fairId = $this->db->insert('art_fairs', [
            'name' => $name,
            'location' => $location,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $description,
            'artist_id' => $this->artist_id
        ]);
        
        return ['success' => true, 'fair_id' => $fairId];
    }
    
    public function getSubscribers() {
        return $this->db->select("
            SELECT s.*, u.username, u.profile_picture
            FROM subscriptions s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.artist_id = :artist_id
            ORDER BY s.created_at DESC
        ", ['artist_id' => $this->artist_id]);
    }
    
    public function getReviews() {
        return $this->db->select("
            SELECT r.*, u.username, u.profile_picture
            FROM reviews r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.artist_id = :artist_id
            ORDER BY r.created_at DESC
        ", ['artist_id' => $this->artist_id]);
    }
    
    public function getArtworks() {
        return $this->db->select("
            SELECT *
            FROM artworks
            WHERE artist_id = :artist_id
            ORDER BY created_at DESC
        ", ['artist_id' => $this->artist_id]);
    }
    
    public function getGalleries() {
        return $this->db->select("
            SELECT *
            FROM virtual_galleries
            WHERE artist_id = :artist_id
            ORDER BY created_at DESC
        ", ['artist_id' => $this->artist_id]);
    }
    
    public function getGalleryArtworks($galleryId) {
        // Check if gallery belongs to artist
        $gallery = $this->db->selectOne(
            "SELECT * FROM virtual_galleries WHERE gallery_id = :gallery_id AND artist_id = :artist_id",
            ['gallery_id' => $galleryId, 'artist_id' => $this->artist_id]
        );
        
        if (!$gallery) {
            return ['success' => false, 'message' => 'Gallery not found or you do not have permission to view it.'];
        }
        
        $artworks = $this->db->select("
            SELECT ga.*, a.*
            FROM gallery_artworks ga
            JOIN artworks a ON ga.artwork_id = a.artwork_id
            WHERE ga.gallery_id = :gallery_id
            ORDER BY ga.gallery_artwork_id
        ", ['gallery_id' => $galleryId]);
        
        return ['success' => true, 'artworks' => $artworks];
    }
} 