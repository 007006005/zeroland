/* Helper condivisi dal portale: richieste JSON, escape HTML, notifiche. */
(function () {
  window.esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  window.api = async function (url, body) {
    var opt = body === undefined
      ? { credentials: 'same-origin' }
      : { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) };
    var res, data;
    try { res = await fetch(url, opt); } catch (e) { return { success: false, error: 'Connessione assente.' }; }
    try { data = await res.json(); } catch (e) { return { success: false, error: 'Risposta del server non valida (HTTP ' + res.status + ').' }; }
    if (res.status === 401 && data.login_required) { location.href = 'index.php?login_required=1'; }
    return data;
  };
  var tt;
  window.toast = function (msg) {
    var t = document.getElementById('toast');
    if (!t) { t = document.createElement('div'); t.id = 'toast'; document.body.appendChild(t); }
    t.textContent = msg; t.style.display = 'block';
    clearTimeout(tt); tt = setTimeout(function () { t.style.display = 'none'; }, 3200);
  };
  window.logout = async function () {
    await api('../api/auth.php', { action: 'logout' });
    location.href = 'index.php';
  };
})();
