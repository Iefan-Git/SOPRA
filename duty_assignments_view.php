<?php
/**
 * views/duty_assignments_view.php
 * SOPRA — HTML for the duty & operation location page: topbar, the
 * "Record New Duty Assignment" form (with cascading state/district
 * picker), the filter panel, and the duty records table.
 *
 * Rendered by duty_assignments.php via include, so it shares that
 * controller's variable scope: $success, $error, $old, $stateFl,
 * $districtFl, $personnelFl, $rankFl, $statusFl, $duties, $allPersonnel.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Duty &amp; Operation Location &mdash; <?= APP_NAME ?></title>
<link rel="icon" href="assets/logo.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0b2a4a">
<!-- iOS "Add to Home Screen" support (Safari doesn't fully read manifest.json) -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="SOPRA">
<link rel="apple-touch-icon" href="assets/icon-192.png">
<link rel="apple-touch-icon" sizes="512x512" href="assets/icon-512.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css?v=3">
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="title">
      <div class="title-row">
        <img src="assets/logo.png" alt="Logo" class="topbar-logo">
        <div>
          <h1>Duty &amp; Operation Location</h1>
          <small>Track where, when &amp; why each member is deployed</small>
        </div>
      </div>
    </div>
    <div class="who">
      <span class="role-badge admin">Admin</span>
      <span><?= e($_SESSION['username']) ?></span>
      <a href="payment_ledger.php" class="btn btn-ghost">Payment Ledger</a>
      <a href="logout.php" class="btn btn-ghost">Log Out</a>
    </div>
  </div>

  <div class="panel">
    <h3 style="margin-top:0;font-family:'Oswald',sans-serif;color:var(--navy);letter-spacing:1px;text-transform:uppercase;font-size:15px;">New Duty Assignment</h3>

    <?php if ($success): ?><div class="toast"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="login-error" style="margin-bottom:14px;"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="duty_assignments.php" id="dutyForm">
      <input type="hidden" name="action" value="add_duty">
      <div class="panel-row" style="align-items:flex-start;">
        <div class="field" style="flex:1;min-width:220px;">
          <label>Member</label>
          <select name="personnel_id" class="combo-select" data-placeholder="&mdash; Select Member &mdash;" required>
            <option value="">&mdash; Select Member &mdash;</option>
            <?php foreach ($allPersonnel as $pp): ?>
              <option value="<?= $pp['id'] ?>" <?= (string) $old['personnel_id'] === (string) $pp['id'] ? 'selected' : '' ?>><?= e($pp['name']) ?> (<?= e($pp['rank_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field" style="flex:1;min-width:220px;">
          <label>Duty Type</label>
          <select name="duty_type" id="dutyTypeSelect" class="combo-select" data-placeholder="&mdash; Select Duty Type &mdash;" required>
            <option value="">&mdash; Select Duty Type &mdash;</option>
            <?php foreach (DUTY_TYPES as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= $old['duty_type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="panel-row" style="align-items:flex-start;">
        <div class="field" style="flex:1;min-width:200px;">
          <label>State</label>
          <select name="state" id="stateSelect" class="combo-select" data-placeholder="&mdash; Select State &mdash;" required>
            <option value="">&mdash; Select State &mdash;</option>
            <?php foreach (array_keys(STATES_DISTRICTS) as $st): ?>
              <option value="<?= e($st) ?>" <?= $old['state'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field" style="flex:1;min-width:200px;">
          <label>District</label>
          <select name="district" id="districtSelect" class="combo-select" data-placeholder="&mdash; Select State First &mdash;" required <?= $old['state'] === '' ? 'disabled' : '' ?>>
            <option value="">&mdash; Select State First &mdash;</option>
            <?php if ($old['state'] !== '' && isset(STATES_DISTRICTS[$old['state']])): ?>
              <?php foreach (STATES_DISTRICTS[$old['state']] as $ds): ?>
                <option value="<?= e($ds) ?>" <?= $old['district'] === $ds ? 'selected' : '' ?>><?= e($ds) ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        
      </div>

      <div class="panel-row" style="align-items:flex-start;">
        <div class="field" style="width:180px;">
          <label>Departure Date</label>
          <input type="date" name="date_start" required value="<?= e($old['date_start']) ?>" />
        </div>
        <div class="field" style="width:180px;">
          <label>Return Date</label>
          <input type="date" name="date_end" id="dateEndInput" value="<?= e($old['date_end']) ?>" <?= $old['still_ongoing'] ? 'disabled' : '' ?> />
        </div>
        <div class="field" style="align-self:center;padding-top:20px;">
          <label style="display:flex;align-items:center;gap:6px;font-weight:500;cursor:pointer;">
            <input type="checkbox" name="still_ongoing" id="stillOngoingCheck" style="width:auto;" <?= $old['still_ongoing'] ? 'checked' : '' ?> />
            Still ongoing / return date not yet known
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:auto;padding:12px 28px;">Add New Duty Assignment</button>
    </form>
  </div>

  <div class="panel">
    <form method="get" action="duty_assignments.php" id="dutyFilterForm">
      <div class="panel-row">
        <div class="control">
          <label>State</label>
          <select name="state" id="stateFlSelect" class="search-input combo-select" data-placeholder="All States" onchange="document.getElementById('dutyFilterForm').submit()">
            <option value="">All States</option>
            <?php foreach (array_keys(STATES_DISTRICTS) as $st): ?>
              <option value="<?= e($st) ?>" <?= $stateFl === $st ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="control">
          <label>District</label>
          <select name="district" id="districtFlSelect" class="search-input combo-select" data-placeholder="All Districts" onchange="document.getElementById('dutyFilterForm').submit()">
            <option value="">All Districts</option>
            <?php if ($stateFl !== '' && isset(STATES_DISTRICTS[$stateFl])): ?>
              <?php foreach (STATES_DISTRICTS[$stateFl] as $ds): ?>
                <option value="<?= e($ds) ?>" <?= $districtFl === $ds ? 'selected' : '' ?>><?= e($ds) ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="control">
          <label>Member</label>
          <select name="personnel_id" class="search-input combo-select" data-placeholder="All Members" onchange="document.getElementById('dutyFilterForm').submit()">
            <option value="">All Members</option>
            <?php foreach ($allPersonnel as $pp): ?>
              <option value="<?= $pp['id'] ?>" <?= (string) $personnelFl === (string) $pp['id'] ? 'selected' : '' ?>><?= e($pp['name']) ?> (<?= e($pp['rank_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="control">
          <label>Rank</label>
          <select name="rank" class="search-input" onchange="document.getElementById('dutyFilterForm').submit()">
            <option value="">All Ranks</option>
            <?php foreach (RANKS as $r): ?>
              <option value="<?= e($r) ?>" <?= $rankFl === $r ? 'selected' : '' ?>><?= e($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="control">
          <label>Status</label>
          <select name="status" class="search-input" onchange="document.getElementById('dutyFilterForm').submit()">
            <option value="">All</option>
            <option value="upcoming" <?= $statusFl === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
            <option value="ongoing" <?= $statusFl === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
            <option value="completed" <?= $statusFl === 'completed' ? 'selected' : '' ?>>Completed</option>
          </select>
        </div>
        <?php if ($stateFl !== '' || $districtFl !== '' || $personnelFl || $rankFl !== '' || $statusFl !== ''): ?>
          <a class="btn btn-secondary" href="duty_assignments.php" style="align-self:flex-end;padding:10px 16px;">Reset Filters</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="duty_assignments.php?export=1&<?= buildDutyQuery() ?>" style="align-self:flex-end;padding:10px 16px;">Export CSV</a>
      </div>
    </form>
  </div>

  <div class="table-wrap">
    <table>
    <thead>
      <tr>
        <th class="left">Member</th>
        <th class="left">State / District</th>
        <th class="left">Type</th>
        <th>Departure</th>
        <th>Return</th>
        <th>Duration</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
      <tbody>
        <?php if (empty($duties)): ?>
          <tr class="empty-row"><td colspan="8">No duty assignments recorded yet.</td></tr>
        <?php else: foreach ($duties as $d):
          $status = dutyStatus($d['date_start'], $d['date_end']);
          $tagClass = $status === 'Completed' ? '' : 'user';
        ?>
          <tr>
            <td class="info"><?= e($d['name']) ?> <span style="color:var(--slate-soft);font-size:12px;">(<?= e($d['rank_name']) ?>)</span></td>
            <td class="info"><?= e($d['district']) ?>, <?= e($d['state']) ?><?php if ($d['location']): ?><div style="color:var(--slate-soft);font-size:12px;margin-top:2px;"><?= e($d['location']) ?></div><?php endif; ?></td>
            <td class="info"><?= e(DUTY_TYPES[$d['duty_type']] ?? $d['duty_type']) ?></td>
            <td class="info" style="text-align:center;"><?= e(fmtDate($d['date_start'])) ?></td>
            <td class="info" style="text-align:center;"><?= $d['date_end'] ? e(fmtDate($d['date_end'])) : '—' ?></td>
            <td class="info" style="text-align:center;"><?= e(fmtDuration($d['date_start'], $d['date_end'])) ?></td>
            <td class="info" style="text-align:center;">
              <span class="rtag <?= $tagClass ?>" style="<?= $status === 'Completed' ? 'background:var(--paper-alt);color:var(--slate-soft);' : '' ?>"><?= e($status) ?></span>
            </td>
            <td class="info" style="text-align:center;">
              <form method="post" action="duty_assignments.php" onsubmit="return confirm('Delete the duty record for <?= e(addslashes($d['name'])) ?> in <?= e(addslashes($d['district'])) ?>?');" style="display:inline">
                <input type="hidden" name="action" value="delete_duty">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <button type="submit" class="icon-btn" title="Delete">&times;</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <footer class="note">SOPRA &mdash; duty assignment &amp; operation location log.</footer>
</div>
<script src="searchable_dropdown.js?v=1"></script>
<script>
  var STATES_DISTRICTS = <?= json_encode(STATES_DISTRICTS) ?>;

  function rebuildDistrictSelect(selectedDistrict) {
    var oldSelect = document.getElementById('districtSelect');
    var wrap = oldSelect.closest('.combo') || oldSelect.parentNode;
    var state = document.getElementById('stateSelect').value;
    var districts = STATES_DISTRICTS[state] || [];

    var fresh = document.createElement('select');
    fresh.name = 'district';
    fresh.id = 'districtSelect';
    fresh.className = 'combo-select';
    fresh.required = true;
    if (!state) {
      fresh.disabled = true;
      fresh.setAttribute('data-placeholder', '\u2014 Select State First \u2014');
      fresh.appendChild(new Option('\u2014 Select State First \u2014', ''));
    } else {
      fresh.setAttribute('data-placeholder', '\u2014 Select District \u2014');
      fresh.appendChild(new Option('\u2014 Select District \u2014', ''));
      districts.forEach(function (d) {
        var opt = new Option(d, d);
        if (d === selectedDistrict) opt.selected = true;
        fresh.appendChild(opt);
      });
    }

    if (wrap.classList && wrap.classList.contains('combo')) {
      wrap.parentNode.insertBefore(fresh, wrap);
      wrap.remove();
    } else {
      wrap.replaceChild(fresh, oldSelect);
    }
    window.comboEnhance(fresh);
  }

  document.getElementById('stateSelect').addEventListener('change', function () {
    rebuildDistrictSelect('');
  });

  // On page (re)load after a validation error, keep the previously
  // chosen district selected once its state's list is in place.
  document.addEventListener('DOMContentLoaded', function () {
    rebuildDistrictSelect(<?= json_encode($old['district']) ?>);
  });

  var stillOngoing = document.getElementById('stillOngoingCheck');
  var dateEndInput = document.getElementById('dateEndInput');
  stillOngoing.addEventListener('change', function () {
    dateEndInput.disabled = stillOngoing.checked;
    if (stillOngoing.checked) dateEndInput.value = '';
  });
</script>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js').catch(function () {});
  }
</script>
</body>
</html>
