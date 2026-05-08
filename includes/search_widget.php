<?php
/**
 * ─────────────────────────────────────────────────────────────────
 *  SEARCH WIDGET  —  includes/search_widget.php
 *
 *  HOW TO ADD TO ANY PAGE:
 *  ─────────────────────────────────────────────────────────────────
 *
 *  1. Make sure config.php is already included before this widget.
 *     (Most pages already do `include '../includes/config.php';`)
 *
 *  2. Decide where the path to root is from your page:
 *
 *     • Pages in root folder  (e.g. index.php, blogs.php):
 *           $widget_root = '';
 *           include 'includes/search_widget.php';
 *
 *     • Pages one folder deep (e.g. patient/, doctor/, admin/):
 *           $widget_root = '../';
 *           include '../includes/search_widget.php';
 *
 *  3. Paste the include wherever you want the widget to appear
 *     in your HTML — e.g. inside a card, sidebar, or modal.
 *
 *  OPTIONAL — customise before including:
 *
 *     $widget_title    = 'Find a Doctor';   // card heading  (default: 'Channel Your Doctor')
 *     $widget_compact  = true;              // smaller padding for sidebars (default: false)
 *     $widget_redirect = 'login';           // 'login' = redirect to login first (default)
 *                                           // 'book'  = go straight to book page (logged-in pages)
 *
 *  FULL EXAMPLE inside a patient page:
 *
 *     <?php
 *     $widget_root     = '../';
 *     $widget_title    = 'Quick Doctor Search';
 *     $widget_compact  = true;
 *     $widget_redirect = 'book';
 *     include '../includes/search_widget.php';
 *     ?>
 *
 * ─────────────────────────────────────────────────────────────────
 */

// ── Defaults ───────────────────────────────────────────────────────
if (!isset($widget_root))     $widget_root     = '';          // path to root from current page
if (!isset($widget_title))    $widget_title    = 'Channel Your Doctor';
if (!isset($widget_compact))  $widget_compact  = false;
if (!isset($widget_redirect)) $widget_redirect = 'login';    // 'login' or 'book'

$w_pad   = $widget_compact ? '28px 24px' : '44px 36px';
$w_id    = 'sw_' . substr(md5(microtime()), 0, 6); // unique ID if widget used twice

// ── Load dropdown data ─────────────────────────────────────────────
$sw_hosps = $conn->query("SELECT DISTINCT name FROM hospitals ORDER BY name");
$sw_specs = $conn->query("SELECT DISTINCT specialization FROM doctors ORDER BY specialization");
$sw_docs  = $conn->query("SELECT full_name FROM doctors ORDER BY full_name");
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<div class="sw-wrap" id="<?php echo $w_id; ?>" style="
    background: white;
    border-radius: 24px;
    padding: <?php echo $w_pad; ?>;
    font-family: 'Poppins', sans-serif;
    box-shadow: 0 8px 32px rgba(0,0,0,0.10);
    width: 100%;
