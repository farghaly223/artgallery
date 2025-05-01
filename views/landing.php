<div class="hero-section position-relative mb-5">
    <div class="hero-image" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/img/hero-bg.jpg'); background-size: cover; background-position: center; height: 500px;">
        <div class="container h-100">
            <div class="row h-100 align-items-center">
                <div class="col-md-8 text-white">
                    <h1 class="display-4 fw-bold">Discover Extraordinary Art</h1>
                    <p class="lead">Browse a curated selection of original paintings, photography, sculpture, drawings, and more. Find art you love, support artists worldwide.</p>
                    <div class="mt-4">
                        <a href="browse.php" class="btn btn-primary btn-lg me-2">Browse Art</a>
                        <a href="register.php" class="btn btn-outline-light btn-lg">Join ArtConnect</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="features-section mb-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-12 mb-4">
                <h2 class="display-5">Why Choose ArtConnect?</h2>
                <p class="lead">Experience art in a whole new way with our innovative features.</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-search fa-3x text-primary mb-3"></i>
                        <h3>Find What You Love</h3>
                        <p>Browse by artist, title, keyword or use sophisticated filters to find the perfect artwork for your space.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-vr-cardboard fa-3x text-primary mb-3"></i>
                        <h3>Virtual Room View</h3>
                        <p>See how artworks look in your space before buying with our View In Your Room feature.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-user-tie fa-3x text-primary mb-3"></i>
                        <h3>Personal Art Advisors</h3>
                        <p>Get complimentary guidance from our art advisors who will find artworks personalized for you.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="featured-artwork mb-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="display-6">Curator-Approved Featured Works</h2>
                <p class="text-muted">Discover our newest special collections released weekly.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="browse.php" class="btn btn-outline-primary">View All Artworks</a>
            </div>
        </div>
        <div class="row">
            <?php
            $db = Database::getInstance();
            $featuredArtworks = $db->select("
                SELECT a.*, u.username as artist_name
                FROM artworks a
                JOIN artist_profiles ap ON a.artist_id = ap.artist_id
                JOIN users u ON ap.user_id = u.user_id
                WHERE a.featured = 1
                ORDER BY a.created_at DESC
                LIMIT 3
            ");
            
            if (!empty($featuredArtworks)) {
                foreach ($featuredArtworks as $artwork) {
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="<?php echo $artwork['image_path']; ?>" class="card-img-top" alt="<?php echo $artwork['title']; ?>" style="height: 250px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $artwork['title']; ?></h5>
                                <p class="card-text text-muted">By <?php echo $artwork['artist_name']; ?></p>
                                <p class="card-text fw-bold"><?php echo formatPrice($artwork['price']); ?></p>
                                <a href="artwork.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-outline-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No featured artworks available at the moment. Check back soon!</p>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>

<section class="cta-section py-5 mb-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="display-6">Are You an Artist?</h2>
                <p class="lead">Join our global community of artists. Share your work with art lovers worldwide.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="register.php?type=artist" class="btn btn-primary btn-lg">Join as an Artist</a>
            </div>
        </div>
    </div>
</section>

<section class="testimonials mb-5">
    <div class="container">
        <div class="row text-center mb-4">
            <div class="col-md-12">
                <h2 class="display-6">What Our Community Says</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"I found the perfect piece for my living room using the View In Your Room feature. It's like having my own personal art gallery!"</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">JD</div>
                            <div class="ms-3">
                                <p class="mb-0 fw-bold">Jane Doe</p>
                                <p class="text-muted mb-0">Art Collector</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"As an artist, ArtConnect has given me a platform to share my work with people all over the world. My subscriber base keeps growing!"</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">MS</div>
                            <div class="ms-3">
                                <p class="mb-0 fw-bold">Mark Smith</p>
                                <p class="text-muted mb-0">Professional Artist</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                        </div>
                        <p class="card-text">"The art advisor feature is incredible. They helped me find pieces that perfectly match my style and budget. Couldn't be happier!"</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">AT</div>
                            <div class="ms-3">
                                <p class="mb-0 fw-bold">Alex Thompson</p>
                                <p class="text-muted mb-0">Interior Designer</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section> 