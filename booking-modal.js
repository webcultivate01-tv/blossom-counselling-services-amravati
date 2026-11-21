/* Booking choice popup: In-Person -> contact page, Online -> Topmate (Google Meet) */
(function () {
  // TODO: replace with the real Topmate profile URL
  var TOPMATE_URL = 'https://topmate.io/YOUR_USERNAME';
  var CONTACT_URL = (/\/pages\//i.test(location.pathname) ? '' : 'pages/') + 'contact.html#booking-form';

  var css = '\
.bk-overlay{position:fixed;inset:0;background:rgba(31,29,54,.55);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:10000;opacity:0;visibility:hidden;transition:opacity .25s,visibility .25s}\
.bk-overlay.open{opacity:1;visibility:visible}\
.bk-modal{background:#fff;border-radius:20px;max-width:520px;width:100%;padding:32px 28px 28px;position:relative;box-shadow:0 20px 60px rgba(31,29,54,.3);transform:translateY(16px) scale(.98);transition:transform .25s;max-height:95vh;overflow-y:auto}\
.bk-overlay.open .bk-modal{transform:none}\
.bk-close{position:absolute;top:12px;right:14px;width:34px;height:34px;border-radius:50%;background:#f3f2fc;color:#1f1d36;font-size:20px;line-height:1;display:flex;align-items:center;justify-content:center}\
.bk-close:hover{background:#d6d6f3}\
.bk-title{font-size:22px;font-weight:700;color:#1f1d36;margin:0 0 6px;text-align:center}\
.bk-sub{font-size:15px;color:#5f5d78;text-align:center;margin:0 0 22px}\
.bk-options{display:grid;grid-template-columns:1fr 1fr;gap:14px}\
.bk-opt{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:22px 14px;border:2px solid #e3e2f2;border-radius:16px;background:#faf9fd;color:#1f1d36;text-decoration:none;cursor:pointer;transition:border-color .2s,transform .2s,box-shadow .2s;font-family:inherit}\
.bk-opt:hover,.bk-opt:focus-visible{border-color:#6f73d2;transform:translateY(-3px);box-shadow:0 8px 22px rgba(111,115,210,.2);outline:none}\
.bk-ico{width:54px;height:54px;border-radius:50%;background:#f3f2fc;color:#6f73d2;display:flex;align-items:center;justify-content:center;font-size:22px}\
.bk-opt b{font-size:17px}\
.bk-opt span{font-size:13.5px;color:#5f5d78;line-height:1.4}\
@media(max-width:480px){.bk-options{grid-template-columns:1fr}.bk-modal{padding:28px 18px 20px}}';

  var style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  var overlay = document.createElement('div');
  overlay.className = 'bk-overlay';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-modal', 'true');
  overlay.setAttribute('aria-labelledby', 'bk-title');
  overlay.innerHTML =
    '<div class="bk-modal">' +
      '<button class="bk-close" type="button" aria-label="Close">&times;</button>' +
      '<h3 class="bk-title" id="bk-title">How would you like to meet?</h3>' +
      '<p class="bk-sub">Choose the type of appointment that suits you.</p>' +
      '<div class="bk-options">' +
        '<a class="bk-opt" id="bk-inperson" href="' + CONTACT_URL + '">' +
          '<div class="bk-ico"><i class="fa-solid fa-location-dot"></i></div>' +
          '<b>In-Person</b><span>Visit our counselling centre in Amravati</span>' +
        '</a>' +
        '<a class="bk-opt" id="bk-online" href="' + TOPMATE_URL + '" target="_blank" rel="noopener">' +
          '<div class="bk-ico"><i class="fa-solid fa-video"></i></div>' +
          '<b>Online (Google Meet)</b><span>Book a virtual session from anywhere</span>' +
        '</a>' +
      '</div>' +
    '</div>';
  document.body.appendChild(overlay);

  var lastFocus = null;
  function open() {
    lastFocus = document.activeElement;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    overlay.querySelector('.bk-opt').focus();
  }
  function close() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay || e.target.closest('.bk-close')) return close();
    if (e.target.closest('#bk-online')) return close();
    if (e.target.closest('#bk-inperson')) {
      // Already on the contact page: just close and scroll to the form.
      if (/contact\.html$/i.test(location.pathname) || /\/contact\/?$/i.test(location.pathname)) {
        e.preventDefault();
        close();
        var f = document.getElementById('booking-form');
        if (f) f.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.classList.contains('open')) close();
  });

  // Intercept every "Book Appointment" / "Book an Appointment" link on the site.
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a');
    if (!a || overlay.contains(a)) return;
    if (/book\s+(an\s+)?appointment/i.test(a.textContent)) {
      e.preventDefault();
      open();
    }
  });
})();
