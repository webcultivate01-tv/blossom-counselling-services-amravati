    </div>
    <p class="auth-footer">&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?> &middot; Secure admin area</p>
  </main>
  <script>
    document.querySelectorAll('.toggle-pass').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.target);
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.classList.toggle('is-visible', show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      });
    });
  </script>
</body>
</html>
