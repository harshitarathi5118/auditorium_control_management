# Auditorium Control and Management (ACM)

Simple PHP + MySQL application to manage events, auditoriums, organizers, bookings, equipment and staff.

## Features

- Event management (create, update, delete)
- Auditorium management (create, update, delete)
- Organizer management (create, update, delete)
- Bookings (create, list, cancel)
- Equipment and staff management (basic CRUD)

## Tech Stack

- PHP (no frameworks)
- MySQL / MariaDB
- HTML, CSS, JavaScript (vanilla)

## Setup

1. Install XAMPP (or similar) with PHP and MySQL.
2. Copy the `auditorium-web-system` folder into `C:/xampp/htdocs/`.
3. Import `sql/database.sql` into your MySQL server (phpMyAdmin or mysql CLI).
4. Ensure `config/database.php` contains correct DB credentials (or use .env.example).
5. Start Apache & MySQL and visit: http://localhost/auditorium-web-system/dashboard/index.php

## API Overview

All API endpoints return JSON with format:

```
{ "success": true/false, "data": [], "message": "" }
```

Endpoints:
- `api/events.php` - GET, POST, PUT, DELETE
- `api/organizers.php` - GET, POST, PUT, DELETE
- `api/auditoriums.php` - GET, POST, PUT, DELETE
- `api/bookings.php` - GET, POST, DELETE
- `api/equipment.php` - GET, POST, PUT, DELETE
- `api/staff.php` - GET, POST, PUT, DELETE

## Demo

See `demo_script.txt` for a guided demo flow.

## Notes

This project is intentionally minimal and framework-free for ease of handover and learning.
