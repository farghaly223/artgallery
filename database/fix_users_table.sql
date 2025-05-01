-- Drop the existing users table
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS friends;
DROP TABLE IF EXISTS gift_cards;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS guidance_requests;
DROP TABLE IF EXISTS recommendations;
DROP TABLE IF EXISTS screenshots;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS gallery_artworks;
DROP TABLE IF EXISTS artworks;
DROP TABLE IF EXISTS virtual_galleries;
DROP TABLE IF EXISTS art_fairs;
DROP TABLE IF EXISTS artist_profiles;
DROP TABLE IF EXISTS advisor_profiles;
DROP TABLE IF EXISTS users;

-- Recreate the users table with the correct structure
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('user', 'artist', 'admin', 'advisor') NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'assets/img/default-profile.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_banned BOOLEAN DEFAULT 0,
    ban_reason TEXT
);

-- Create the artist_profiles table
CREATE TABLE IF NOT EXISTS artist_profiles (
    artist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    bio TEXT,
    subscribers_count INT DEFAULT 0,
    artworks_count INT DEFAULT 0,
    balance DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create the advisor_profiles table
CREATE TABLE IF NOT EXISTS advisor_profiles (
    advisor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    specialization VARCHAR(100),
    experience_years INT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, email, password, user_type) 
VALUES ('admin', 'admin@artmarketplace.com', '$2y$10$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'admin'); 