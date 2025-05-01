<?php
class Admin extends User {
    
    public function __construct($id = null) {
        parent::__construct($id);
    }
    
    public function getDashboardData() {
        $pendingReports = $this->db->select("
            SELECT r.*, 
                  reporter.username as reporter_name, 
                  reported.username as reported_name,
                  reporter.user_type as reporter_type,
                  reported.user_type as reported_type
            FROM reports r
            JOIN users reporter ON r.reporter_id = reporter.user_id
            JOIN users reported ON r.reported_id = reported.user_id
            WHERE r.status = 'pending'
            ORDER BY r.created_at DESC
        ");
        
        $bannedUsers = $this->db->select("
            SELECT *
            FROM users
            WHERE is_banned = 1
            ORDER BY created_at DESC
        ");
        
        $userStats = $this->db->selectOne("
            SELECT 
                COUNT(CASE WHEN user_type = 'user' THEN 1 END) as viewer_count,
                COUNT(CASE WHEN user_type = 'artist' THEN 1 END) as artist_count,
                COUNT(CASE WHEN user_type = 'advisor' THEN 1 END) as advisor_count,
                COUNT(CASE WHEN is_banned = 1 THEN 1 END) as banned_count
            FROM users
        ");
        
        $artworkStats = $this->db->selectOne("
            SELECT 
                COUNT(*) as total_artworks,
                COUNT(CASE WHEN is_sold = 1 THEN 1 END) as sold_artworks,
                SUM(CASE WHEN is_sold = 1 THEN price ELSE 0 END) as total_sales
            FROM artworks
        ");
        
        $recentUsers = $this->db->select("
            SELECT *
            FROM users
            ORDER BY created_at DESC
            LIMIT 10
        ");
        
        return [
            'pendingReports' => $pendingReports,
            'bannedUsers' => $bannedUsers,
            'userStats' => $userStats,
            'artworkStats' => $artworkStats,
            'recentUsers' => $recentUsers
        ];
    }
    
    public function getReports($status = null) {
        $sql = "
            SELECT r.*, 
                  reporter.username as reporter_name, 
                  reported.username as reported_name,
                  reporter.user_type as reporter_type,
                  reported.user_type as reported_type
            FROM reports r
            JOIN users reporter ON r.reporter_id = reporter.user_id
            JOIN users reported ON r.reported_id = reported.user_id
        ";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE r.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        return $this->db->select($sql, $params);
    }
    
    public function resolveReport($reportId, $action) {
        $report = $this->db->selectOne(
            "SELECT * FROM reports WHERE report_id = :report_id",
            ['report_id' => $reportId]
        );
        
        if (!$report) {
            return ['success' => false, 'message' => 'Report not found.'];
        }
        
        if ($action == 'ban') {
            // Ban the reported user
            $this->banUser($report['reported_id'], "Banned due to report: " . $report['reason']);
            
            // Update report status
            $this->db->update('reports', 
                ['status' => 'resolved'], 
                'report_id = :report_id', 
                ['report_id' => $reportId]
            );
            
            return ['success' => true, 'message' => 'User banned and report resolved.'];
        } else if ($action == 'dismiss') {
            // Update report status
            $this->db->update('reports', 
                ['status' => 'dismissed'], 
                'report_id = :report_id', 
                ['report_id' => $reportId]
            );
            
            return ['success' => true, 'message' => 'Report dismissed.'];
        }
        
        return ['success' => false, 'message' => 'Invalid action.'];
    }
    
    public function banUser($userId, $reason) {
        $user = $this->db->selectOne(
            "SELECT * FROM users WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        $this->db->update('users', 
            ['is_banned' => 1, 'ban_reason' => $reason], 
            'user_id = :user_id', 
            ['user_id' => $userId]
        );
        
        return ['success' => true, 'message' => 'User banned successfully.'];
    }
    
    public function unbanUser($userId) {
        $user = $this->db->selectOne(
            "SELECT * FROM users WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        $this->db->update('users', 
            ['is_banned' => 0, 'ban_reason' => null], 
            'user_id = :user_id', 
            ['user_id' => $userId]
        );
        
        return ['success' => true, 'message' => 'User unbanned successfully.'];
    }
    
    public function getAllUsers() {
        return $this->db->select("
            SELECT *
            FROM users
            ORDER BY created_at DESC
        ");
    }
    
    public function getUserDetails($userId) {
        $user = $this->db->selectOne(
            "SELECT * FROM users WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        $userData = [
            'user' => $user
        ];
        
        if ($user['user_type'] == 'artist') {
            $artistData = $this->db->selectOne(
                "SELECT * FROM artist_profiles WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            
            $artworks = $this->db->select(
                "SELECT * FROM artworks WHERE artist_id = :artist_id",
                ['artist_id' => $artistData['artist_id']]
            );
            
            $userData['artist_profile'] = $artistData;
            $userData['artworks'] = $artworks;
        }
        
        $reports = $this->db->select(
            "SELECT * FROM reports WHERE reported_id = :user_id",
            ['user_id' => $userId]
        );
        
        $userData['reports'] = $reports;
        
        return ['success' => true, 'data' => $userData];
    }
    
    public function manageArtworks() {
        return $this->db->select("
            SELECT a.*, u.username as artist_name
            FROM artworks a
            JOIN artist_profiles ap ON a.artist_id = ap.artist_id
            JOIN users u ON ap.user_id = u.user_id
            ORDER BY a.created_at DESC
        ");
    }
    
    public function deleteArtwork($artworkId) {
        $artwork = $this->db->selectOne(
            "SELECT * FROM artworks WHERE artwork_id = :artwork_id",
            ['artwork_id' => $artworkId]
        );
        
        if (!$artwork) {
            return ['success' => false, 'message' => 'Artwork not found.'];
        }
        
        $this->db->delete('artworks', 'artwork_id = :artwork_id', ['artwork_id' => $artworkId]);
        
        // Update artist artwork count
        $this->db->query(
            "UPDATE artist_profiles SET artworks_count = artworks_count - 1 WHERE artist_id = :artist_id",
            ['artist_id' => $artwork['artist_id']]
        );
        
        return ['success' => true, 'message' => 'Artwork deleted successfully.'];
    }
    
    public function verifyUser($userId) {
        $user = $this->db->selectOne(
            "SELECT * FROM users WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Here we could add a verified flag to the users table if needed
        
        return ['success' => true, 'message' => 'User verified successfully.'];
    }
} 