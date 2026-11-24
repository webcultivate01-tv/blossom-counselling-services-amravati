/* =========================================================
   Blossom Counseling — single source of truth for services.
   Edit ONLY this file to change services; the navbar dropdown
   (with hover cards), mobile menu, footer, Services page,
   home-page cards, forms and gallery filters all read from it.
   ========================================================= */
(function () {
  var PG = /\/pages\//i.test(location.pathname) ? '' : 'pages/';
  var IMG = function (f) { return encodeURI(f).replace(/#/g, '%23'); };
  var CARD = IMG;

  var SERVICES = [
    {
      id: 'individual', name: 'Individual Counselling', short: 'Individual Counselling',
      tag: 'For Personal Growth', icon: 'fa-user',
      img: "public/services/Individual Counselling.png",
      desc: 'A safe, confidential and non-judgmental space to explore emotional and psychological challenges. Support for anxiety, overthinking, depression, OCD-related concerns, childhood trauma, unresolved emotional experiences, stress, low self-esteem and personal difficulties.',
      items: [
        'Anxiety and anxiety-related concerns', 'Excessive worrying and overthinking',
        'Intrusive thoughts and obsessive-compulsive concerns (OCD)', 'Depression and low mood',
        'Stress and burnout', 'Emotional regulation and mood-related difficulties',
        'Childhood trauma and unresolved emotional experiences', 'Low self-esteem and lack of confidence',
        'Self-doubt and negative thinking patterns', 'Anger management',
        'Fear, insecurity and emotional distress', 'Grief, loss and bereavement',
        'Life transitions and adjustment difficulties', 'Personal growth and self-development'
      ]
    },
    {
      id: 'child', name: 'Child and Adolescent Counselling', short: 'Child & Adolescent Counselling',
      tag: 'For Young Minds', icon: 'fa-child-reaching',
      img: "public/services/Child & Adolescent Counselling.png",
      desc: 'Helping children and adolescents navigate emotional, behavioural, social and academic challenges. Support for anxiety, confidence, peer relationships, bullying, emotional regulation, adjustment difficulties and developmental concerns.',
      items: [
        'Emotional and behavioural difficulties', 'Academic stress and performance anxiety',
        'Overthinking and excessive worrying', 'Social and peer relationship difficulties',
        'Bullying and adjustment concerns', 'Confidence and self-esteem issues',
        'Emotional regulation', 'Family and school-related challenges',
        'Support for children with attention and learning-related difficulties'
      ]
    },
    {
      id: 'relationship', name: 'Relationship and Marital Counselling', short: 'Relationship & Marital Counselling',
      tag: 'For Couples', icon: 'fa-heart',
      img: "public/services/Relationship & Marital Counselling.png",
      desc: 'Supporting individuals and couples in understanding relationship challenges, improving communication and navigating conflicts. Focus on emotional connection, trust, healthy boundaries and building healthier relationship patterns.',
      items: [
        'Marital and relationship conflicts', 'Communication difficulties',
        'Trust and emotional intimacy concerns', 'Understanding relationship patterns',
        'Separation and relationship-related distress', 'Conflict resolution and healthy boundaries'
      ]
    },
    {
      id: 'parenting', name: 'Parenting Counselling and Guidance', short: 'Parenting Counselling & Guidance',
      tag: 'For Parents', icon: 'fa-people-roof',
      img: "public/services/Parenting Counselling & Guidance.png",
      desc: 'Helping parents better understand their children\'s emotional and behavioural needs. Guidance on positive parenting, managing behavioural challenges, adolescent concerns, effective communication and strengthening parent-child relationships.',
      items: [
        'Understanding children\'s emotional and behavioural needs', 'Positive and effective parenting strategies',
        'Parent-child relationship concerns', 'Managing behavioural challenges',
        'Adolescent parenting challenges', 'Screen time and digital habits',
        'Building emotional connection and communication', 'Supporting children\'s confidence and emotional development'
      ]
    },
    {
      id: 'wellbeing', name: 'Counselling for Women and Men', short: 'Counselling for Women and Men',
      tag: 'For Women &amp; Men', icon: 'fa-spa',
      img: "public/services/Women's & Men's Well-being.png",
      desc: 'A supportive space to address emotional, personal, family and professional challenges. Focus on stress management, self-worth, emotional well-being, relationship concerns, identity and navigating different stages of life.',
      groups: [
        { title: 'Women\'s Mental Health and Well-being', items: [
          'Emotional well-being and self-care', 'Stress and emotional overload',
          'Self-worth and identity concerns', 'Relationship and family-related challenges',
          'Life transitions and adjustment', 'Balancing personal and professional responsibilities'
        ]},
        { title: 'Men\'s Mental Health and Well-being', items: [
          'Stress and work-related pressures', 'Emotional expression and regulation',
          'Relationship and family concerns', 'Self-esteem and personal challenges',
          'Managing expectations and life transitions'
        ]}
      ]
    },
    {
      id: 'older', name: 'Emotional Well-being for Seniors', short: 'Emotional Well-being for Seniors',
      tag: 'For Golden Years', icon: 'fa-hand-holding-heart',
      img: "public/services/Older Adult Counselling.png",
      desc: 'Emotional support for older adults navigating loneliness, grief, retirement, changing family roles and life transitions. Encouraging emotional well-being, meaningful connections and healthy adjustment.',
      items: [
        'Emotional well-being during ageing', 'Loneliness and social isolation',
        'Adjustment to retirement and changing roles', 'Grief and bereavement',
        'Family and intergenerational relationship concerns', 'Coping with life transitions'
      ]
    },
    {
      id: 'career', name: 'Career Guidance and Psychological Assessment', short: 'Career Guidance & Assessment',
      tag: 'For Students &amp; Professionals', icon: 'fa-compass',
      img: "public/services/Career Guidance & Assessment.png",
      desc: 'Helping students and individuals gain a better understanding of their personality, interests, strengths and abilities. Support with career exploration, psychological assessments and informed academic and career decisions.',
      items: [
        'Career guidance and exploration', 'Personality-based career guidance',
        'Career interest and aptitude assessments', 'Understanding individual strengths and interests',
        'Academic and career-related decision-making'
      ]
    },
    {
      homeHide: true, // shown only on the Services page (home shows 2 rows + "View More Services")
      id: 'career-counselling', name: 'Career Counselling and Guidance', short: 'Career Counselling & Guidance',
      tag: 'For Students &amp; Adults', icon: 'fa-bullseye',
      img: "public/services/Career Guidance & Assessment.png",
      desc: 'Comprehensive career counselling through structured assessments that explore an individual\'s personality, interests, career values, skills, strengths and abilities. By understanding their unique profile, we help students and adults identify suitable career paths, explore opportunities and make informed academic and professional decisions. Our goal is to help individuals choose careers that align with their potential, interests and personal aspirations.',
      items: [
        'Structured assessments of personality and interests', 'Career values, skills and strengths mapping',
        'Identifying suitable career paths', 'Exploring academic and professional opportunities',
        'Career guidance for students', 'Career guidance and transitions for adults',
        'Informed academic and professional decision-making', 'Aligning careers with potential and aspirations'
      ]
    },
    {
      id: 'workshops', name: 'Workshops, Seminars & Motivational Talks', short: 'Workshops, Seminars & Motivational Talks',
      tag: 'For Groups &amp; Organisations', icon: 'fa-chalkboard-user',
      img: "public/services/Workshops, Seminars & Speaking.png",
      desc: 'Conducting interactive workshops, seminars and motivational sessions for schools, colleges, teachers, parents, students, women, community groups and organisations. Topics include mental health awareness, emotional intelligence, stress management, confidence building, resilience, relationships and personal growth.',
      groups: [
        { title: 'For Schools and Students', items: [
          'Emotional intelligence and emotional awareness', 'Managing anxiety and overthinking',
          'Building confidence and self-esteem', 'Stress and examination anxiety management',
          'Healthy friendships and relationships', 'Bullying awareness and prevention',
          'Focus, motivation and positive habits'
        ]},
        { title: 'For Teachers and Educators', items: [
          'Understanding children\'s emotional and psychological needs', 'Positive discipline and classroom management',
          'Effective communication and professional relationships', 'Managing stress and preventing burnout',
          'Understanding today\'s generation of learners', 'Building supportive classroom environments'
        ]},
        { title: 'For Parents', items: [
          'Understanding children\'s behaviour', 'Positive parenting',
          'Adolescent development and challenges', 'Managing screen time and digital exposure',
          'Building healthy parent-child relationships', 'Supporting children\'s emotional well-being'
        ]},
        { title: 'For Women and Community Groups', items: [
          'Women\'s emotional well-being', 'Self-confidence and self-worth',
          'Managing stress and emotional challenges', 'Healthy relationships and boundaries',
          'Personal growth and empowerment'
        ]},
        { title: 'For Organisations and Workplaces', items: [
          'Workplace mental health awareness', 'Stress and burnout management',
          'Emotional intelligence', 'Interpersonal communication',
          'Work-life balance', 'Teamwork and professional relationships'
        ]},
        { title: 'Motivational Speaking and Mental Health Awareness', items: [
          'Motivational talks for schools, colleges and organisations', 'Personal growth and self-development sessions',
          'Building confidence, resilience and a positive mindset', 'Mental health awareness programmes',
          'Emotional well-being and life skills talks', 'Sessions on overcoming challenges and developing a growth mindset'
        ]}
      ]
    }
  ];

  var MODES = {
    title: 'Online and Offline Counselling',
    items: [
      'Individual counselling sessions', 'Relationship counselling', 'Parenting consultations',
      'Adolescent counselling', 'Online counselling for individuals across locations',
      'In-person counselling sessions in Amravati'
    ]
  };

  /* ---------- helpers ---------- */
  var esc = function (s) { return String(s).replace(/&(?!amp;)/g, '&amp;').replace(/</g, '&lt;'); };
  var plain = function (s) { return String(s).replace(/&amp;/g, '&'); };
  // flat list of bullet points for a service (groups flattened with headings)
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  function listHTML(items, cls) {
    return '<ul class="' + cls + '">' + items.map(function (i) { return '<li>' + esc(i) + '</li>'; }).join('') + '</ul>';
  }
  function bodyHTML(s, cls) {
    if (s.groups) {
      return s.groups.map(function (g) {
        return '<h6 class="' + cls + '-grp">' + esc(g.title) + '</h6>' + listHTML(g.items, cls);
      }).join('');
    }
    return listHTML(s.items, cls);
  }
  function link(s, onPage) { return onPage ? '#' + s.id : PG + 'services.html#' + s.id; }

  /* ---------- renderers ---------- */
  // 1. Desktop navbar dropdown: hover a service → card with its sub-services
  function renderNav() {
    var menu = $('.dropdown-menu');
    if (!menu) return;
    var onServices = /services\.html$/i.test(location.pathname);
    menu.innerHTML = SERVICES.map(function (s) {
      return '<a class="dropdown-item" href="' + link(s, false) + '">' + esc(s.short) + '</a>';
    }).join('') + '<a class="dropdown-item" href="' + PG + 'services.html#modes">Online &amp; Offline Sessions</a>';
    // on the services page, clicking a dropdown item switches section in place
    if (onServices) {
      $$('.dropdown-item', menu).forEach(function (a) {
        a.addEventListener('click', function (e) {
          var id = a.getAttribute('href').split('#')[1];
          if (window.showService && document.getElementById(id)) { e.preventDefault(); history.replaceState(null, '', '#' + id); window.showService(id); }
        });
      });
    }
  }

  // 2. Mobile menu list
  function renderMobile() {
    var sub = document.getElementById('mobileServicesSub');
    if (!sub) return;
    var onServices = /services\.html$/i.test(location.pathname);
    sub.innerHTML = SERVICES.map(function (s) {
      var h = onServices ? '#' + s.id : PG + 'services.html#' + s.id;
      var oc = onServices ? ' onclick="event.preventDefault();history.replaceState(null,\'\',\'#' + s.id + '\');showService(\'' + s.id + '\')"' : '';
      return '<a class="mobile-nav-link" href="' + h + '"' + oc + '>' + esc(s.short) + '</a>';
    }).join('') + '<a class="mobile-nav-link" href="' + PG + 'services.html#modes"' +
      (onServices ? ' onclick="event.preventDefault();history.replaceState(null,\'\',\'#modes\');showService(\'modes\')"' : '') + '>Online &amp; Offline Sessions</a>';
  }

  // 3. Footer
  function renderFooter() {
    $$('footer .footer-col').forEach(function (col) {
      var h = $('h4', col);
      if (h && /services/i.test(h.textContent)) {
        col.classList.add('footer-services');
        var ul = $('ul', col);
        ul.innerHTML = SERVICES.map(function (s) {
          return '<li><a href="' + link(s, false) + '">' + esc(s.short) + '</a></li>';
        }).join('') + '<li><a href="' + PG + 'services.html#modes">Online &amp; Offline Sessions</a></li>';
      }
    });
  }

  // 4. Home-page cards
  function renderHome() {
    var grid = $('.services-grid-home');
    if (!grid) return;
    grid.innerHTML = SERVICES.filter(function (s) { return !s.homeHide; }).map(function (s) {
      return '<a class="service-card-home reveal" href="' + link(s, false) + '">' +
        '<div class="service-card-img-wrap"><img class="service-card-img" src="' + CARD(s.img) + '" alt="' + esc(s.name) + '"></div>' +
        '<div class="service-card-body"><h4>' + esc(s.short) + '</h4><p>' + esc(shortDesc(s.desc)) + '</p>' +
        '<span class="service-read-btn">Read More <i class="fa-solid fa-arrow-right"></i></span></div></a>';
    }).join('');
    if (!$('.services-more-wrap')) {
      var wrap = document.createElement('div');
      wrap.className = 'services-more-wrap';
      wrap.style.cssText = 'text-align:center;margin-top:36px';
      wrap.innerHTML = '<a class="btn-primary" href="' + PG + 'services.html">View More Services <i class="fa-solid fa-arrow-right"></i></a>';
      grid.parentNode.insertBefore(wrap, grid.nextSibling);
    }
  }
  function shortDesc(d) { var first = d.split('. ')[0]; return first.replace(/\.$/, '') + '.'; }

  // 5. Services page: sidebar + sections
  function renderServicesPage() {
    var nav = $('.sidebar-nav'), panel = $('.content-panel');
    if (!nav || !panel) return;
    var arrow = '<svg class="arrow" viewBox="0 0 16 16" fill="none"><path d="M6 4l4 4-4 4" stroke="#7c74d6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    var sbItem = function (id, icon, label) {
      return '<li><a href="#' + id + '" title="' + plain(label) + '" onclick="showService(\'' + id + '\');return false;">' +
        '<span class="sb-name">' + label + '</span>' + arrow + '</a></li>';
    };
    nav.innerHTML = SERVICES.map(function (s) { return sbItem(s.id, s.icon, esc(s.short)); }).join('') +
      sbItem('modes', 'fa-globe', 'Online &amp; Offline Sessions');

    var book = '<a class="svc-book-btn" href="#book-choice"><i class="fa-regular fa-calendar-check"></i> Book Appointment</a>';
    var secs = SERVICES.map(function (s) {
      var count = s.groups ? s.groups.reduce(function (n, g) { return n + g.items.length; }, 0) : s.items.length;
      return '<section class="service-section" id="' + s.id + '"><article class="svc-card">' +
        '<div class="svc-card-body">' +
          '<span class="svc-card-tag static"><i class="fa-solid ' + s.icon + '"></i> ' + s.tag + '</span>' +
          '<h2>' + esc(s.name) + '</h2><div class="service-divider"></div>' +
          '<div class="svc-intro">' +
            '<p class="svc-card-desc">' + esc(s.desc) + '</p>' +
            '<div class="svc-card-media"><img src="' + IMG(s.img) + '" alt="' + esc(s.name) + '"/></div>' +
          '</div>' +
          '<div class="svc-covers-head"><h3>What We Help With</h3><span>' + count + ' areas</span></div>' +
          (s.groups
            ? '<div class="svc-groups">' + s.groups.map(function (g) {
                return '<div class="svc-group"><h4>' + esc(g.title) + '</h4>' + listHTML(g.items, 'svc-tick') + '</div>';
              }).join('') + '</div>'
            : listHTML(s.items, 'svc-tick svc-tick-grid')) +
          '<div class="svc-card-foot">' + book + '<span><i class="fa-solid fa-lock"></i> Confidential &middot; Online &amp; in-person</span></div>' +
        '</div></article></section><hr class="divider-line">';
    }).join('');

    secs += '<section class="service-section" id="modes"><article class="svc-card"><div class="svc-card-body">' +
      '<span class="svc-card-tag static"><i class="fa-solid fa-globe"></i> Flexible Access</span>' +
      '<h2>' + esc(MODES.title) + '</h2><div class="service-divider"></div>' +
      '<p class="svc-card-desc">Choose the way that suits you — sit with us in person in Amravati, or connect from anywhere through secure online sessions.</p>' +
      listHTML(MODES.items, 'svc-tick svc-tick-grid') +
      '<div class="svc-card-foot">' + book + '</div></div></article></section>';

    panel.innerHTML = secs;
  }

  // 6. Forms: <select> of services
  function renderSelects() {
    $$('select.hcf-select, select.form-sel').forEach(function (sel) {
      var first = sel.options[0] && /service/i.test(sel.options[0].text + sel.name + (sel.previousElementSibling ? sel.previousElementSibling.textContent : ''));
      if (!first) return;
      var head = sel.options[0].outerHTML;
      sel.innerHTML = head + SERVICES.map(function (s) { return '<option>' + esc(plain(s.name)) + '</option>'; }).join('');
    });
  }

  // 7. Gallery filters
  function renderGalleryFilters() {
    var bar = $('.gallery-filter');
    if (!bar) return;
    var used = {};
    $$('.gallery-item').forEach(function (g) { used[g.getAttribute('data-cat')] = 1; });
    var btns = bar.querySelectorAll('.filter-btn');
    var all = btns[0].outerHTML;
    // Categories managed in the admin panel (set by gallery.html); falls back to the services list.
    var cats = window.BLOSSOM_GALLERY_CATS || SERVICES.map(function (s) { return { key: s.id, name: s.short }; });
    bar.innerHTML = all + cats.filter(function (c) { return used[c.key]; }).map(function (c) {
      return '<button class="filter-btn" onclick="filterGallery(\'' + String(c.key).replace(/[^\w-]/g, '') + '\',this)">' + esc(c.name) + '</button>';
    }).join('');
  }

  // 8. FAQ sentence
  function renderFaq() {
    $$('.faq-a').forEach(function (a) {
      if (/types of counselling|wide range of counselling services/i.test(a.textContent)) {
        var names = SERVICES.map(function (s) { return esc(s.name); });
        a.innerHTML = 'We offer a wide range of services including ' + names.slice(0, -1).join(', ') + ' and ' + names[names.length - 1] + '. Sessions are available both online and in person in Amravati.';
      }
    });
  }

  function init() {
    renderNav(); renderMobile(); renderFooter(); renderHome();
    renderServicesPage(); renderSelects(); renderGalleryFilters(); renderFaq();
    window.renderGalleryFilters = renderGalleryFilters;
    document.dispatchEvent(new Event('services:rendered'));
  }

  window.BLOSSOM_SERVICES = SERVICES;
  init(); // script is loaded at the end of <body>, so the DOM above is ready
})();
