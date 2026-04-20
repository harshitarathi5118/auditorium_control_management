<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - ACM Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand">Auditorium Control System</div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="events.php">Events</a>
            <a href="bookings.php">Bookings</a>
            <a href="admin.php" class="active">Admin</a>
        </nav>
    </div>

    <div class="card">
        <div class="card-title">Create Organizer</div>
        <div id="org-message" class="message"></div>
        <form id="org-form">
            <div class="form-group">
                <label for="org_name">Name</label>
                <input type="text" id="org_name" required>
            </div>
            <div class="form-group">
                <label for="org_department">Department</label>
                <input type="text" id="org_department">
            </div>
            <div class="form-group">
                <label for="org_contact">Contact</label>
                <input type="text" id="org_contact">
            </div>
            <div class="form-group">
                <label for="org_email">Email</label>
                <input type="email" id="org_email">
            </div>
            <div class="actions"><button type="submit" class="btn">Create Organizer</button></div>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-title">Organizers</div>
        <div id="organizers-loading">Loading organizers...</div>
        <table id="organizers-table" class="table" style="display:none;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div style="height:18px;"></div>

    <div class="card">
        <div class="card-title">Create Auditorium</div>
        <div id="aud-message" class="message"></div>
        <form id="aud-form">
            <div class="form-group">
                <label for="aud_name">Name</label>
                <input type="text" id="aud_name" required>
            </div>
            <div class="form-group">
                <label for="aud_location">Location</label>
                <input type="text" id="aud_location" required>
            </div>
            <div class="form-group">
                <label for="aud_capacity">Capacity</label>
                <input type="number" id="aud_capacity" required min="0">
            </div>
            <div class="form-group">
                <label for="aud_status">Status</label>
                <select id="aud_status">
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>
            <div class="actions"><button type="submit" class="btn">Create Auditorium</button></div>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-title">Auditoriums</div>
        <div id="auditoriums-loading">Loading auditoriums...</div>
        <table id="auditoriums-table" class="table" style="display:none;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Capacity</th>
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
function showMessage(el, text, type='') { el.textContent = text; el.className = 'message ' + type; }
async function fetchOrganizersList() {
    const loading = document.getElementById('organizers-loading'); const table = document.getElementById('organizers-table'); const tbody = table.querySelector('tbody'); loading.style.display='block'; table.style.display='none'; tbody.innerHTML='';
    try{ const res = await fetch(apiBase + '/organizers.php'); const json = await res.json(); if(!json.success) throw new Error(json.message||'Failed'); if(!json.data || json.data.length===0){ loading.textContent='No organizers found.'; tbody.innerHTML='<tr><td colspan="5">No data available</td></tr>'; return; } loading.style.display='none'; table.style.display='table'; json.data.forEach(o=>{ const tr=document.createElement('tr'); const ntd=document.createElement('td'); ntd.textContent=o.organizer_name; const dtd=document.createElement('td'); dtd.textContent=o.department||''; const ctd=document.createElement('td'); ctd.textContent=o.contact_number||''; const etd=document.createElement('td'); etd.textContent=o.email||''; const actionsTd=document.createElement('td'); const editBtn=document.createElement('button'); editBtn.textContent='Edit'; editBtn.className='btn'; editBtn.style.marginRight='8px'; editBtn.addEventListener('click', ()=>{ document.getElementById('org_name').value=o.organizer_name; document.getElementById('org_department').value=o.department||''; document.getElementById('org_contact').value=o.contact_number||''; document.getElementById('org_email').value=o.email||''; const submitBtn=document.querySelector('#org-form .btn'); submitBtn.textContent='Update Organizer'; submitBtn.dataset.editing = o.organizer_id; }); const delBtn=document.createElement('button'); delBtn.textContent='Delete'; delBtn.className='btn'; delBtn.style.background='#8a1f1f'; delBtn.style.marginLeft='8px'; delBtn.addEventListener('click', async ()=>{ if(!confirm('Delete this organizer?')) return; try{ const res = await fetch(apiBase + '/organizers.php',{ method:'DELETE', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: 'organizer_id=' + encodeURIComponent(o.organizer_id) }); const j=await res.json(); if(j.success){ fetchOrganizersList(); } else { alert(j.message||'Failed'); } }catch(err){ alert('Network error'); } }); actionsTd.appendChild(editBtn); actionsTd.appendChild(delBtn); tr.appendChild(ntd); tr.appendChild(dtd); tr.appendChild(ctd); tr.appendChild(etd); tr.appendChild(actionsTd); tbody.appendChild(tr); }); }catch(err){ loading.textContent='Error loading organizers'; console.error(err); } }
