    </main>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>ArtConnect</h5>
                    <p>Discover, buy, and sell art online. It's never been easier for art lovers to find, preview, and purchase original works and for artists to share their work with a global audience.</p>
                </div>
                <div class="col-md-2">
                    <h5>Explore</h5>
                    <ul class="list-unstyled">
                        <li><a href="browse.php" class="text-white">Browse Art</a></li>
                        <li><a href="artists.php" class="text-white">Artists</a></li>
                        <li><a href="galleries.php" class="text-white">Virtual Galleries</a></li>
                        <li><a href="art_fairs.php" class="text-white">Art Fairs</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h5>Account</h5>
                    <ul class="list-unstyled">
                        <?php if(isLoggedIn()): ?>
                            <li><a href="profile.php" class="text-white">My Profile</a></li>
                            <li><a href="logout.php" class="text-white">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="text-white">Login</a></li>
                            <li><a href="register.php" class="text-white">Sign Up</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect with Us</h5>
                    <div class="d-flex">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f fa-2x"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-2x"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-2x"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-pinterest fa-2x"></i></a>
                    </div>
                    <div class="mt-3">
                        <p>Subscribe to our newsletter</p>
                        <form>
                            <div class="input-group">
                                <input type="email" class="form-control" placeholder="Your email">
                                <button class="btn btn-primary" type="button">Subscribe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> ArtConnect. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-white me-3">Terms of Service</a>
                    <a href="#" class="text-white">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 