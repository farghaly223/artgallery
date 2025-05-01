<?php
// Database configuration
$host = 'localhost';
$dbname = 'art_marketplace';
$username = 'root';
$password = '';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("DROP DATABASE IF EXISTS $dbname");
    $pdo->exec("CREATE DATABASE $dbname");
    $pdo->exec("USE $dbname");
    
    // Create tables
    $tables = "
    -- Users table
    CREATE TABLE users (
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
    CREATE TABLE artist_profiles (
        artist_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE,
        bio TEXT,
        subscribers_count INT DEFAULT 0,
        artworks_count INT DEFAULT 0,
        balance DECIMAL(10, 2) DEFAULT 0.00,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );

    -- Advisor profiles
    CREATE TABLE advisor_profiles (
        advisor_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE,
        specialization VARCHAR(100),
        experience_years INT,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );

    -- Artworks
    CREATE TABLE artworks (
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
    CREATE TABLE virtual_galleries (
        gallery_id INT AUTO_INCREMENT PRIMARY KEY,
        artist_id INT,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artist_id) REFERENCES artist_profiles(artist_id) ON DELETE CASCADE
    );

    -- Gallery artworks
    CREATE TABLE gallery_artworks (
        gallery_artwork_id INT AUTO_INCREMENT PRIMARY KEY,
        gallery_id INT,
        artwork_id INT,
        position_x INT,
        position_y INT,
        FOREIGN KEY (gallery_id) REFERENCES virtual_galleries(gallery_id) ON DELETE CASCADE,
        FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE
    );

    -- Art fairs
    CREATE TABLE art_fairs (
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
    CREATE TABLE orders (
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
    CREATE TABLE reviews (
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
    CREATE TABLE reports (
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
    CREATE TABLE subscriptions (
        subscription_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        artist_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (artist_id) REFERENCES artist_profiles(artist_id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, artist_id)
    );

    -- Friends/connections
    CREATE TABLE friends (
        friendship_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        friend_id INT,
        status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (friend_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, friend_id)
    );
    ";
    
    // Execute create tables SQL
    $statements = explode(';', $tables);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    // Insert sample data
    $pdo->exec("
    -- Insert default admin user
    INSERT INTO users (username, email, password, user_type) 
    VALUES ('admin', 'admin@artmarketplace.com', '\$2y\$10\$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'admin');
    
    -- Insert sample users
    INSERT INTO users (username, email, password, user_type)
    VALUES 
    ('viewer1', 'viewer1@example.com', '\$2y\$10\$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'user'),
    ('artist1', 'artist1@example.com', '\$2y\$10\$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'artist'),
    ('advisor1', 'advisor1@example.com', '\$2y\$10\$9YGeHIJ6mUwQKHcgHc5Eeen9mF6l4NtAojFdYO/DwGXtjbKJRIlAa', 'advisor');
    ");
    
    // Insert artist profile
    $pdo->exec("INSERT INTO artist_profiles (user_id, bio) VALUES (3, 'Contemporary artist specializing in abstract expressionism and mixed media.');");
    
    // Insert advisor profile
    $pdo->exec("INSERT INTO advisor_profiles (user_id, specialization, experience_years) VALUES (4, 'Contemporary Art', 8);");
    
    // Insert sample artworks
    $pdo->exec("
    INSERT INTO artworks (artist_id, title, description, price, category, image_path, dimensions, material, featured)
    VALUES 
    (1, 'Abstract Harmony', 'A vibrant exploration of color and form.', 1200.00, 'Painting', 'assets/img/artwork1.jpg', '24 x 36 inches', 'Acrylic on canvas', 1),
    (1, 'Urban Landscape', 'A contemporary view of city life.', 950.00, 'Photography', 'assets/img/artwork2.jpg', '20 x 30 inches', 'Digital print', 1),
    (1, 'Serenity', 'A peaceful sculpture inspired by natural forms.', 2500.00, 'Sculpture', 'assets/img/artwork3.jpg', '12 x 8 x 8 inches', 'Bronze', 1),
    (1, 'Forest Dreams', 'A dreamlike forest scene with rich textures.', 1500.00, 'Painting', 'assets/img/artwork4.jpg', '30 x 40 inches', 'Oil on canvas', 0);
    ");
    
    // Insert sample virtual gallery
    $pdo->exec("INSERT INTO virtual_galleries (artist_id, title, description) VALUES (1, 'Expressions of Nature', 'A collection exploring the beauty and complexity of natural forms.');");
    
    // Link artworks to the gallery
    $pdo->exec("
    INSERT INTO gallery_artworks (gallery_id, artwork_id, position_x, position_y)
    VALUES 
    (1, 1, 100, 150),
    (1, 2, 300, 150),
    (1, 3, 500, 150);
    ");
    
    // Insert sample art fair
    $pdo->exec("INSERT INTO art_fairs (name, location, start_date, end_date, description, artist_id) VALUES ('Contemporary Art Fair 2023', 'New York, NY', '2023-09-15', '2023-09-20', 'A gathering of the most innovative contemporary artists.', 1);");
    
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Setup Complete</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    </head>
    <body>
        <div class='container py-5'>
            <div class='card shadow-sm'>
                <div class='card-body'>
                    <h2 class='card-title text-success'><i class='fas fa-check-circle me-2'></i>Database Setup Complete!</h2>
                    <p class='card-text'>The database has been successfully created and populated with initial data.</p>
                    <hr>
                    <p>You can now:</p>
                    <div class='d-flex gap-2'>
                        <a href='index.php' class='btn btn-primary'><i class='fas fa-home me-2'></i>Go to Homepage</a>
                        <a href='register.php' class='btn btn-outline-primary'><i class='fas fa-user-plus me-2'></i>Register a New Account</a>
                    </div>
                </div>
            </div>
        </div>
        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js'></script>
    </body>
    </html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Setup Error</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    </head>
    <body>
        <div class='container py-5'>
            <div class='card shadow-sm border-danger'>
                <div class='card-body'>
                    <h2 class='card-title text-danger'><i class='fas fa-exclamation-triangle me-2'></i>Database Setup Error</h2>
                    <p class='card-text'>An error occurred while setting up the database:</p>
                    <div class='alert alert-danger'>" . $e->getMessage() . "</div>
                    <hr>
                    <a href='index.php' class='btn btn-primary'>Go to Homepage</a>
                </div>
            </div>
        </div>
        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js'></script>
    </body>
    </html>";
}
?> 