/**
 * Main JavaScript file for ArtConnect
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto close alerts after 3 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Virtual Room View functionality
    initVirtualRoomView();

    // Filter functionality for artwork browsing
    initFilterFunctionality();

    // Add to cart functionality
    initAddToCartFunctionality();

    // Review submission functionality
    initReviewFunctionality();

    // Artist dashboard chart initialization
    initArtistDashboardCharts();
});

/**
 * Initialize the Virtual Room View feature
 */
function initVirtualRoomView() {
    const roomViewContainer = document.getElementById('room-view-container');
    if (!roomViewContainer) return;

    const artworkPreview = document.getElementById('artwork-preview');
    const roomImage = document.getElementById('room-image');
    const artworkSelector = document.getElementById('artwork-selector');

    // If all required elements exist
    if (artworkPreview && roomImage && artworkSelector) {
        // Change artwork on selection
        artworkSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const artworkSrc = selectedOption.getAttribute('data-image');
            const artworkWidth = selectedOption.getAttribute('data-width');
            const artworkHeight = selectedOption.getAttribute('data-height');

            if (artworkSrc) {
                artworkPreview.src = artworkSrc;
                
                // Set artwork dimensions proportionally
                const maxWidth = roomViewContainer.offsetWidth * 0.5;
                const maxHeight = roomViewContainer.offsetHeight * 0.6;
                
                let width = parseInt(artworkWidth, 10) || 100;
                let height = parseInt(artworkHeight, 10) || 100;
                
                const ratio = Math.min(maxWidth / width, maxHeight / height);
                
                artworkPreview.style.width = (width * ratio) + 'px';
                artworkPreview.style.height = (height * ratio) + 'px';
                
                artworkPreview.style.display = 'block';
            } else {
                artworkPreview.style.display = 'none';
            }
        });

        // Make artwork draggable within room
        let isDragging = false;
        let offsetX, offsetY;

        artworkPreview.addEventListener('mousedown', function(e) {
            isDragging = true;
            offsetX = e.clientX - artworkPreview.getBoundingClientRect().left;
            offsetY = e.clientY - artworkPreview.getBoundingClientRect().top;
            artworkPreview.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            
            const containerRect = roomViewContainer.getBoundingClientRect();
            const previewRect = artworkPreview.getBoundingClientRect();
            
            let left = e.clientX - containerRect.left - offsetX;
            let top = e.clientY - containerRect.top - offsetY;
            
            // Keep within container bounds
            left = Math.max(0, Math.min(left, containerRect.width - previewRect.width));
            top = Math.max(0, Math.min(top, containerRect.height - previewRect.height));
            
            artworkPreview.style.left = left + 'px';
            artworkPreview.style.top = top + 'px';
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
            if (artworkPreview) {
                artworkPreview.style.cursor = 'grab';
            }
        });

        // Room background selector if available
        const roomSelector = document.getElementById('room-selector');
        if (roomSelector) {
            roomSelector.addEventListener('change', function() {
                const selectedRoom = this.options[this.selectedIndex].getAttribute('data-image');
                if (selectedRoom) {
                    roomImage.src = selectedRoom;
                }
            });
        }

        // Take screenshot functionality
        const screenshotBtn = document.getElementById('take-screenshot');
        if (screenshotBtn) {
            screenshotBtn.addEventListener('click', function() {
                // Here you would implement actual screenshot functionality
                // For this example, we'll just show an alert
                alert('Screenshot taken and saved to your gallery!');
            });
        }
    }
}

/**
 * Initialize filter functionality for browsing artworks
 */
function initFilterFunctionality() {
    const filterForm = document.getElementById('filter-form');
    if (!filterForm) return;

    const priceRangeMin = document.getElementById('price-range-min');
    const priceRangeMax = document.getElementById('price-range-max');
    const priceRangeOutput = document.getElementById('price-range-output');

    // Update price range display
    if (priceRangeMin && priceRangeMax && priceRangeOutput) {
        function updatePriceRange() {
            priceRangeOutput.textContent = `$${priceRangeMin.value} - $${priceRangeMax.value}`;
        }

        priceRangeMin.addEventListener('input', updatePriceRange);
        priceRangeMax.addEventListener('input', updatePriceRange);
        
        // Initial update
        updatePriceRange();
    }

    // Implement instant filtering if needed
    const filterInputs = filterForm.querySelectorAll('input, select');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            // If you want to submit the form immediately on any change
            // filterForm.submit();
            
            // Alternatively, you could use AJAX to filter without page reload
        });
    });
}

/**
 * Initialize add to cart functionality
 */
function initAddToCartFunctionality() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const artworkId = this.getAttribute('data-artwork-id');
            const artworkTitle = this.getAttribute('data-artwork-title');
            
            // Here you would implement actual cart functionality
            // For this example, we'll just show an alert
            alert(`"${artworkTitle}" has been added to your cart!`);
            
            // You could use AJAX to add to cart without page reload
            // fetch('add_to_cart.php', {
            //     method: 'POST',
            //     body: JSON.stringify({ artwork_id: artworkId }),
            //     headers: { 'Content-Type': 'application/json' }
            // })
            // .then(response => response.json())
            // .then(data => {
            //     // Update cart count in UI
            // });
        });
    });
}

/**
 * Initialize review submission functionality
 */
function initReviewFunctionality() {
    const reviewForm = document.getElementById('review-form');
    if (!reviewForm) return;

    const ratingInputs = reviewForm.querySelectorAll('input[name="rating"]');
    const ratingValue = document.getElementById('rating-value');

    // Update rating display when user selects a star
    if (ratingInputs.length && ratingValue) {
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingValue.textContent = this.value;
            });
        });
    }

    // Handle form submission
    reviewForm.addEventListener('submit', function(e) {
        const commentInput = reviewForm.querySelector('textarea[name="comment"]');
        
        if (!commentInput.value.trim()) {
            e.preventDefault();
            alert('Please enter a comment for your review.');
            commentInput.focus();
        }
    });
}

/**
 * Initialize charts for artist dashboard
 */
function initArtistDashboardCharts() {
    const salesChartCanvas = document.getElementById('salesChart');
    if (!salesChartCanvas) return;

    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }

    // Example chart data
    const ctx = salesChartCanvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Sales',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: 'rgba(78, 115, 223, 0.2)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
} 