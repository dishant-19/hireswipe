(function () {
  var app = document.getElementById('swipe-app');
  if (!app) return;

  var csrf = app.dataset.csrf;
  var deck = document.getElementById('deck');
  var emptyBox = document.getElementById('empty');
  var actions = document.getElementById('actions');
  var modal = document.getElementById('match-modal');
  var cards = [];
  var busy = false;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function post(url, data) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(data || {})
    }).then(function (r) { return r.json(); });
  }

  function cardHTML(c) {
    var img = c.image
      ? '<img class="card-img" src="' + esc(c.image) + '" alt="">'
      : '<div class="card-img card-img-text">' + esc(c.initials) + '</div>';
    var tags = (c.tags || []).filter(Boolean).map(function (t) { return '<span class="tag">' + esc(t) + '</span>'; }).join('');
    var facts = (c.facts || []).map(function (f) {
      return '<div class="fact"><span>' + esc(f[0]) + '</span><strong>' + esc(f[1]) + '</strong></div>';
    }).join('');
    return '' +
      '<div class="stamp stamp-like">LIKE</div><div class="stamp stamp-nope">PASS</div>' +
      img +
      '<div class="card-body">' +
        '<div class="score" title="How well you fit each other">' + c.score + '% fit</div>' +
        '<h3>' + esc(c.title) + '</h3>' +
        '<p class="card-sub">' + esc(c.subtitle) + ' · ' + esc(c.location) + '</p>' +
        '<div class="tags">' + tags + '</div>' +
        '<div class="facts">' + facts + '</div>' +
        (c.text ? '<p class="card-text">' + esc(c.text) + '</p>' : '') +
      '</div>';
  }

  function render() {
    deck.innerHTML = '';
    if (!cards.length) {
      emptyBox.hidden = false;
      actions.hidden = true;
      return;
    }
    emptyBox.hidden = true;
    actions.hidden = false;
    // Show up to three cards stacked; the first one is on top
    cards.slice(0, 3).reverse().forEach(function (c, i, arr) {
      var depth = arr.length - 1 - i;
      var el = document.createElement('div');
      el.className = 'swipe-card';
      el.dataset.depth = depth;
      el.innerHTML = cardHTML(c);
      deck.appendChild(el);
      if (depth === 0) attachDrag(el);
    });
  }

  function attachDrag(el) {
    var startX = 0, dx = 0, dragging = false;
    var likeStamp = el.querySelector('.stamp-like');
    var nopeStamp = el.querySelector('.stamp-nope');

    el.addEventListener('pointerdown', function (e) {
      if (busy) return;
      dragging = true; startX = e.clientX; dx = 0;
      el.classList.add('dragging');
      el.setPointerCapture(e.pointerId);
    });
    el.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      dx = e.clientX - startX;
      el.style.transform = 'translateX(' + dx + 'px) rotate(' + (dx / 18) + 'deg)';
      likeStamp.style.opacity = Math.max(0, Math.min(1, dx / 100));
      nopeStamp.style.opacity = Math.max(0, Math.min(1, -dx / 100));
    });
    function end() {
      if (!dragging) return;
      dragging = false;
      el.classList.remove('dragging');
      if (dx > 110) decide('like');
      else if (dx < -110) decide('pass');
      else {
        el.style.transform = '';
        likeStamp.style.opacity = 0;
        nopeStamp.style.opacity = 0;
      }
    }
    el.addEventListener('pointerup', end);
    el.addEventListener('pointercancel', end);
  }

  function decide(direction) {
    if (busy || !cards.length) return;
    busy = true;
    var card = cards[0];
    var top = deck.querySelector('.swipe-card[data-depth="0"]');
    if (top) {
      top.classList.remove('dragging');
      top.classList.add('fly');
      var stamp = top.querySelector(direction === 'like' ? '.stamp-like' : '.stamp-nope');
      if (stamp) stamp.style.opacity = 1;
      top.style.transform = 'translateX(' + (direction === 'like' ? 700 : -700) + 'px) rotate(' + (direction === 'like' ? 30 : -30) + 'deg)';
      top.style.opacity = 0;
    }
    post('api/swipe.php', { target_id: card.id, direction: direction }).then(function (res) {
      if (res.error) { alert(res.error); }
      else if (res.matched) showMatch(res);
    }).catch(function () {
      alert('Could not save your swipe. Check your connection and try again.');
    }).then(function () {
      setTimeout(function () {
        cards.shift();
        busy = false;
        render();
      }, 250);
    });
  }

  function showMatch(res) {
    document.getElementById('match-text').textContent = 'You and ' + res.name + ' liked each other.';
    document.getElementById('match-chat').href = 'chat.php?match=' + res.match_id;
    modal.hidden = false;
  }

  document.getElementById('btn-like').addEventListener('click', function () { decide('like'); });
  document.getElementById('btn-pass').addEventListener('click', function () { decide('pass'); });
  document.getElementById('match-close').addEventListener('click', function () { modal.hidden = true; });
  document.getElementById('reset-btn').addEventListener('click', function () {
    post('api/reset.php').then(load);
  });
  document.addEventListener('keydown', function (e) {
    if (!modal.hidden) { if (e.key === 'Escape') modal.hidden = true; return; }
    if (e.key === 'ArrowRight') decide('like');
    if (e.key === 'ArrowLeft') decide('pass');
  });

  function load() {
    fetch('api/deck.php', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) { cards = d.cards || []; render(); })
      .catch(function () { deck.innerHTML = '<p class="alert alert-error">Could not load profiles. Please refresh.</p>'; });
  }
  load();
})();
