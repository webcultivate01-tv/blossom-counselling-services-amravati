      </main>
    </div>
  </div>

  <script>
  (function () {
    var body = document.body;
    function toggle(open) { body.classList.toggle('nav-open', open); }
    document.getElementById('menuBtn').addEventListener('click', function () { toggle(!body.classList.contains('nav-open')); });
    document.getElementById('sidebarBackdrop').addEventListener('click', function () { toggle(false); });
    document.getElementById('sidebarClose').addEventListener('click', function () { toggle(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggle(false); });

    var clock = document.getElementById('clock');
    function tick() {
      var d = new Date();
      clock.textContent = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) + ', ' +
        d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    tick(); setInterval(tick, 1000);

    // Confirm before destructive actions: <form data-confirm="Message">
    document.addEventListener('submit', function (e) {
      var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
      if (msg && !window.confirm(msg)) e.preventDefault();
    });
    // Toggle a hidden row/panel: <a data-open="element-id">
    document.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('[data-open]');
      if (!a) return;
      e.preventDefault();
      var el = document.getElementById(a.getAttribute('data-open'));
      if (el) el.hidden = !el.hidden;
    });
    // Auto-submit selects: <select data-autosubmit>
    document.addEventListener('change', function (e) {
      if (e.target.matches && e.target.matches('[data-autosubmit]')) e.target.form.submit();
    });
    // Live search: <input data-autosearch> submits shortly after typing stops
    var searchTimer;
    document.addEventListener('input', function (e) {
      if (e.target.matches && e.target.matches('[data-autosearch]')) {
        clearTimeout(searchTimer);
        var form = e.target.form;
        searchTimer = setTimeout(function () { form.submit(); }, 500);
      }
    });
    // Keep the caret at the end of a live-search box after the page reloads
    var live = document.querySelector('[data-autosearch]');
    if (live && live.value && new URLSearchParams(location.search).has('q')) {
      live.focus(); live.setSelectionRange(live.value.length, live.value.length);
    }
  })();
  </script>
</body>
</html>
