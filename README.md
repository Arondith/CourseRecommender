# CourseRecommender

## About

A **web-based academic course recommendation and administration system** built with HTML, PHP, JavaScript, and a database-backed authentication flow. The project combines a student-facing recommendation experience with administrative controls for managing access, viewing information, and supporting reporting.

Users can register, log in, recover account access, open a dashboard, and move through the course-recommendation workflow. The system also includes a separate **administrator login and dashboard**, authentication checks, session handling, database connectivity, API-style backend logic, and chart/reporting components. The project demonstrates how a front-end interface connects to PHP application logic and persistent database data in a multi-role web system.

## Core features

- User registration and login
- Password recovery interface
- User dashboard
- Course recommendation workflow
- Separate administrator login
- Administrative dashboard
- Authentication checks and protected access
- Session handling
- PHP backend/API logic
- Database connection and persistent data
- Charts and reporting support
- Responsive web interface

## Project structure

Key files include:

- `index.html` — main entry page
- `register.html` / `register.php` — user registration
- `login.php` — user authentication
- `dashboard.html` — user dashboard
- `admin-login.html` / `admin_login.php` — administrator authentication
- `admin.html` / `admin_api.php` — administrative interface and API logic
- `auth_check.php` — authentication validation
- `db_connect.php` — database connection setup
- `chart.js` — charting library used by the project
- `images/` — image assets

## Technology

- HTML
- CSS
- JavaScript
- PHP
- Database-backed authentication
- Sessions and access control
- Chart/reporting components

## Running locally

1. Clone or download the repository.
2. Place the project in a PHP-capable local web server environment such as XAMPP, WAMP, or Laragon.
3. Start the web server and MySQL/MariaDB service.
4. Create a local database named `coursematch_db`, which matches the default database name configured in `db_connect.php`.
5. If your local database username, password, host, or database name differs from the defaults, update the corresponding values in `db_connect.php` before running the application.
6. Import or create the database tables required by your local copy of the project.
7. Open the project through your local server URL rather than opening the HTML/PHP files directly from the filesystem.

### Default local database configuration

The repository currently expects the common local-development defaults below:

- Host: `localhost`
- User: `root`
- Password: empty
- Database: `coursematch_db`
- Character set: `utf8mb4`

These values are suitable only as local-development defaults. Use environment-appropriate credentials and avoid committing production passwords or other secrets to the repository.

## Notes

This repository is an academic/software project. Review database credentials and environment-specific settings before deploying it publicly.
