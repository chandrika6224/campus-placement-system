<?php
// Determine base path dynamically
$depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
$base  = str_repeat('../', $depth);
$respondUrl = $base . 'chatbot/respond.php';
?>
<!-- CampusBot Floating Widget -->
<style>
#campusbot-btn {
    position:fixed;bottom:25px;right:25px;z-index:9999;
    width:58px;height:58px;border-radius:50%;
    background:linear-gradient(135deg,#1a237e,#3949ab);
    color:#fff;border:none;cursor:pointer;
    box-shadow:0 4px 20px rgba(63,81,181,0.5);
    font-size:1.6rem;display:flex;align-items:center;justify-content:center;
    transition:all 0.3s;
}
#campusbot-btn:hover { transform:scale(1.1);box-shadow:0 6px 25px rgba(63,81,181,0.7); }
#campusbot-btn .bot-badge {
    position:absolute;top:-4px;right:-4px;
    background:#e53935;color:#fff;border-radius:50%;
    width:20px;height:20px;font-size:0.65rem;font-weight:800;
    display:flex;align-items:center;justify-content:center;
    animation:pulse-badge 2s infinite;
}
@keyframes pulse-badge { 0%,100%{transform:scale(1)} 50%{transform:scale(1.2)} }

#campusbot-window {
    position:fixed;bottom:95px;right:25px;z-index:9999;
    width:370px;height:520px;
    background:#fff;border-radius:16px;
    box-shadow:0 10px 40px rgba(0,0,0,0.2);
    display:none;flex-direction:column;overflow:hidden;
    animation:slideUp 0.3s ease;
}
@keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

#campusbot-header {
    background:linear-gradient(135deg,#1a237e,#3949ab);
    padding:14px 18px;display:flex;align-items:center;gap:12px;
    flex-shrink:0;
}
#campusbot-header .bot-avatar {
    width:40px;height:40px;border-radius:50%;
    background:rgba(255,255,255,0.2);
    display:flex;align-items:center;justify-content:center;font-size:1.3rem;
    border:2px solid rgba(255,255,255,0.4);
}
#campusbot-header .bot-info { flex:1; }
#campusbot-header .bot-name { color:#fff;font-weight:700;font-size:0.95rem; }
#campusbot-header .bot-status { color:#69f0ae;font-size:0.75rem;display:flex;align-items:center;gap:4px; }
#campusbot-header .bot-status::before { content:'';width:7px;height:7px;background:#69f0ae;border-radius:50%;display:inline-block;animation:blink-dot 1.5s infinite; }
@keyframes blink-dot { 0%,100%{opacity:1} 50%{opacity:0.3} }
#campusbot-close { background:none;border:none;color:rgba(255,255,255,0.7);font-size:1.2rem;cursor:pointer;padding:4px;border-radius:4px;transition:all 0.2s; }
#campusbot-close:hover { color:#fff;background:rgba(255,255,255,0.15); }

#campusbot-messages {
    flex:1;overflow-y:auto;padding:15px;
    display:flex;flex-direction:column;gap:10px;
    background:#f8f9ff;
}
#campusbot-messages::-webkit-scrollbar { width:4px; }
#campusbot-messages::-webkit-scrollbar-thumb { background:#c5cae9;border-radius:2px; }

