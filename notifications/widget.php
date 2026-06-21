<?php
$fetchUrl     = '/placement/notifications/fetch.php';
$markReadUrl  = '/placement/notifications/mark_read.php';
$notifPageUrl = '/placement/notifications/index.php';
?>
<style>
.notif-bell-wrap { position:relative;display:inline-flex;align-items:center; }
.notif-bell-btn {
    background:rgba(255,255,255,0.12);border:none;color:#fff;
    width:38px;height:38px;border-radius:8px;cursor:pointer;
    font-size:1.15rem;display:flex;align-items:center;justify-content:center;
    transition:all 0.2s;position:relative;
}
.notif-bell-btn:hover { background:rgba(255,255,255,0.22); }
.notif-count-badge {
    position:absolute;top:-5px;right:-5px;
    background:#e53935;color:#fff;border-radius:50%;
    min-width:18px;height:18px;font-size:0.65rem;font-weight:800;
    display:none;align-items:center;justify-content:center;
    padding:0 3px;border:2px solid #1a237e;
    animation:notif-pop 0.3s ease;
}
@keyframes notif-pop { from{transform:scale(0)} to{transform:scale(1)} }

.notif-dropdown {
    position:absolute;top:48px;right:0;z-index:9998;
    width:340px;background:#fff;border-radius:12px;
    box-shadow:0 8px 30px rgba(0,0,0,0.18);
    display:none;flex-direction:column;overflow:hidden;
    animation:dropIn 0.2s ease;
}
@keyframes dropIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
.notif-dropdown.open { display:flex; }

.notif-drop-header {
    padding:12px 16px;background:linear-gradient(135deg,#1a237e,#3949ab);
    display:flex;justify-content:space-between;align-items:center;
}
.notif-drop-header span { color:#ffd54f;font-weight:700;font-size:0.95rem; }
.notif-mark-all-btn {
    background:rgba(255,255,255,0.15);border:none;color:#fff;
    padding:4px 10px;border-radius:10px;cursor:pointer;font-size:0.75rem;
    font-weight:600;transition:all 0.2s;
}
.notif-mark-all-btn:hover { background:rgba(255,255,255,0.25); }

.notif-drop-list { max-height:320px;overflow-y:auto; }
.notif-drop-list::-webkit-scrollbar { width:4px; }
.notif-drop-list::-webkit-scrollbar-thumb { background:#c5cae9;border-radius:2px; }

.notif-drop-item {
    display:flex;gap:10px;align-items:flex-start;
    padding:11px 14px;border-bottom:1px solid #f0f0f0;
    cursor:pointer;transition:background 0.15s;
}
.notif-drop-item:hover { background:#f5f5ff; }
.notif-drop-item.unread { background:#f8f9ff;border-left:3px solid #3f51b5; }
.notif-drop-item.read { border-left:3px solid transparent; }
.notif-drop-icon { font-size:1.2rem;margin-top:2px;flex-shrink:0; }
.notif-drop-body { flex:1;min-width:0; }
.notif-drop-title { font-weight:700;font-size:0.85rem;color:#1a237e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.notif-drop-msg { font-size:0.78rem;color:#666;margin-top:2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.notif-drop-time { font-size:0.72rem;color:#999;margin-top:3px; }
.notif-drop-dot { width:8px;height:8px;background:#3f51b5;border-radius:50%;flex-shrink:0;margin-top:6px; }

.notif-drop-footer {
    padding:10px;text-align:center;border-top:1px solid #f0f0f0;
    background:#fafafa;
}
.notif-drop-footer a {
    color:#3f51b5;font-size:0.85rem;font-weight:700;text-decoration:none;
}
.notif-drop-footer a:hover { text-decoration:underline; }

.notif-empty { padding:30px;text-align:center;color:#999;font-size:0.88rem; }
</style>

<div class="notif-bell-wrap" id="notif-wrap">
    <button class="notif-bell-btn" onclick="toggleNotifDropdown(event)" title="Notifications">
        🔔
        <span class="notif-count-badge" id="notif-badge">0</span>
    </button>
    <div class="notif-dropdown" id="notif-dropdown">
        <div class="notif-drop-header">
            <span>🔔 Notifications <span id="notif-header-count" style="background:rgba(255,255,255,0.2);padding:1px 7px;border-radius:10px;font-size:0.78rem"></span></span>
            <button class="notif-mark-all-btn" onclick="markAllRead()">✓ Mark all read</button>
        </div>
        <div class="notif-drop-list" id="notif-list">
            <div class="notif-empty">Loading...</div>
        </div>
        <div class="notif-drop-footer">
            <a href="<?= $notifPageUrl ?>">View all notifications →</a>
        </div>
    </div>
</div>

<script>
const NOTIF_FETCH_URL    = '<?= $fetchUrl ?>';
const NOTIF_MARK_URL     = '<?= $markReadUrl ?>';
const NOTIF_PAGE_URL     = '<?= $notifPageUrl ?>';
const NOTIF_TYPE_ICONS   = {job:'💼',application:'📋',interview:'🎥',test:'📝',notice:'📢',system:'🔔'};

let notifOpen = false;
let lastCount = 0;

function toggleNotifDropdown(e) {
    e.stopPropagation();
    notifOpen = !notifOpen;
    document.getElementById('notif-dropdown').classList.toggle('open', notifOpen);
    if (notifOpen) fetchNotifications();
}

document.addEventListener('click', function(e) {
    if (!document.getElementById('notif-wrap').contains(e.target)) {
        notifOpen = false;
        document.getElementById('notif-dropdown').classList.remove('open');
    }
});

function fetchNotifications() {
    fetch(NOTIF_FETCH_URL)
    .then(r => r.json())
    .then(data => {
        const count = data.count;
        const badge = document.getElementById('notif-badge');
        const headerCount = document.getElementById('notif-header-count');

        // Update badge
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
        headerCount.textContent = count > 0 ? count + ' unread' : '';

        // Play sound on new notification
        if (count > lastCount && lastCount !== -1) {
            playNotifSound();
        }
        lastCount = count;

        // Render list
        const list = document.getElementById('notif-list');
        if (!data.notifications || data.notifications.length === 0) {
            list.innerHTML = '<div class="notif-empty">🔔 No notifications yet</div>';
            return;
        }
        list.innerHTML = data.notifications.map(n => `
            <div class="notif-drop-item ${n.is_read ? 'read' : 'unread'}"
                 onclick="markOneRead(${n.id}, '${n.link.replace(/'/g,"\\'")}')">
                <div class="notif-drop-icon">${NOTIF_TYPE_ICONS[n.type] || '🔔'}</div>
                <div class="notif-drop-body">
                    <div class="notif-drop-title">${escHtml(n.title)}</div>
                    <div class="notif-drop-msg">${escHtml(n.message)}</div>
                    <div class="notif-drop-time">${n.time_ago}</div>
                </div>
                ${!n.is_read ? '<div class="notif-drop-dot"></div>' : ''}
            </div>
        `).join('');
    })
    .catch(() => {});
}

function markOneRead(id, link) {
    fetch(NOTIF_MARK_URL, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=one&id='+id
    }).then(() => {
        fetchNotifications();
        if (link) window.location.href = link;
    });
}

function markAllRead() {
    fetch(NOTIF_MARK_URL, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=all'
    }).then(() => {
        lastCount = -1;
        fetchNotifications();
    });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function playNotifSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.3);
    } catch(e) {}
}

// Initial fetch + poll every 30 seconds
fetchNotifications();
setInterval(fetchNotifications, 30000);
</script>
