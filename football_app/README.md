# Football Tournament Management System

## Setup Instructions

### 1. Database Setup
1. Start XAMPP/WAMP and open phpMyAdmin
2. Import the `FootballTournamentDB.sql` file to create the database, tables, views, stored procedures, and sample data
3. Default admin login: **Username:** admin | **Password:** admin123

### 2. Website Setup
1. Copy the entire `football_app` folder to your XAMPP htdocs directory (e.g., `C:\xampp\htdocs\football_app`)
2. Start Apache and MySQL from XAMPP control panel
3. Open your browser and go to: `http://localhost/football_app/login.php`

### 3. Database Configuration
If your MySQL credentials are different, edit `config.php`:
```php
$DB_HOST = 'localhost';
$DB_NAME = 'FootballTournamentDB';
$DB_USER = 'root';
$DB_PASS = '';
```

## Features
- **Authentication**: Login & Registration with secure password hashing
- **Dashboard**: Statistics overview with upcoming matches
- **Tournaments**: Full CRUD (Add, Edit, Delete) with search
- **Teams**: Full CRUD with tournament dropdown selection
- **Players**: Full CRUD with Position field, age validation
- **Matches**: Full CRUD with scores, tournament & team dropdowns
- **Search**: Search functionality on all pages
- **Security**: Prepared statements (SQL injection prevention), session protection
- **Responsive**: Mobile-friendly with hamburger menu
- **Database**: Views, Stored Procedures, JOINs, Subqueries

## File Structure
```
football_app/
  assets/
    logo.png
    matches.jpg
    players.jfif
    style.css
    team.jfif
    tournament.jfif
  config.php          - Database connection & helper functions
  navbar.php          - Shared navigation with hamburger menu
  footer.php          - Shared footer
  login.php           - Login page
  register.php        - Registration page (NEW)
  logout.php          - Logout handler
  index.php           - Dashboard with stats
  tournament.php      - Tournament CRUD
  team.php            - Team CRUD
  players.php         - Player CRUD with Position
  matches.php         - Match CRUD with scores
  FootballTournamentDB.sql - Complete database script
```

