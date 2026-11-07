(function () {
  var chat = document.getElementById('chat');
  if (!chat) return;

  var matchId = chat.dataset.match;
  var csrf = chat.dataset.csrf;
  var lastId = parseInt(chat.dataset.last, 10) || 0;
  var box = document.getElementById('chat-box');
  var form = document.getElementById('chat-form');
  var input = document.getElementById('chat-input');
  var sending = false;

  function toBottom() { box.scrollTop = box.scrollHeight; }
  toBottom();

  function addMessage(m) {
    if (m.id <= lastId) return;
    lastId = m.id;
    var empty = document.getElementById('chat-empty');
    if (empty) empty.remove();
    var div = document.createElement('div');
    div.className = 'bubble ' + (m.mine ? 'mine' : 'theirs');
    div.appendChild(document.createTextNode(m.body));
    var t = document.createElement('time');
    t.textContent = m.time;
    div.appendChild(t);
    box.appendChild(div);
  }

  // Check for new messages every 3 seconds
  function poll() {
    if (document.hidden) return;
    fetch('api/messages.php?match=' + encodeURIComponent(matchId) + '&after=' + lastId, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.messages && d.messages.length) {
          var nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 80;
          d.messages.forEach(addMessage);
          if (nearBottom) toBottom();
        }
      })
      .catch(function () { /* try again next time */ });
  }
  setInterval(poll, 3000);

  // Send without reloading the page
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var body = input.value.trim();
    if (!body || sending) return;
    sending = true;
    fetch('api/messages.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ match_id: parseInt(matchId, 10), body: body })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.error) { alert(d.error); return; }
        addMessage(d.message);
        input.value = '';
        input.style.height = '';
        toBottom();
      })
      .catch(function () { alert('Message not sent. Check your connection and try again.'); })
      .then(function () { sending = false; input.focus(); });
  });

  // Enter sends, Shift+Enter adds a new line
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      form.dispatchEvent(new Event('submit', { cancelable: true }));
    }
  });
  input.addEventListener('input', function () {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 120) + 'px';
  });
})();
