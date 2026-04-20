# Auditorium Control and Management (ACM)

Simple PHP + MySQL application to manage events, auditoriums, organizers, bookings, equipment and staff.
The Auditorium Control and Management System is a DBMS-based project designed to efficiently manage auditorium bookings, scheduling, and event coordination. It helps administrators handle reservations, avoid scheduling conflicts, and maintain organized records.


## Features

- Event management (create, update, delete)
- Auditorium management (create, update, delete)
- Organizer management (create, update, delete)
- Bookings (create, list, cancel)
- Equipment and staff management (basic CRUD)
-  Features



## Tech Stack

- Frontend: HTML, CSS, JavaScript
- Backend: Node.js / PHP (based on your project)
- Database: MySQL
- Tools: XAMPP / VS Code

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



## Project Structure

├── api/            # Backend logic
├── assets/         # Images, CSS, JS files
├── config/         # Configuration files
├── dashboard/      # User interface
├── sql/            # Database scripts
├── index           # Entry point
├── README.md       # Project documentation



## Installation & Setup

1. Clone the repository:

git clone https://github.com/harshitarathi5118/auditorium_control_management.git

2. Move to project folder:

cd auditorium_control_management

3. Setup database:

- Import SQL file from "/sql" folder into MySQL

4. Configure environment:

- Rename ".env.example" to ".env"
- Add your database credentials

5. Run the project:

- Start XAMPP (Apache & MySQL)
- Open in browser:

http://localhost/auditorium-web-system


## Screenshots

<img width="1902" height="918" alt="image" src="https://github.com/user-attachments/assets/7ee8501d-fbb1-4256-8b07-51a6cacd91b8" />
<img width="1919" height="763" alt="image" src="https://github.com/user-attachments/assets/be2f5676-cbc5-47f4-bb3b-67a80250c98a" />
<img width="1896" height="371" alt="image" src="https://github.com/user-attachments/assets/00adc5bd-b265-4eac-bc40-81ca4706187e" />
<img width="1834" height="785" alt="image" src="https://github.com/user-attachments/assets/e5476098-d79c-4e87-bdf2-c4bb068c2658" />
<img width="1911" height="904" alt="image" src="https://github.com/user-attachments/assets/7bfcce4b-cdb2-41fe-aef1-20251d404aea" />
<img width="1919" height="910" alt="image" src="https://github.com/user-attachments/assets/7b390529-5555-4f22-8830-6461317c1f57" />


## Use Cases

- College auditorium booking
- Event management system
- Conference scheduling

## Future Improvements

- Mobile-friendly UI
- Online payment integration
- Notification system


