# ArtConnect - Art Marketplace

ArtConnect is a web application that helps users discover, buy, and sell art online. It provides a platform for art lovers to find, preview, and purchase original works and for artists to share their work with a global audience.

## Database Setup

To initialize the database, follow these steps:

1. Make sure MySQL is running
2. Access the application in your browser
3. Run the database setup scripts in this order:
   - `setup_db.php` - Creates all tables and sample data
   - `update_schema.php` - Updates any schema issues and adds featured artworks

## Features

- **Browse Artworks**: Users can browse a curated selection of original paintings, photography, sculpture, drawings, and collage by artist, title, and keyword or use sophisticated filters.
- **Virtual Room View**: Users can virtually "hang" art on their wall using the View In Your Room feature to see how an artwork will look in their home before buying it.
- **Personalized Recommendations**: Users can request complimentary guidance from an art advisor to find artworks handpicked for them.
- **Artist Subscriptions**: Users can subscribe to artists to follow their work.
- **Virtual Galleries**: Artists can create online virtual galleries hosted by the app.
- **Art Fairs & Galleries**: Artists can register their Art Fairs and Galleries, and users can find Art Fairs near them.
- **Reviews & Reports**: Users can review and report artists.
- **Referral Program**: Users can invite friends and get discounts.
- **eGift Cards**: Users can buy eGift Cards for family and friends.

## User Types

- **Viewer**: Regular users who browse, purchase art, request recommendations, etc.
- **Artist**: Creators who can upload, sell artworks, create virtual galleries, etc.
- **Advisor**: Art experts who provide personalized recommendations to users.
- **Admin**: System administrators who manage the platform, handle reports, ban users if necessary, etc.

## Technical Implementation

### Object-Oriented PHP

The application is built using OOP principles in PHP:

- **Abstract User Class**: Base class for all user types (Viewer, Artist, Admin, Advisor)
- **Strategy Pattern**: Applied for different user behaviors and permissions
- **Database Layer**: PDO with singleton pattern for database connections

### Database

- MySQL database with multiple tables for users, artworks, galleries, orders, reviews, etc.
- Relationships between entities (users, artworks, orders, etc.)

### Frontend

- Bootstrap 5 for responsive UI
- Custom CSS for styling
- JavaScript for interactive features (virtual room view, filtering, etc.)

## Installation

1. Clone the repository
2. Import the database schema from `database/setup.sql`
3. Configure database connection in `config/config.php`
4. Place the project in your web server's document root
5. Access the application via your browser

## Default Login Credentials

- **Admin**: admin / password
- **Viewer**: viewer1 / password
- **Artist**: artist1 / password
- **Advisor**: advisor1 / password

## Directory Structure

- `/assets`: Contains CSS, JavaScript, and image files
- `/classes`: Contains all PHP classes
- `/config`: Contains configuration files
- `/database`: Contains database setup script
- `/includes`: Contains utility functions and autoloader
- `/views`: Contains HTML templates

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

## License

This project is licensed under the MIT License.

## Credits

Developed by [Your Name] for the Software Engineering Project. 