async function fetchAuditoriumsList() { const loading=document.getElementById('auditoriums-loading'); const table=document.getElementById('auditoriums-table'); const tbody=table.querySelector('tbody'); loading.style.display='block'; table.style.display='none'; tbody.innerHTML=''; try{ const res=await fetch(apiBase + '/auditoriums.php'); const json=await res.json(); if(!json.success) throw new Error(json.message||'Failed'); if(!json.data || json.data.length===0){ loading.textContent='No auditoriums found.'; tbody.innerHTML='<tr><td colspan="5">No data available</td></tr>'; return; } loading.style.display='none'; table.style.display='table'; json.data.forEach(a=>{ const tr=document.createElement('tr'); const ntd=document.createElement('td'); ntd.textContent=a.auditorium_name; const ltd=document.createElement('td'); ltd.textContent=a.location||''; const ctd=document.createElement('td'); ctd.textContent=a.capacity||''; const std=document.createElement('td'); std.textContent=a.status||''; const actionsTd=document.createElement('td'); const editBtn=document.createElement('button'); editBtn.textContent='Edit'; editBtn.className='btn'; editBtn.style.marginRight='8px'; editBtn.addEventListener('click', ()=>{ document.getElementById('aud_name').value=a.auditorium_name; document.getElementById('aud_location').value=a.location||''; document.getElementById('aud_capacity').value=a.capacity||''; document.getElementById('aud_status').value=a.status||'Available'; const submitBtn=document.querySelector('#aud-form .btn'); submitBtn.textContent='Update Auditorium'; submitBtn.dataset.editing = a.auditorium_id; }); const delBtn=document.createElement('button'); delBtn.textContent='Delete'; delBtn.className='btn'; delBtn.style.background='#8a1f1f'; delBtn.style.marginLeft='8px'; delBtn.addEventListener('click', async ()=>{ if(!confirm('Delete this auditorium?')) return; try{ const res=await fetch(apiBase + '/auditoriums.php',{ method:'DELETE', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: 'auditorium_id=' + encodeURIComponent(a.auditorium_id) }); const j=await res.json(); if(j.success){ fetchAuditoriumsList(); } else { alert(j.message||'Failed'); } }catch(err){ alert('Network error'); } }); actionsTd.appendChild(editBtn); actionsTd.appendChild(delBtn); tr.appendChild(ntd); tr.appendChild(ltd); tr.appendChild(ctd); tr.appendChild(std); tr.appendChild(actionsTd); tbody.appendChild(tr); }); }catch(err){ loading.textContent='Error loading auditoriums'; console.error(err); } }
document.getElementById('org-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('org-message');
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled=true;
    showMessage(msg,'Processing...','info');
    const payload = {
        organizer_name: document.getElementById('org_name').value.trim(),
        department: document.getElementById('org_department').value.trim(),
        contact_number: document.getElementById('org_contact').value.trim(),
        email: document.getElementById('org_email').value.trim()
    };
    const editingId = submitBtn.dataset.editing ? parseInt(submitBtn.dataset.editing,10):0;
    try{
        if(editingId){
            const bodyStr = 'organizer_id='+encodeURIComponent(editingId)+'&'+ new URLSearchParams(payload).toString();
            const res = await fetch(apiBase + '/organizers.php', {
                method:'PUT', headers:{ 'Content-Type': 'application/x-www-form-urlencoded' }, body: bodyStr
            });
            const j = await res.json();
            if(j.success){
                showMessage(msg,'Organizer updated','success');
                submitBtn.textContent='Create Organizer';
                delete submitBtn.dataset.editing;
                orgForm.reset();
                fetchOrganizersList();
            } else {
                showMessage(msg,j.message||'Failed','error');
            }
        } else {
            const res = await fetch(apiBase + '/organizers.php', {
                method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(payload)
            });
            const j = await res.json();
            if(j.success){
                showMessage(msg,'Organizer created','success');
                orgForm.reset();
                fetchOrganizersList();
            } else {
                showMessage(msg,j.message||'Failed','error');
            }
        }
    }catch(err){
        showMessage(msg,'Network error','error');
    } finally{
        submitBtn.disabled=false;
    }
});
document.getElementById('aud-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg=document.getElementById('aud-message');
    const submitBtn=this.querySelector('button[type="submit"]');
    submitBtn.disabled=true;
    showMessage(msg,'Processing...','info');
    const payload={
        auditorium_name: document.getElementById('aud_name').value.trim(),
        location: document.getElementById('aud_location').value.trim(),
        capacity: parseInt(document.getElementById('aud_capacity').value||0,10),
        status: document.getElementById('aud_status').value
    };
    const editingId = submitBtn.dataset.editing ? parseInt(submitBtn.dataset.editing,10):0;
    try{
        if(editingId){
            const bodyStr='auditorium_id='+encodeURIComponent(editingId)+'&'+ new URLSearchParams(payload).toString();
            const res=await fetch(apiBase + '/auditoriums.php',{ method:'PUT', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: bodyStr });
            const j=await res.json();
            if(j.success){
                showMessage(msg,'Auditorium updated','success');
                submitBtn.textContent='Create Auditorium';
                delete submitBtn.dataset.editing;
                audForm.reset();
                fetchAuditoriumsList();
            } else {
                showMessage(msg,j.message||'Failed','error');
            }
        } else {
            const res = await fetch(apiBase + '/auditoriums.php',{ method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(payload) });
            const j=await res.json();
            if(j.success){
                showMessage(msg,'Auditorium created','success');
                audForm.reset();
                fetchAuditoriumsList();
            } else {
                showMessage(msg,j.message||'Failed','error');
            }
        }
    }catch(err){
        showMessage(msg,'Network error','error');
    } finally{
        submitBtn.disabled=false;
    }
});
fetchOrganizersList(); fetchAuditoriumsList();
</script>
</body>
</html>
