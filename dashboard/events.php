<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Events - ACM Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand">Auditorium Control System</div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="events.php" class="active">Events</a>
            <a href="bookings.php">Bookings</a>
            <a href="admin.php">Admin</a>
        </nav>
    </div>

    <div class="card">
        <div class="card-title">Create Event</div>
        <div id="event-message" class="message"></div>
        <form id="event-form">
            <div class="form-group">
                <label for="event_name">Event name</label>
                <input type="text" id="event_name" name="event_name" required>
            </div>
            <div class="form-group">
                <label for="event_date">Event date</label>
                <input type="date" id="event_date" name="event_date" required>
            </div>
            <div class="form-group">
                <label for="start_time">Start time</label>
                <input type="time" id="start_time" name="start_time" required>
            </div>
            <div class="form-group">
                <label for="end_time">End time</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>
            <div class="form-group">
                <label for="organizer_id">Organizer</label>
                <select id="organizer_id" name="organizer_id" required>
                    <option value="">Loading organizers...</option>
                </select>
            </div>
            <div class="actions"><button type="submit" class="btn">Create event</button></div>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-title">Events list</div>
        <div id="events-loading">Loading events...</div>
        <table id="events-table" class="table" style="display:none;">
            <thead>
            <tr>
                <th>Event</th>
                <th>Date</th>
                <th>Time</th>
                <th>Organizer</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<script>
const apiBase = '../api';
const today = new Date().toISOString().split('T')[0];
document.getElementById('event_date').min = today;
const dateInput = document.getElementById('event_date');
const startTimeInput = document.getElementById('start_time');
const endTimeInput = document.getElementById('end_time');

