<?php
// Connect to database
$db = new PDO('mysql:host=localhost;dbname=art_marketplace', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if featured column exists and add it if not
try {
    $stmt = $db->prepare("SELECT featured FROM artworks LIMIT 1");
    $stmt->execute();
} catch (PDOException $e) {
    // Column doesn't exist, add it
    $db->exec("ALTER TABLE artworks ADD COLUMN featured BOOLEAN DEFAULT 0");
    echo "Added 'featured' column to artworks table.<br>";
}

// Set some artworks as featured
$db->exec("UPDATE artworks SET featured = 1 WHERE artwork_id IN (1, 2, 3)");
echo "Updated featured artworks.<br>";

echo "<p>Database schema updated successfully.</p>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";
?> 