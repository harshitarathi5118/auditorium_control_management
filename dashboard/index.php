<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auditorium Control System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand">Auditorium Control System</div>
        <nav class="nav">
            <a href="index.php" class="active">Home</a>
            <a href="events.php">Events</a>
            <a href="bookings.php">Bookings</a>
            <a href="admin.php">Admin</a>
        </nav>
    </div>

    <div class="card-grid">
        <div class="card">
            <div class="card-title">Events</div>
            <p>Create and manage events that will take place in your auditoriums.</p>
            <div class="actions"><a class="btn" href="events.php">Go to Events</a></div>
        </div>
        <div class="card">
            <div class="card-title">Bookings</div>
            <p>Assign auditoriums to events and review existing bookings.</p>
            <div class="actions"><a class="btn" href="bookings.php">Go to Bookings</a></div>
        </div>
        <div class="card">
            <div class="card-title">Admin</div>
            <p>Manage organizers, auditoriums, equipment, and staff.</p>
            <div class="actions"><a class="btn" href="admin.php">Go to Admin</a></div>
        </div>
    </div>
</div>
</body>
</html>
