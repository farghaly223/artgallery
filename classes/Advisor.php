<?php
class Advisor extends User {
    private $advisor_id;
    private $specialization;
    private $experience_years;
    
    public function __construct($id = null) {
        parent::__construct($id);
        
        if ($id) {
            $this->loadAdvisorData();
        }
    }
    
    private function loadAdvisorData() {
        $advisorData = $this->db->selectOne(
            "SELECT * FROM advisor_profiles WHERE user_id = :user_id",
            ['user_id' => $this->id]
        );
        
        if ($advisorData) {
            $this->advisor_id = $advisorData['advisor_id'];
            $this->specialization = $advisorData['specialization'];
            $this->experience_years = $advisorData['experience_years'];
        }
    }
    
    public function getDashboardData() {
        $pendingRequests = $this->db->select("
            SELECT gr.*, u.username, u.profile_picture
            FROM guidance_requests gr
            JOIN users u ON gr.user_id = u.user_id
            WHERE (gr.advisor_id = :advisor_id OR gr.advisor_id IS NULL)
                AND gr.status IN ('pending', 'in_progress')
            ORDER BY gr.created_at DESC
        ", ['advisor_id' => $this->advisor_id]);
        
        $completedRequests = $this->db->select("
            SELECT gr.*, u.username, u.profile_picture
            FROM guidance_requests gr
            JOIN users u ON gr.user_id = u.user_id
            WHERE gr.advisor_id = :advisor_id AND gr.status = 'completed'
            ORDER BY gr.created_at DESC
            LIMIT 10
        ", ['advisor_id' => $this->advisor_id]);
        
        $recommendedArtworks = $this->db->select("
            SELECT r.*, a.title, a.image_path, gr.user_id
            FROM recommendations r
            JOIN guidance_requests gr ON r.request_id = gr.request_id
            JOIN artworks a ON r.artwork_id = a.artwork_id
            WHERE gr.advisor_id = :advisor_id
            ORDER BY r.created_at DESC
            LIMIT 10
        ", ['advisor_id' => $this->advisor_id]);
        
        return [
            'pendingRequests' => $pendingRequests,
            'completedRequests' => $completedRequests,
            'recommendedArtworks' => $recommendedArtworks
        ];
    }
    
    public function getAdvisorId() {
        return $this->advisor_id;
    }
    
    public function getSpecialization() {
        return $this->specialization;
    }
    
    public function getExperienceYears() {
        return $this->experience_years;
    }
    
    public function updateProfile($data) {
        parent::updateProfile($data);
        
        $advisorData = [];
        if (isset($data['specialization'])) {
            $advisorData['specialization'] = $data['specialization'];
            $this->specialization = $data['specialization'];
        }
        
        if (isset($data['experience_years'])) {
            $advisorData['experience_years'] = $data['experience_years'];
            $this->experience_years = $data['experience_years'];
        }
        
        if (!empty($advisorData)) {
            $this->db->update('advisor_profiles', 
                $advisorData, 
                'advisor_id = :advisor_id', 
                ['advisor_id' => $this->advisor_id]
            );
        }
    }
    
    public function getPendingRequests() {
        return $this->db->select("
            SELECT gr.*, u.username, u.profile_picture
            FROM guidance_requests gr
            JOIN users u ON gr.user_id = u.user_id
            WHERE gr.advisor_id IS NULL AND gr.status = 'pending'
            ORDER BY gr.created_at ASC
        ");
    }
    
    public function assignToRequest($requestId) {
        $request = $this->db->selectOne(
            "SELECT * FROM guidance_requests WHERE request_id = :request_id AND (advisor_id IS NULL OR advisor_id = :advisor_id)",
            ['request_id' => $requestId, 'advisor_id' => $this->advisor_id]
        );
        
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found or already assigned to another advisor.'];
        }
        
        $this->db->update('guidance_requests', 
            ['advisor_id' => $this->advisor_id, 'status' => 'in_progress'], 
            'request_id = :request_id', 
            ['request_id' => $requestId]
        );
        
        return ['success' => true, 'message' => 'Request assigned successfully.'];
    }
    
    public function addRecommendation($requestId, $artworkId, $comment) {
        $request = $this->db->selectOne(
            "SELECT * FROM guidance_requests WHERE request_id = :request_id AND advisor_id = :advisor_id",
            ['request_id' => $requestId, 'advisor_id' => $this->advisor_id]
        );
        
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found or not assigned to you.'];
        }
        
        $recommendationId = $this->db->insert('recommendations', [
            'request_id' => $requestId,
            'artwork_id' => $artworkId,
            'comment' => $comment
        ]);
        
        return ['success' => true, 'recommendation_id' => $recommendationId];
    }
    
    public function completeRequest($requestId) {
        $request = $this->db->selectOne(
            "SELECT * FROM guidance_requests WHERE request_id = :request_id AND advisor_id = :advisor_id",
            ['request_id' => $requestId, 'advisor_id' => $this->advisor_id]
        );
        
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found or not assigned to you.'];
        }
        
        // Check if recommendations exist
        $recommendations = $this->db->select(
            "SELECT * FROM recommendations WHERE request_id = :request_id",
            ['request_id' => $requestId]
        );
        
        if (empty($recommendations)) {
            return ['success' => false, 'message' => 'Please add at least one recommendation before completing the request.'];
        }
        
        $this->db->update('guidance_requests', 
            ['status' => 'completed'], 
            'request_id = :request_id', 
            ['request_id' => $requestId]
        );
        
        return ['success' => true, 'message' => 'Request completed successfully.'];
    }
    
    public function getRequestDetails($requestId) {
        $request = $this->db->selectOne("
            SELECT gr.*, u.username, u.profile_picture
            FROM guidance_requests gr
            JOIN users u ON gr.user_id = u.user_id
            WHERE gr.request_id = :request_id
        ", ['request_id' => $requestId]);
        
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.'];
        }
        
        $recommendations = $this->db->select("
            SELECT r.*, a.title, a.image_path, a.price, a.category
            FROM recommendations r
            JOIN artworks a ON r.artwork_id = a.artwork_id
            WHERE r.request_id = :request_id
            ORDER BY r.created_at DESC
        ", ['request_id' => $requestId]);
        
        return [
            'success' => true,
            'request' => $request,
            'recommendations' => $recommendations
        ];
    }
    
    public function recommendArtwork($userId, $artworkId, $comment) {
        // This is for unsolicited recommendations, not tied to a specific request
        $this->db->insert('recommendations', [
            'artwork_id' => $artworkId,
            'comment' => $comment
            // No request_id means it's an unsolicited recommendation
        ]);
        
        return ['success' => true, 'message' => 'Recommendation created successfully.'];
    }
} 