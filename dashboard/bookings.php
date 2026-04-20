<?php
// dashboard/bookings.php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bookings - ACM Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand">Auditorium Control System</div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="events.php">Events</a>
            <a href="bookings.php" class="active">Bookings</a>
            <a href="admin.php">Admin</a>
        </nav>
    </div>

    <div class="card">
        <div class="card-title">Create Booking</div>
        <div id="booking-message" class="message"></div>
        <form id="booking-form">
            <div class="form-group">
                <label for="event_id">Event</label>
                <select id="event_id" required>
                    <option value="">Loading events...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="auditorium_id">Auditorium</label>
                <select id="auditorium_id" required>
                    <option value="">Loading auditoriums...</option>
                </select>
            </div>
            <div class="actions"><button type="submit" class="btn">Create Booking</button></div>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-title">Bookings list</div>
        <div id="bookings-loading">Loading bookings...</div>
        <table id="bookings-table" class="table" style="display:none;">
            <thead>
                <tr>
                    <th>Booking Date</th>
                    <th>Event</th>
                    <th>Auditorium</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<script>
const apiBase = '../api';
function showMessage(el, text, type='') {
    el.textContent = text;
    el.className = 'message ' + type;
}
async function loadEventsForSelect() {
    const sel = document.getElementById('event_id');
    sel.innerHTML = '<option value="">Loading events...</option>';
    try {
        const res = await fetch(apiBase + '/events.php');
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Failed to load events');
        sel.innerHTML = '<option value="">-- Select event --</option>';
        const now = new Date();
        json.data.forEach(ev => {
            const eventDateTime = new Date(ev.event_date + 'T' + ev.start_time);
            if (eventDateTime > now) {
                const opt = document.createElement('option');
                opt.value = ev.event_id;
                opt.textContent = ev.event_name + ' (' + ev.event_date + ' ' + ev.start_time + ')';
                sel.appendChild(opt);
            }
        });
        if (sel.options.length === 1) {
            sel.innerHTML = '<option value="">No upcoming events available</option>';
        }
    } catch (err) {
        sel.innerHTML = '<option value="">Error loading events</option>';
        console.error(err);
    }
}
async function loadAuditoriumsForSelect() {
    const sel = document.getElementById('auditorium_id');
    sel.innerHTML = '<option value="">Loading auditoriums...</option>';
    try {
        const res = await fetch(apiBase + '/auditoriums.php');
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Failed to load auditoriums');
        sel.innerHTML = '<option value="">-- Select auditorium --</option>';
        json.data.forEach(a => {
            const opt = document.createElement('option');
            opt.value = a.auditorium_id;
            opt.textContent = a.auditorium_name + (a.capacity ? ' ('+a.capacity+' seats)' : '');
            sel.appendChild(opt);
        });
    } catch (err) {
        sel.innerHTML = '<option value="">Error loading auditoriums</option>';
        console.error(err);
    }
}
document.getElementById('event_id').addEventListener('change', async function() {
    const msg = document.getElementById('booking-message');
    const eventId = parseInt(this.value || 0, 10);
    if (!eventId) return;
    showMessage(msg, 'Checking auditorium availability...', 'info');
    try {
        const res = await fetch(apiBase + '/bookings.php');
        const json = await res.json();
        if (!json.success) throw new Error('Failed to fetch bookings');
        const sel = document.getElementById('auditorium_id');
        for (let i=0;i<sel.options.length;i++) sel.options[i].disabled = false;
        const evRes = await fetch(apiBase + '/events.php');
        const evJson = await evRes.json();
        if (!evJson.success) throw new Error('Failed to fetch event details');
        const selectedEvent = evJson.data.find(x => parseInt(x.event_id) === eventId);
        if (!selectedEvent) { showMessage(msg, 'Selected event not found', 'error'); return; }
        const newStart = selectedEvent.start_time;
        const newEnd = selectedEvent.end_time;
        const date = selectedEvent.event_date;
        json.data.forEach(b => {
            if (b.event_date === date) {
                if (newStart < b.end_time && newEnd > b.start_time) {
                    for (let i=0;i<sel.options.length;i++){
                        if (parseInt(sel.options[i].value) === parseInt(b.auditorium_id)) {
                            sel.options[i].disabled = true;
                        }
                    }
                }
            }
        });
        showMessage(msg, 'Some auditoriums may be unavailable due to conflicts', 'info');
    } catch (err) {
        showMessage(msg, 'Network error checking availability', 'error');
        console.error(err);
    }
});
async function loadBookings() {
    const tbl = document.getElementById('bookings-table');
    const body = tbl.querySelector('tbody');
    const loading = document.getElementById('bookings-loading');
    loading.style.display = 'block';
    tbl.style.display = 'none';
    body.innerHTML = '';
    try {
        const res = await fetch(apiBase + '/bookings.php');
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Failed to load bookings');
        if (!json.data || json.data.length === 0) {
            loading.textContent = 'No bookings found.';
            tbl.querySelector('tbody').innerHTML = '<tr><td colspan="4">No data available</td></tr>';
            return;
        }
        loading.style.display = 'none';
        tbl.style.display = 'table';
        json.data.forEach(b => {
            const tr = document.createElement('tr');
            const dtd = document.createElement('td');
            dtd.textContent = b.booking_date;
            const etd = document.createElement('td');
            etd.textContent = b.event_name;
            const atd = document.createElement('td');
            atd.textContent = b.auditorium_name;
            const actionsTd = document.createElement('td');
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel Booking';
            cancelBtn.className = 'btn';
            cancelBtn.style.background = '#8a1f1f';
            cancelBtn.addEventListener('click', async () => {
                if (!confirm('Cancel this booking?')) return;
                try {
                    const res = await fetch(apiBase + '/bookings.php', { method: 'DELETE', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'booking_id=' + encodeURIComponent(b.booking_id) });
                    const jsonDel = await res.json();
                    if (jsonDel.success) {
                        await loadBookings();
                        await loadEventsForSelect();
                    } else {
                        alert(jsonDel.message || 'Failed to cancel');
                    }
                } catch (err) {
                    alert('Network error');
                }
            });
            actionsTd.appendChild(cancelBtn);
            tr.appendChild(dtd);
            tr.appendChild(etd);
            tr.appendChild(atd);
            tr.appendChild(actionsTd);
            body.appendChild(tr);
        });
    } catch (err) {
        loading.textContent = 'Error loading bookings';
        console.error(err);
    }
}
document.getElementById('booking-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('booking-message');
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    showMessage(msg, 'Creating booking...', 'info');
    const payload = {
        event_id: parseInt(document.getElementById('event_id').value || 0, 10),
        auditorium_id: parseInt(document.getElementById('auditorium_id').value || 0, 10)
    };
    try {
        const res = await fetch(apiBase + '/bookings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
            showMessage(msg, 'Booking created successfully.', 'success');
            await loadEventsForSelect();
            await loadBookings();
        } else {
            showMessage(msg, json.message || 'Failed to create booking', 'error');
        }
    } catch (err) {
        showMessage(msg, 'Network error. Please try again.', 'error');
        console.error(err);
    } finally {
        btn.disabled = false;
    }
});
loadEventsForSelect();
loadAuditoriumsForSelect();
loadBookings();
</script>
</body>
</html>