dateInput.addEventListener('change', () => {
    const selectedDate = dateInput.value;
    const now = new Date();
    const todayStr = now.toISOString().split('T')[0];
    if (selectedDate === todayStr) {
        const currentTime = now.toTimeString().slice(0, 5);
        startTimeInput.min = currentTime;
    } else {
        startTimeInput.min = "";
    }
});
startTimeInput.addEventListener('change', () => {
    endTimeInput.min = startTimeInput.value;
    endTimeInput.value = "";
});
function showMessage(el, text, type='') { el.textContent = text; el.className = 'message ' + type; }
async function fetchOrganizers() { const sel = document.getElementById('organizer_id'); sel.innerHTML = '<option value="">Loading...</option>'; try { const res = await fetch(apiBase + '/organizers.php'); const json = await res.json(); if (!json.success) throw new Error(json.message || 'Failed to load organizers'); if (!json.data || json.data.length === 0) { sel.innerHTML = '<option value="">No organizers available</option>'; return; } sel.innerHTML = '<option value="">-- Select organizer --</option>'; json.data.forEach(o => { const opt = document.createElement('option'); opt.value = o.organizer_id; opt.textContent = o.organizer_name + (o.department ? ' ('+o.department+')' : ''); sel.appendChild(opt); }); } catch (err) { sel.innerHTML = '<option value="">Error loading organizers</option>'; console.error(err); } }
async function loadEvents() {
    const tbl = document.getElementById('events-table');
    const body = tbl.querySelector('tbody');
    const loading = document.getElementById('events-loading');
    loading.style.display = 'block';
    tbl.style.display = 'none';
    body.innerHTML = '';
    try {
        const res = await fetch(apiBase + '/events.php');
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Failed to load events');
        if (!json.data || json.data.length === 0) {
            loading.textContent = 'No events found.';
            tbl.querySelector('tbody').innerHTML = '<tr><td colspan="6">No data available</td></tr>';
            return;
        }
        const now = new Date();
        loading.style.display = 'none';
        tbl.style.display = 'table';
        json.data.forEach(ev => {
            const tr = document.createElement('tr');
            const nameTd = document.createElement('td'); nameTd.textContent = ev.event_name;
            const dateTd = document.createElement('td'); dateTd.textContent = ev.event_date;
            const timeTd = document.createElement('td'); timeTd.textContent = ev.start_time + ' - ' + ev.end_time;
            const orgTd = document.createElement('td'); orgTd.textContent = ev.organizer_name || '';
            const statusTd = document.createElement('td'); const eventDateTime = new Date(ev.event_date + 'T' + ev.start_time); statusTd.textContent = eventDateTime < now ? 'Completed' : 'Upcoming';
            const actionsTd = document.createElement('td');
            const editBtn = document.createElement('button'); editBtn.textContent='Edit'; editBtn.className='btn'; editBtn.style.marginRight='8px';
            editBtn.addEventListener('click', ()=>{
                document.getElementById('event_name').value = ev.event_name;
                document.getElementById('event_date').value = ev.event_date;
                document.getElementById('start_time').value = ev.start_time;
                document.getElementById('end_time').value = ev.end_time;
                document.getElementById('organizer_id').value = ev.organizer_id;
                const submitBtn = document.querySelector('#event-form .btn'); submitBtn.textContent='Update Event'; submitBtn.dataset.editing = ev.event_id;
            });
            const delBtn = document.createElement('button'); delBtn.textContent='Delete'; delBtn.className='btn'; delBtn.style.background='#8a1f1f'; delBtn.style.marginLeft='8px'; delBtn.addEventListener('click', async ()=>{
                if(!confirm('Are you sure you want to delete this event?')) return;
                try{ const res = await fetch(apiBase + '/events.php', { method:'DELETE', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: 'event_id=' + encodeURIComponent(ev.event_id) }); const jsonDel = await res.json(); if(jsonDel.success){ await loadEvents(); } else { alert(jsonDel.message||'Failed to delete'); } }catch(err){ alert('Network error'); }
            });
            actionsTd.appendChild(editBtn); actionsTd.appendChild(delBtn);
            tr.appendChild(nameTd); tr.appendChild(dateTd); tr.appendChild(timeTd); tr.appendChild(orgTd); tr.appendChild(statusTd); tr.appendChild(actionsTd);
            body.appendChild(tr);
        });
    } catch (err) {
        loading.textContent = 'Error loading events'; console.error(err);
    }
}
// single consolidated submit handler
const eventForm = document.getElementById('event-form');
if (!eventForm.dataset.handlerAttached) {
    eventForm.addEventListener('submit', async function(e){
        e.preventDefault();
        const msg = document.getElementById('event-message');
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true; showMessage(msg,'Processing...','info');
        const editingId = submitBtn.dataset.editing ? parseInt(submitBtn.dataset.editing,10):0;
        const payload = { event_name: document.getElementById('event_name').value.trim(), event_date: document.getElementById('event_date').value, start_time: document.getElementById('start_time').value, end_time: document.getElementById('end_time').value, organizer_id: parseInt(document.getElementById('organizer_id').value||0,10) };
        try{
            let res,json;
            if(editingId){ const bodyStr = 'event_id='+encodeURIComponent(editingId)+'&'+ new URLSearchParams(payload).toString(); res = await fetch(apiBase + '/events.php', { method:'PUT', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: bodyStr }); json = await res.json(); if(json.success){ showMessage(msg,'Event updated','success'); submitBtn.textContent='Create event'; delete submitBtn.dataset.editing; eventForm.reset(); await loadEvents(); } else { showMessage(msg,json.message||'Failed','error'); } }
            else { res = await fetch(apiBase + '/events.php', { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(payload) }); json = await res.json(); if(json.success){ showMessage(msg,'Event created successfully.','success'); eventForm.reset(); await loadEvents(); } else { showMessage(msg,json.message||'Failed to create','error'); } }
        } catch(err){ showMessage(msg,'Network error. Please try again.','error'); console.error(err); } finally{ submitBtn.disabled=false; }
    });
    eventForm.dataset.handlerAttached = '1';
}
fetchOrganizers(); loadEvents();
</script>
</body>
</html>