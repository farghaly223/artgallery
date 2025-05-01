<?php 
require_once 'includes/functions.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtConnect - Discover, Buy and Sell Art Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <header class="bg-dark text-white">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php">ArtConnect</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="browse.php">Browse Art</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="artists.php">Artists</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="galleries.php">Virtual Galleries</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="art_fairs.php">Art Fairs</a>
                        </li>
                    </ul>
                    <form class="d-flex me-2" action="search.php" method="GET">
                        <input class="form-control me-2" type="search" name="keyword" placeholder="Search artworks..." aria-label="Search">
                        <button class="btn btn-outline-light" type="submit">Search</button>
                    </form>
                    <div class="d-flex">
                        <?php if(isLoggedIn()): ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user me-1"></i> <?php echo $_SESSION['username']; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <?php if(isUserType('user')): ?>
                                        <li><a class="dropdown-item" href="user_dashboard.php">My Dashboard</a></li>
                                    <?php elseif(isUserType('artist')): ?>
                                        <li><a class="dropdown-item" href="artist_dashboard.php">Artist Dashboard</a></li>
                                    <?php elseif(isUserType('admin')): ?>
                                        <li><a class="dropdown-item" href="admin_dashboard.php">Admin Dashboard</a></li>
                                    <?php elseif(isUserType('advisor')): ?>
                                        <li><a class="dropdown-item" href="advisor_dashboard.php">Advisor Dashboard</a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                            <a href="register.php" class="btn btn-primary">Sign Up</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <?php 
        echo flashMessage(); 
        ?> 