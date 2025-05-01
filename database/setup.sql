-- Drop all existing tables in reverse order of dependencies
DROP TABLE IF EXISTS screenshots;
DROP TABLE IF EXISTS recommendations;
DROP TABLE IF EXISTS guidance_requests;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS gift_cards;
DROP TABLE IF EXISTS friends;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS gallery_artworks;
DROP TABLE IF EXISTS virtual_galleries;
DROP TABLE IF EXISTS art_fairs;
DROP TABLE IF EXISTS artworks;
DROP TABLE IF EXISTS advisor_profiles;
DROP TABLE IF EXISTS artist_profiles;
DROP TABLE IF EXISTS users;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS art_marketplace;

-- Users table
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

-- Artist profiles
CREATE TABLE IF NOT EXISTS artist_profiles (
    artist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    bio TEXT,
    subscribers_count INT DEFAULT 0,
    artworks_count INT DEFAULT 0,
    balance DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Advisor profiles
CREATE TABLE IF NOT EXISTS advisor_profiles (
    advisor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    specialization VARCHAR(100),
    experience_years INT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Artworks
CREATE TABLE IF NOT EXISTS artworks (
    artwork_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    dimensions VARCHAR(50),
    material VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_sold BOOLEAN DEFAULT 0,
    featured BOOLEAN DEFAULT 0,
    FOREIGN KEY (artist_id) REFERENCES artist_profiles(artist_id) ON DELETE CASCADE
);

-- Virtual galleries
CREATE TABLE IF NOT EXISTS virtual_galleries (
    gallery_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artist_id) REFERENCES artist_profiles(artist_id) ON DELETE CASCADE
);

-- Gallery artworks
CREATE TABLE IF NOT EXISTS gallery_artworks (
    gallery_artwork_id INT AUTO_INCREMENT PRIMARY KEY,
    gallery_id INT,
    artwork_id INT,
    position_x INT,
    position_y INT,
    FOREIGN KEY (gallery_id) REFERENCES virtual_galleries(gallery_id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE
);

-- Art fairs
CREATE TABLE IF NOT EXISTS art_fairs (
    fair_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    description TEXT,
    artist_id INT,
    FOREIGN KEY (artist_id) REFERENCES artist_profiles(artist_id) ON DELETE SET NULL
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artwork_id INT,
    price DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE SET NULL
);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artist_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (artist_id) REFERENCES artist_profiles(artist_id) ON DELETE CASCADE
);

-- Reports
CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT,
    reported_id INT,
    reason TEXT NOT NULL,
    status ENUM('pending', 'resolved', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Subscriptions
CREATE TABLE IF NOT EXISTS subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artist_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (artist_id) REFERENCES artist_profiles(artist_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, artist_id)
);

-- Friends/connections
CREATE TABLE IF NOT EXISTS friends (
    friendship_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    friend_id INT,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, friend_id)
);

-- Gift cards
CREATE TABLE IF NOT EXISTS gift_cards (
    gift_card_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(16) UNIQUE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    sender_id INT,
    recipient_email VARCHAR(100),
    is_redeemed BOOLEAN DEFAULT 0,
    redeemed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (redeemed_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Referrals
CREATE TABLE IF NOT EXISTS referrals (
    referral_id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT,
    referred_email VARCHAR(100) NOT NULL,
    is_signed_up BOOLEAN DEFAULT 0,
    discount_applied BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- User guidance requests
CREATE TABLE IF NOT EXISTS guidance_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    advisor_id INT,
    requirements TEXT NOT NULL,
    wall_dimensions VARCHAR(50),
    preferred_style VARCHAR(100),
    budget_range VARCHAR(50),
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (advisor_id) REFERENCES advisor_profiles(advisor_id) ON DELETE SET NULL
);

-- Recommendations
CREATE TABLE IF NOT EXISTS recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT,
    artwork_id INT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES guidance_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE
);

-- Virtual room screenshots
CREATE TABLE IF NOT EXISTS screenshots (
    screenshot_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, email, password, user_type) 
VALUES ('admin', 'admin@artmarketplace.com', '$2y$10$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'admin');

-- Insert sample data for testing
INSERT INTO users (username, email, password, user_type)
VALUES 
('viewer1', 'viewer1@example.com', '$2y$10$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'user'),
('artist1', 'artist1@example.com', '$2y$10$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'artist'),
('advisor1', 'advisor1@example.com', '$2y$10$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'advisor');

-- Insert artist profile
INSERT INTO artist_profiles (user_id, bio)
VALUES (3, 'Contemporary artist specializing in abstract expressionism and mixed media.');

-- Insert advisor profile
INSERT INTO advisor_profiles (user_id, specialization, experience_years)
VALUES (4, 'Contemporary Art', 8);

-- Insert sample artworks
INSERT INTO artworks (artist_id, title, description, price, category, image_path, dimensions, material, featured)
VALUES 
(1, 'Abstract Harmony', 'A vibrant exploration of color and form.', 1200.00, 'Painting', 'assets/img/artwork1.jpg', '24 x 36 inches', 'Acrylic on canvas', 1),
(1, 'Urban Landscape', 'A contemporary view of city life.', 950.00, 'Photography', 'assets/img/artwork2.jpg', '20 x 30 inches', 'Digital print', 1),
(1, 'Serenity', 'A peaceful sculpture inspired by natural forms.', 2500.00, 'Sculpture', 'assets/img/artwork3.jpg', '12 x 8 x 8 inches', 'Bronze', 1),
(1, 'Forest Dreams', 'A dreamlike forest scene with rich textures.', 1500.00, 'Painting', 'assets/img/artwork4.jpg', '30 x 40 inches', 'Oil on canvas', 0);

-- Insert sample virtual gallery
INSERT INTO virtual_galleries (artist_id, title, description)
VALUES (1, 'Expressions of Nature', 'A collection exploring the beauty and complexity of natural forms.');

-- Link artworks to the gallery
INSERT INTO gallery_artworks (gallery_id, artwork_id, position_x, position_y)
VALUES 
(1, 1, 100, 150),
(1, 2, 300, 150),
(1, 3, 500, 150);

-- Insert sample art fair
INSERT INTO art_fairs (name, location, start_date, end_date, description, artist_id)
VALUES ('Contemporary Art Fair 2023', 'New York, NY', '2023-09-15', '2023-09-20', 'A gathering of the most innovative contemporary artists.', 1); 