.bot-msg, .user-msg {
    max-width:85%;padding:10px 14px;border-radius:12px;
    font-size:0.88rem;line-height:1.55;word-wrap:break-word;
    animation:msgIn 0.2s ease;
}
@keyframes msgIn { from{opacity:0;transform:translateY(5px)} to{opacity:1;transform:translateY(0)} }
.bot-msg {
    background:#fff;color:#333;border-radius:4px 12px 12px 12px;
    box-shadow:0 1px 4px rgba(0,0,0,0.08);align-self:flex-start;
    border-left:3px solid #3f51b5;
}
.user-msg {
    background:linear-gradient(135deg,#1a237e,#3949ab);
    color:#fff;border-radius:12px 4px 12px 12px;
    align-self:flex-end;
}
.bot-msg a { color:#3f51b5;font-weight:600; }
.bot-msg strong { color:#1a237e; }
.typing-indicator { display:flex;gap:4px;padding:10px 14px;background:#fff;border-radius:4px 12px 12px 12px;align-self:flex-start;box-shadow:0 1px 4px rgba(0,0,0,0.08); }
.typing-indicator span { width:7px;height:7px;background:#c5cae9;border-radius:50%;animation:typing 1.2s infinite; }
.typing-indicator span:nth-child(2) { animation-delay:0.2s; }
.typing-indicator span:nth-child(3) { animation-delay:0.4s; }
@keyframes typing { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

#campusbot-quick-btns {
    padding:8px 12px;background:#fff;border-top:1px solid #f0f0f0;
    display:flex;gap:6px;flex-wrap:wrap;flex-shrink:0;
}
.quick-btn {
    padding:4px 10px;border-radius:12px;border:1px solid #c5cae9;
    background:#f5f5f5;color:#3f51b5;font-size:0.75rem;font-weight:600;
    cursor:pointer;transition:all 0.2s;white-space:nowrap;
}
.quick-btn:hover { background:#e8eaf6;border-color:#3f51b5; }

#campusbot-input-area {
    padding:12px;background:#fff;border-top:1px solid #f0f0f0;
    display:flex;gap:8px;align-items:center;flex-shrink:0;
}
#campusbot-input {
    flex:1;padding:9px 14px;border:1.5px solid #e0e0e0;border-radius:20px;
    font-size:0.88rem;outline:none;transition:border 0.2s;font-family:inherit;
}
#campusbot-input:focus { border-color:#3f51b5;box-shadow:0 0 0 3px rgba(63,81,181,0.1); }
#campusbot-send {
    width:38px;height:38px;border-radius:50%;
    background:linear-gradient(135deg,#1a237e,#3949ab);
    color:#fff;border:none;cursor:pointer;font-size:1rem;
    display:flex;align-items:center;justify-content:center;
    transition:all 0.2s;flex-shrink:0;
}
#campusbot-send:hover { transform:scale(1.1); }
#campusbot-send:disabled { background:#ccc;cursor:not-allowed;transform:none; }

@media(max-width:420px) {
    #campusbot-window { width:calc(100vw - 20px);right:10px;bottom:80px; }
}
</style>

<!-- Bot Toggle Button -->
<button id="campusbot-btn" onclick="toggleBot()" title="Chat with CampusBot">
    <span id="bot-icon">💬</span>
    <span class="bot-badge" id="bot-badge">1</span>
</button>

<!-- Chat Window -->
<div id="campusbot-window">
    <div id="campusbot-header">
        <div class="bot-avatar">🤖</div>
        <div class="bot-info">
            <div class="bot-name">CampusBot</div>
            <div class="bot-status">Online — Ready to help</div>
        </div>
        <button id="campusbot-close" onclick="toggleBot()">✕</button>
    </div>

    <div id="campusbot-messages">
        <!-- Welcome message injected by JS -->
    </div>

    <div id="campusbot-quick-btns">
        <button class="quick-btn" onclick="sendQuick('jobs')">💼 Jobs</button>
        <button class="quick-btn" onclick="sendQuick('how to apply')">📋 Apply</button>
        <button class="quick-btn" onclick="sendQuick('tests')">📝 Tests</button>
        <button class="quick-btn" onclick="sendQuick('interview')">🎥 Interview</button>
        <button class="quick-btn" onclick="sendQuick('placement prediction')">🔮 Prediction</button>
        <button class="quick-btn" onclick="sendQuick('skill gap')">🧩 Skills</button>
        <button class="quick-btn" onclick="sendQuick('help')">❓ Help</button>
    </div>

    <div id="campusbot-input-area">
        <input type="text" id="campusbot-input" placeholder="Ask me anything..." maxlength="300"
            onkeydown="if(event.key==='Enter') sendMessage()">
        <button id="campusbot-send" onclick="sendMessage()">➤</button>
    </div>
</div>

<script>
const RESPOND_URL = '<?= $respondUrl ?>';
let botOpen = false;
let firstOpen = true;

const welcomeMessages = {
    student:   "👋 Hi <?= htmlspecialchars($_SESSION['name'] ?? 'there') ?>! I'm **CampusBot**. I can help you with jobs, applications, tests, interviews, and more. What do you need? 😊",
    recruiter: "👋 Hello <?= htmlspecialchars($_SESSION['name'] ?? 'there') ?>! I'm **CampusBot**. I can help you post jobs, manage applications, and schedule interviews. How can I assist? 🏢",
    admin:     "👋 Hi Admin! I'm **CampusBot**. Ask me about managing students, tests, interviews, or reports. How can I help? 🛡️",
    guest:     "👋 Hello! I'm **CampusBot**, your placement assistant. I can help with registration, jobs, and more. Type **help** to get started! 🎓",
};
const role = '<?= $_SESSION['role'] ?? 'guest' ?>';

function toggleBot() {
    botOpen = !botOpen;
    const win = document.getElementById('campusbot-window');
    const icon = document.getElementById('bot-icon');
    const badge = document.getElementById('bot-badge');
    win.style.display = botOpen ? 'flex' : 'none';
    icon.textContent = botOpen ? '✕' : '💬';
    badge.style.display = 'none';

    if (botOpen && firstOpen) {
        firstOpen = false;
        const msg = welcomeMessages[role] || welcomeMessages.guest;
        appendBotMessage(msg);
    }
    if (botOpen) document.getElementById('campusbot-input').focus();
}

function appendBotMessage(text) {
    const msgs = document.getElementById('campusbot-messages');
    const div = document.createElement('div');
    div.className = 'bot-msg';
    div.innerHTML = formatMessage(text);
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
}

function appendUserMessage(text) {
    const msgs = document.getElementById('campusbot-messages');
    const div = document.createElement('div');
    div.className = 'user-msg';
    div.textContent = text;
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
}

function showTyping() {
    const msgs = document.getElementById('campusbot-messages');
    const div = document.createElement('div');
    div.className = 'typing-indicator';
    div.id = 'typing-indicator';
    div.innerHTML = '<span></span><span></span><span></span>';
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
}

function hideTyping() {
    const el = document.getElementById('typing-indicator');
    if (el) el.remove();
}

function formatMessage(text) {
    // Convert markdown-like syntax to HTML
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_top">$1</a>')
        .replace(/\n/g, '<br>');
}

function sendMessage() {
    const input = document.getElementById('campusbot-input');
    const msg = input.value.trim();
    if (!msg) return;

    appendUserMessage(msg);
    input.value = '';
    document.getElementById('campusbot-send').disabled = true;

    showTyping();

    // Simulate slight delay for natural feel
    setTimeout(() => {
        fetch(RESPOND_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'message=' + encodeURIComponent(msg)
        })
        .then(r => r.json())
        .then(data => {
            hideTyping();
            appendBotMessage(data.reply || "Sorry, I couldn't process that.");
            document.getElementById('campusbot-send').disabled = false;
        })
        .catch(() => {
            hideTyping();
            appendBotMessage("⚠️ Connection error. Please try again.");
            document.getElementById('campusbot-send').disabled = false;
        });
    }, 600 + Math.random() * 400);
}

function sendQuick(text) {
    document.getElementById('campusbot-input').value = text;
    sendMessage();
}

// Show badge after 3 seconds if not opened
setTimeout(() => {
    if (!botOpen) {
        document.getElementById('bot-badge').style.display = 'flex';
    }
}, 3000);
</script>
