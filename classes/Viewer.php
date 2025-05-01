<?php
class Viewer extends User {
    
    public function __construct($id = null) {
        parent::__construct($id);
    }
    
    public function getDashboardData() {
        $recentArtworks = $this->db->select("
            SELECT a.*, u.username as artist_name 
            FROM artworks a
            JOIN artist_profiles ap ON a.artist_id = ap.artist_id
            JOIN users u ON ap.user_id = u.user_id
            ORDER BY a.created_at DESC
            LIMIT 6
        ");
        
        $subscriptions = $this->db->select("
            SELECT s.*, u.username, ap.bio
            FROM subscriptions s
            JOIN artist_profiles ap ON s.artist_id = ap.artist_id
            JOIN users u ON ap.user_id = u.user_id
            WHERE s.user_id = :user_id
            ORDER BY s.created_at DESC
        ", ['user_id' => $this->id]);
        
        $recentOrders = $this->db->select("
            SELECT o.*, a.title, a.image_path, u.username as artist_name
            FROM orders o
            JOIN artworks a ON o.artwork_id = a.artwork_id
            JOIN artist_profiles ap ON a.artist_id = ap.artist_id
            JOIN users u ON ap.user_id = u.user_id
            WHERE o.user_id = :user_id
            ORDER BY o.created_at DESC
            LIMIT 5
        ", ['user_id' => $this->id]);
        
        return [
            'recentArtworks' => $recentArtworks,
            'subscriptions' => $subscriptions,
            'recentOrders' => $recentOrders
        ];
    }
    
    public function browseArtworks($filters = []) {
        $sql = "
            SELECT a.*, u.username as artist_name
            FROM artworks a
            JOIN artist_profiles ap ON a.artist_id = ap.artist_id
            JOIN users u ON ap.user_id = u.user_id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters)) {
            if (isset($filters['category']) && !empty($filters['category'])) {
                $sql .= " AND a.category = :category";
                $params['category'] = $filters['category'];
            }
            if (isset($filters['min_price']) && !empty($filters['min_price'])) {
                $sql .= " AND a.price >= :min_price";
                $params['min_price'] = $filters['min_price'];
            }
            if (isset($filters['max_price']) && !empty($filters['max_price'])) {
                $sql .= " AND a.price <= :max_price";
                $params['max_price'] = $filters['max_price'];
            }
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (a.title LIKE :search OR a.description LIKE :search OR u.username LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        
        return $this->db->select($sql, $params);
    }
    
    public function searchArtworks($keyword) {
        return $this->db->select("
            SELECT a.*, u.username as artist_name 
            FROM artworks a
            JOIN artist_profiles ap ON a.artist_id = ap.artist_id
            JOIN users u ON ap.user_id = u.user_id
            WHERE a.title LIKE :keyword 
                OR a.description LIKE :keyword 
                OR u.username LIKE :keyword
                OR a.category LIKE :keyword
            ORDER BY a.created_at DESC
        ", ['keyword' => '%' . $keyword . '%']);
    }
    
    public function subscribeToArtist($artistId) {
        // Check if already subscribed
        $subscription = $this->db->selectOne(
            "SELECT * FROM subscriptions WHERE user_id = :user_id AND artist_id = :artist_id",
            ['user_id' => $this->id, 'artist_id' => $artistId]
        );
        
        if ($subscription) {
            return ['success' => false, 'message' => 'You are already subscribed to this artist.'];
        }
        
        // Add subscription
        $this->db->insert('subscriptions', [
            'user_id' => $this->id,
            'artist_id' => $artistId
        ]);
        
        // Update artist subscribers count
        $this->db->query(
            "UPDATE artist_profiles SET subscribers_count = subscribers_count + 1 WHERE artist_id = :artist_id",
            ['artist_id' => $artistId]
        );
        
        return ['success' => true, 'message' => 'Successfully subscribed to artist.'];
    }
    
    public function makeOrder($artworkId, $paymentMethod) {
        // Get artwork details
        $artwork = $this->db->selectOne(
            "SELECT * FROM artworks WHERE artwork_id = :artwork_id AND is_sold = 0",
            ['artwork_id' => $artworkId]
        );
        
        if (!$artwork) {
            return ['success' => false, 'message' => 'Artwork not available for purchase.'];
        }
        
        // Create order
        $orderId = $this->db->insert('orders', [
            'user_id' => $this->id,
            'artwork_id' => $artworkId,
            'price' => $artwork['price'],
            'payment_method' => $paymentMethod,
            'status' => 'completed'
        ]);
        
        if ($orderId) {
            // Mark artwork as sold
            $this->db->update('artworks', 
                ['is_sold' => 1], 
                'artwork_id = :artwork_id', 
                ['artwork_id' => $artworkId]
            );
            
            // Update artist balance
            $this->db->query(
                "UPDATE artist_profiles SET balance = balance + :price WHERE artist_id = :artist_id",
                ['price' => $artwork['price'], 'artist_id' => $artwork['artist_id']]
            );
            
            return ['success' => true, 'order_id' => $orderId];
        }
        
        return ['success' => false, 'message' => 'Failed to create order.'];
    }
    
    public function reviewArtist($artistId, $rating, $comment) {
        // Check if already reviewed
        $existing = $this->db->selectOne(
            "SELECT * FROM reviews WHERE user_id = :user_id AND artist_id = :artist_id",
            ['user_id' => $this->id, 'artist_id' => $artistId]
        );
        
        if ($existing) {
            // Update existing review
            $this->db->update('reviews', 
                ['rating' => $rating, 'comment' => $comment], 
                'review_id = :review_id', 
                ['review_id' => $existing['review_id']]
            );
            
            return ['success' => true, 'message' => 'Review updated successfully.'];
        } else {
            // Create new review
            $this->db->insert('reviews', [
                'user_id' => $this->id,
                'artist_id' => $artistId,
                'rating' => $rating,
                'comment' => $comment
            ]);
            
            return ['success' => true, 'message' => 'Review submitted successfully.'];
        }
    }
    
    public function reportArtist($artistId, $reason) {
        $this->db->insert('reports', [
            'reporter_id' => $this->id,
            'reported_id' => $artistId,
            'reason' => $reason
        ]);
        
        return ['success' => true, 'message' => 'Report submitted successfully.'];
    }
    
    public function requestGuidance($requirements, $wallDimensions, $preferredStyle, $budgetRange) {
        $requestId = $this->db->insert('guidance_requests', [
            'user_id' => $this->id,
            'requirements' => $requirements,
            'wall_dimensions' => $wallDimensions,
            'preferred_style' => $preferredStyle,
            'budget_range' => $budgetRange
        ]);
        
        return ['success' => true, 'request_id' => $requestId];
    }
    
    public function createFriendRequest($friendId) {
        // Check if already friends or request pending
        $friendship = $this->db->selectOne(
            "SELECT * FROM friends WHERE 
            (user_id = :user_id AND friend_id = :friend_id) OR 
            (user_id = :friend_id AND friend_id = :user_id)",
            ['user_id' => $this->id, 'friend_id' => $friendId]
        );
        
        if ($friendship) {
            if ($friendship['status'] == 'accepted') {
                return ['success' => false, 'message' => 'You are already friends with this user.'];
            } else if ($friendship['status'] == 'pending') {
                return ['success' => false, 'message' => 'Friend request already pending.'];
            }
        }
        
        // Create friendship request
        $this->db->insert('friends', [
            'user_id' => $this->id,
            'friend_id' => $friendId,
            'status' => 'pending'
        ]);
        
        return ['success' => true, 'message' => 'Friend request sent successfully.'];
    }
    
    public function buyGiftCard($amount, $recipientEmail) {
        // Generate unique code
        $code = strtoupper(bin2hex(random_bytes(8)));
        
        $giftCardId = $this->db->insert('gift_cards', [
            'code' => $code,
            'amount' => $amount,
            'sender_id' => $this->id,
            'recipient_email' => $recipientEmail
        ]);
        
        return ['success' => true, 'gift_card_id' => $giftCardId, 'code' => $code];
    }
    
    public function inviteFriend($email) {
        $referralId = $this->db->insert('referrals', [
            'referrer_id' => $this->id,
            'referred_email' => $email
        ]);
        
        return ['success' => true, 'referral_id' => $referralId];
    }
    
    public function getFriends() {
        return $this->db->select("
            SELECT f.friendship_id, f.status, u.user_id, u.username, u.profile_picture
            FROM friends f
            JOIN users u ON (
                f.friend_id = u.user_id AND f.user_id = :user_id
                OR f.user_id = u.user_id AND f.friend_id = :user_id
            )
            WHERE u.user_id != :user_id
            ORDER BY f.created_at DESC
        ", ['user_id' => $this->id]);
    }
    
    public function searchNearbyGalleries($location) {
        return $this->db->select("
            SELECT af.*
            FROM art_fairs af
            WHERE af.location LIKE :location
            ORDER BY af.start_date DESC
        ", ['location' => '%' . $location . '%']);
    }
} 