">
    <h2 style="
        font-size: <?php echo $widget_compact ? '20px' : '26px'; ?>;
        font-weight: 700;
        color: #8b1e3f;
        text-align: center;
        margin-bottom: <?php echo $widget_compact ? '20px' : '28px'; ?>;
        letter-spacing: -0.3px;
    "><?php echo htmlspecialchars($widget_title); ?></h2>

    <style>
    #<?php echo $w_id; ?> .sw-field { position:relative; margin-bottom:14px; }
    #<?php echo $w_id; ?> .sw-icon  {
        position:absolute; left:18px; top:50%; transform:translateY(-50%);
        font-size:20px; pointer-events:none; z-index:1;
    }
    #<?php echo $w_id; ?> .sw-field input,
    #<?php echo $w_id; ?> .sw-field select {
        width:100%; padding:15px 18px 15px 50px;
        border:2px solid #e8e8e8; border-radius:12px;
        font-size:14px; font-family:'Poppins',sans-serif;
        background:#fafafa; color:#333; transition:all 0.2s;
    }
    #<?php echo $w_id; ?> .sw-field input:focus,
    #<?php echo $w_id; ?> .sw-field select:focus {
        outline:none; border-color:#8b1e3f; background:#fff;
        box-shadow:0 3px 12px rgba(214,54,96,0.12);
    }
    #<?php echo $w_id; ?> .sw-field input::placeholder { color:#aaa; }
    #<?php echo $w_id; ?> .sw-field select {
        appearance:none;
        background-image:url('data:image/svg+xml;charset=UTF-8,<svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1L7 7L13 1" stroke="%23999" stroke-width="2" stroke-linecap="round"/></svg>');
        background-repeat:no-repeat; background-position:right 18px center; background-size:14px;
        padding-right:44px; cursor:pointer;
    }
    #<?php echo $w_id; ?> .sw-btn {
        width:100%; padding:15px; margin-top:18px;
        background:linear-gradient(135deg, #8b1e3f 0%, #a84461 100%);
        color:white; border:none; border-radius:50px;
        font-family:'Poppins',sans-serif; font-size:15px; font-weight:700;
        letter-spacing:1.2px; text-transform:uppercase; cursor:pointer;
        box-shadow:0 5px 18px rgba(214,54,96,0.35); transition:all 0.25s;
        display:flex; align-items:center; justify-content:center; gap:8px;
    }
    #<?php echo $w_id; ?> .sw-btn:hover {
        transform:translateY(-2px); box-shadow:0 8px 28px rgba(214,54,96,0.45);
    }
    #<?php echo $w_id; ?> .sw-results { margin-top:24px; padding-top:20px; border-top:2px solid #f0f0f0; display:none !important; }
    #<?php echo $w_id; ?> .sw-results.active { display:block !important; }
    #<?php echo $w_id; ?> .sw-results-title { font-size:16px; font-weight:700; color:#333; margin-bottom:14px; }
    #<?php echo $w_id; ?> .sw-list { 
        max-height:700px; 
        overflow-y:auto; 
        overflow-x:hidden;
        scroll-behavior: smooth;
    }
    #<?php echo $w_id; ?> .sw-list::-webkit-scrollbar { width:8px; }
    #<?php echo $w_id; ?> .sw-list::-webkit-scrollbar-track { background:#f0f0f0; border-radius:10px; }
    #<?php echo $w_id; ?> .sw-list::-webkit-scrollbar-thumb { background:#8b1e3f; border-radius:10px; }
    #<?php echo $w_id; ?> .sw-list::-webkit-scrollbar-thumb:hover { background:#6d1732; }
    #<?php echo $w_id; ?> .sw-doc-card {
        background:#fafafa; border-radius:14px; padding:16px; margin-bottom:12px;
        border:2px solid transparent; transition:all 0.25s;
    }
    #<?php echo $w_id; ?> .sw-doc-card:hover {
        border-color:#8b1e3f; transform:translateY(-2px);
        box-shadow:0 6px 20px rgba(0,0,0,0.08); background:#fff;
    }
    #<?php echo $w_id; ?> .sw-doc-name { font-size:15px; font-weight:700; color:#2c3e50; margin-bottom:8px; }
    #<?php echo $w_id; ?> .sw-doc-info { font-size:13px; color:#666; margin:4px 0; }
    #<?php echo $w_id; ?> .sw-badge {
        display:inline-block; padding:3px 12px; border-radius:16px;
        font-size:11px; font-weight:700; margin-top:6px;
    }
    #<?php echo $w_id; ?> .sw-badge-green  { background:#d4edda; color:#155724; }
    #<?php echo $w_id; ?> .sw-badge-yellow { background:#fff3cd; color:#856404; }
    #<?php echo $w_id; ?> .sw-book-btn {
        width:100%; padding:11px; background:#8b1e3f; color:white; border:none;
        border-radius:50px; font-family:'Poppins',sans-serif; font-size:13px;
        font-weight:700; cursor:pointer; margin-top:12px; transition:all 0.2s;
    }
    #<?php echo $w_id; ?> .sw-book-btn:hover { background:#6d1732; transform:translateY(-1px); }
    #<?php echo $w_id; ?> .sw-empty { text-align:center; color:#aaa; padding:30px; font-size:14px; }
    </style>

    <form id="<?php echo $w_id; ?>_form">
        <!-- Doctor name -->
        <div class="sw-field">
            <span class="sw-icon">👨‍⚕️</span>
            <input type="text"
                   id="<?php echo $w_id; ?>_doc"
                   placeholder="Doctor name"
                   maxlength="40"
                   list="<?php echo $w_id; ?>_dl"
                   autocomplete="off">
            <datalist id="<?php echo $w_id; ?>_dl">
                <?php while ($d = $sw_docs->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($d['full_name']); ?>">
                <?php endwhile; ?>
            </datalist>
        </div>

        <!-- Hospital -->
        <div class="sw-field">
            <span class="sw-icon">🏥</span>
            <select id="<?php echo $w_id; ?>_hosp">
                <option value="">Any Hospital</option>
                <?php while ($h = $sw_hosps->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($h['name']); ?>">
                        <?php echo htmlspecialchars($h['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Specialization -->
        <div class="sw-field">
            <span class="sw-icon">⚕️</span>
            <select id="<?php echo $w_id; ?>_spec">
                <option value="">Any Specialization</option>
                <?php while ($s = $sw_specs->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($s['specialization']); ?>">
                        <?php echo htmlspecialchars($s['specialization']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Date -->
        <div class="sw-field">
            <span class="sw-icon">📅</span>
            <input type="date"
                   id="<?php echo $w_id; ?>_date"
                   min="<?php echo date('Y-m-d'); ?>">
        </div>

        <button type="submit" class="sw-btn">
            <span>🔍</span><span>Search</span>
        </button>
    </form>

    <div class="sw-results" id="<?php echo $w_id; ?>_results">
        <div class="sw-results-title">Available Doctors</div>
        <div class="sw-list"   id="<?php echo $w_id; ?>_list"></div>
    </div>
</div>

<script>
(function() {
    const wid      = '<?php echo $w_id; ?>';
    const root     = '<?php echo $widget_root; ?>';
    const redirect = '<?php echo $widget_redirect; ?>';

    document.getElementById(wid + '_form').addEventListener('submit', function(e) {
        e.preventDefault();
        const params = new URLSearchParams();
        const doc  = document.getElementById(wid + '_doc').value;
        const hosp = document.getElementById(wid + '_hosp').value;
        const spec = document.getElementById(wid + '_spec').value;
        const date = document.getElementById(wid + '_date').value;
        if (doc)  params.append('doctor', doc);
        if (hosp) params.append('hospital', hosp);
        if (spec) params.append('specialization', spec);
        if (date) params.append('date', date);

        const listEl    = document.getElementById(wid + '_list');
        const resultsEl = document.getElementById(wid + '_results');
        listEl.innerHTML = '<div class="sw-empty">Searching...</div>';
        resultsEl.classList.add('active');

        fetch(root + 'search_api.php?' + params)
            .then(r => r.json())
            .then(data => showResults(data.doctors, listEl, resultsEl))
            .catch(() => {
                listEl.innerHTML = '<div class="sw-empty">⚠️ Error loading results. Please try again.</div>';
            });
    });

    function showResults(doctors, listEl, resultsEl) {
        if (!doctors || doctors.length === 0) {
            listEl.innerHTML = '<div class="sw-empty">No doctors found. Try different filters.</div>';
            return;
        }
        let html = '';
        doctors.forEach(function(d) {
            const badge = d.available_slots > 10
                ? '<span class="sw-badge sw-badge-green">Available</span>'
                : d.available_slots > 0
                    ? '<span class="sw-badge sw-badge-yellow">' + d.available_slots + ' slots left</span>'
                    : '';
            html += '<div class="sw-doc-card">'
                + '<div class="sw-doc-name">Dr. ' + esc(d.full_name) + '</div>'
                + '<div class="sw-doc-info">🩺 ' + esc(d.specialization) + '</div>'
                + '<div class="sw-doc-info">💰 Rs. ' + parseFloat(d.consultation_fee).toFixed(2) + '</div>'
                + '<div class="sw-doc-info">🏥 ' + esc(d.hospitals) + '</div>'
                + badge
                + '<button class="sw-book-btn" onclick="swBook(\'' + wid + '\',' + d.id + ',\'' + esc(d.full_name).replace(/'/g,"\\x27") + '\')">'
                + '📅 Book Appointment</button>'
                + '</div>';
        });
        listEl.innerHTML = html;
    }

    window.swBook = window.swBook || function(wid, id, name) {
        const root     = document.getElementById(wid).dataset.root     || '';
        const redirect = document.getElementById(wid).dataset.redirect || 'login';
        if (redirect === 'book') {
            window.location.href = root + 'patient/book_appointment.php?doctor_id=' + id;
        } else {
            window.location.href = root + 'login.php?book_doctor=' + id + '&doctor_name=' + encodeURIComponent(name);
        }
    };

    // Attach root/redirect as data attrs so swBook can read them
    const wrap = document.getElementById(wid);
    wrap.dataset.root     = root;
    wrap.dataset.redirect = redirect;
})();

function esc(text) {
    const d = document.createElement('div');
    d.textContent = String(text || '');
    return d.innerHTML;
}
</script>
