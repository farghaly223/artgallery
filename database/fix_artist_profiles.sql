-- Drop the existing artist_profiles table
DROP TABLE IF EXISTS artist_profiles;

-- Recreate with auto_increment
CREATE TABLE artist_profiles (
    artist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    bio TEXT,
    subscribers_count INT DEFAULT 0,
    artworks_count INT DEFAULT 0,
    balance DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
); 