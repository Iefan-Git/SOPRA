<?php
/**
 * views/payment_ledger_view.php
 * SOPRA — HTML for the admin payment ledger page: topbar, filter
 * panel, stat cards, the 12-month ledger table, and the Add/Edit
 * Member + Manage Users modals.
 *
 * Rendered by payment_ledger.php via include, so it shares that
 * controller's variable scope: $year, $search, $selRanks,
 * $filterMonth, $filterStatus, $personnelList, $payMap,
 * $rate, $fullyPaid, $paidThisMonth, $totalCollected, $allUsers,
 * $membersByRank, $newUsername, $newPassword, $modal,
 * $editData, $upcomingDuty.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin &mdash; <?= APP_NAME ?></title>
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
          <h1><?= APP_NAME ?> &mdash; Payment Ledger</h1>
          <small>Monthly member payment status &bull; <?= e(APP_UNIT) ?></small>
        </div>
      </div>
    </div>
    <div class="who">
      <span class="role-badge admin">Admin</span>
      <span><?= e($_SESSION['username']) ?></span>
      <a href="payment_ledger.php" class="btn btn-ghost current">Payment Ledger</a>
      <a href="duty_assignments.php" class="btn btn-ghost">Duty Assignments</a>
      <a href="logout.php" class="btn btn-ghost" onclick="return confirm('Log out of SOPRA?');">Log Out</a>
    </div>
  </div>

  <?php if (!empty($upcomingDuty)): ?>
  <div class="viewer-banner duty-banner">
    <span>&#128205;</span>
    <span>
      <b>Upcoming duty assignments:</b>
      <?php foreach ($upcomingDuty as $i => $du): ?>
        <?= $i > 0 ? ' &middot; ' : '' ?><?= e($du['name']) ?> (<?= e($du['rank_name']) ?>) &rarr; <?= e($du['district']) ?>, <?= e($du['state']) ?><?= $du['location'] ? ' (' . e($du['location']) . ')' : '' ?>, <?= fmtDate($du['date_start']) ?>
      <?php endforeach; ?>
      <a href="duty_assignments.php" style="margin-left:8px;font-weight:700;color:#1c4a75;">View all &rarr;</a>
    </span>
  </div>
  <?php endif; ?>

  <div class="panel">
    <form method="get" action="payment_ledger.php" id="filterForm">
      <input type="hidden" name="ranks" id="ranksField" value="<?= e(implode(',', $selRanks)) ?>">
      <input type="hidden" name="year" value="<?= $year ?>">
      <div class="panel-row">
        <div class="control">
          <label>Year</label>
          <div class="year-nav">
            <a href="payment_ledger.php?<?= buildQuery(['year' => $year - 1]) ?>" style="background:var(--paper-alt);border:1px solid var(--line);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--navy);text-decoration:none;border-radius:4px;">&#8592;</a>
            <span class="yr"><?= $year ?></span>
            <a href="payment_ledger.php?<?= buildQuery(['year' => $year + 1]) ?>" style="background:var(--paper-alt);border:1px solid var(--line);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--navy);text-decoration:none;border-radius:4px;">&#8594;</a>
          </div>
        </div>
        <div class="control">
          <label>Search Name</label>
          <select name="search" id="searchNameSelect" class="combo-select" data-placeholder="Type a name..." onchange="document.getElementById('filterForm').submit()">
            <option value="">&mdash; All Names &mdash;</option>
            <?php foreach ($membersForRemoval as $m): ?>
              <option value="<?= e($m['name']) ?>" <?= $search === $m['name'] ? 'selected' : '' ?>><?= e($m['name']) ?> (<?= e($m['rank_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="control" style="flex:1;min-width:260px;">
          <label>Filter by Rank</label>
          <div class="chips" id="rankChips">
            <?php foreach (RANKS as $r):
                $active = in_array($r, $selRanks, true); ?>
              <div class="chip <?= $active ? '' : 'off' ?>" data-rank="<?= e($r) ?>"><?= e($r) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="panel-row" style="margin-top:16px;">
        <div class="control">
          <label>Month</label>
          <select name="fmonth" class="search-input" onchange="document.getElementById('filterForm').submit()">
            <option value="">All Months</option>
            <?php foreach (MONTHS as $idx => $m): ?>
              <option value="<?= $idx ?>" <?= $filterMonth === $idx ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="control">
          <label>Payment Status</label>
          <select name="fstatus" class="search-input" onchange="document.getElementById('filterForm').submit()">
            <option value="">All Statuses</option>
            <option value="paid" <?= $filterStatus === 'paid' ? 'selected' : '' ?>>Paid</option>
            <option value="unpaid" <?= $filterStatus === 'unpaid' ? 'selected' : '' ?>>Not Paid</option>
          </select>
        </div>
        <?php if ($filterMonth !== null || $filterStatus !== '' || $search !== '' || !empty($selRanks)): ?>
          <a class="btn btn-secondary" href="payment_ledger.php?year=<?= $year ?>" style="align-self:flex-end;padding:10px 16px;">Reset Filters</a>
        <?php endif; ?>
        <div style="margin-left:auto;display:flex;gap:10px;flex-wrap:wrap;">
          <a class="btn export-btn" href="payment_ledger.php?<?= buildQuery(['export_month' => 1]) ?>" title="Export payment status for a single month as a PDF">Export This Month (PDF)</a>
          <a class="btn export-btn" href="payment_ledger.php?<?= buildQuery(['export' => 1]) ?>" title="Export the full 12 months as a PDF">Export Full Year (PDF)</a>
          <a class="btn manage-btn" href="payment_ledger.php?<?= buildQuery(['modal' => 'manageUsers']) ?>">Manage Users</a>
        </div>
      </div>
    </form>
  </div>

  <div class="stats">
    <div class="stat-card"><div class="num"><?= count($personnelList) ?></div><div class="lbl">Members Shown</div></div>
    <div class="stat-card">
      <div class="num"><?= $rate ?>%</div>
      <div class="lbl">Collection Rate <?= $year ?></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $rate ?>%"></div></div>
    </div>
    <div class="stat-card"><div class="num"><?= $fullyPaid ?></div><div class="lbl">Fully Paid (12/12)</div></div>
    <div class="stat-card"><div class="num"><?= $paidThisMonth === null ? '—' : $paidThisMonth . '/' . count($personnelList) ?></div><div class="lbl">Paid This Month</div></div>
    <div class="stat-card"><div class="num">RM <?= number_format($totalCollected, 2) ?></div><div class="lbl">Total Collected <?= $year ?></div></div>
  </div>

  <div class="table-wrap">
    <table class="full-ledger">
      <thead>
        <tr>
          <th class="left">Rank</th>
          <th class="left">Name / Call Sign</th>
          <?php foreach (MONTHS as $m): ?><th><?= $m ?></th><?php endforeach; ?>
          <th>Progress</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($personnelList)): ?>
          <tr class="empty-row"><td colspan="15">No members match these filters.</td></tr>
        <?php else: foreach ($personnelList as $pp):
          $paidCount = 0;
          $ticks = '';
          for ($m = 0; $m < 12; $m++) {
              $paid = payInfo($payMap, $pp['id'], $m)['paid'];
              if ($paid) $paidCount++;
              $ticks .= '<div class="tick ' . ($paid ? 'on' : '') . '"></div>';
          }
        ?>
          <tr>
            <td class="info rank"><?= e($pp['rank_name']) ?></td>
            <td class="info name">
              <?= e($pp['name']) ?>
              <span class="row-actions">
                <a class="icon-btn" href="payment_ledger.php?<?= buildQuery(['modal' => 'edit', 'id' => $pp['id']]) ?>" title="Edit">&#9998;</a>
                <form method="post" action="payment_ledger.php" style="display:inline" onsubmit="return confirm('Delete <?= e(addslashes($pp['name'])) ?> (<?= e($pp['rank_name']) ?>) from the ledger? This action cannot be undone.');">
                  <input type="hidden" name="action" value="delete_personnel">
                  <input type="hidden" name="id" value="<?= $pp['id'] ?>">
                  <input type="hidden" name="year" value="<?= $year ?>">
                  <input type="hidden" name="search" value="<?= e($search) ?>">
                  <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
                  <input type="hidden" name="fmonth" value="<?= $filterMonth === null ? '' : $filterMonth ?>">
                  <input type="hidden" name="fstatus" value="<?= e($filterStatus) ?>">
                  <button type="submit" class="icon-btn" title="Remove">&times;</button>
                </form>
              </span>
            </td>
            <?php foreach (MONTHS as $idx => $mn):
              $info = payInfo($payMap, $pp['id'], $idx);
              $paid = $info['paid'];
              $tip = $paid
                ? ($mn . ': ' . fmtRM($info['amount']) . ' (paid ' . fmtDate($info['paid_date']) . ') — click to reopen')
                : ($mn . ': not paid — click to record payment'); ?>
              <td class="cell">
                <form method="post" action="payment_ledger.php" class="pay-form" data-paid="<?= $paid ? '1' : '0' ?>" data-month="<?= e($mn) ?>">
                  <input type="hidden" name="action" value="toggle_payment">
                  <input type="hidden" name="personnel_id" value="<?= $pp['id'] ?>">
                  <input type="hidden" name="month" value="<?= $idx ?>">
                  <input type="hidden" name="year" value="<?= $year ?>">
                  <input type="hidden" name="amount" class="amount-field" value="">
                  <input type="hidden" name="search" value="<?= e($search) ?>">
                  <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
                  <input type="hidden" name="fmonth" value="<?= $filterMonth === null ? '' : $filterMonth ?>">
                  <input type="hidden" name="fstatus" value="<?= e($filterStatus) ?>">
                  <button type="submit" class="fillbtn <?= $paid ? 'paid' : 'unpaid' ?>" title="<?= e($tip) ?>">
                    <?php if ($paid && $info['amount'] !== null): ?>
                      <span class="fillbtn-amt"><?= number_format($info['amount'], 0) ?></span>
                      <span class="fillbtn-date"><?= e(fmtDateShort($info['paid_date'])) ?></span>
                    <?php endif; ?>
                  </button>
                </form>
              </td>
            <?php endforeach; ?>
            <td class="progress-cell">
              <div class="ribbon"><?= $ticks ?></div>
              <div class="ratio"><?= $paidCount ?>/12 paid</div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="legend">
    <span><span class="swatch" style="background:var(--green)"></span>Paid</span>
    <span><span class="swatch" style="background:var(--red)"></span>Not Paid Yet</span>
    <span style="color:var(--slate-soft)">Click a month cell to record or reopen a payment. Each member may pay whatever RM amount they choose.</span>
  </div>

  <footer class="note">SOPRA &mdash; any changes made by an Admin will be visible to everyone using this system.</footer>
</div>

<?php if ($modal === 'add' || $modal === 'edit'):
  $isEdit = $modal === 'edit' && $editData;
  $dName = $isEdit ? $editData['name'] : '';
  $dRank = $isEdit ? $editData['rank_name'] : RANKS[0];
?>
<div class="overlay">
  <div class="modal">
    <h3><?= $isEdit ? 'Edit Member' : 'Add Member' ?></h3>
    <form method="post" action="payment_ledger.php">
      <input type="hidden" name="action" value="<?= $isEdit ? 'edit_personnel' : 'add_personnel' ?>">
      <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
      <input type="hidden" name="year" value="<?= $year ?>">
      <input type="hidden" name="search" value="<?= e($search) ?>">
      <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
      <input type="hidden" name="fmonth" value="<?= $filterMonth === null ? '' : $filterMonth ?>">
      <input type="hidden" name="fstatus" value="<?= e($filterStatus) ?>">
      <div class="field">
        <label>Name / Call Sign</label>
        <input type="text" name="name" value="<?= e($dName) ?>" placeholder="cth. TANGO" required>
      </div>
      <div class="field">
        <label>Rank</label>
        <select name="rank">
          <?php foreach (RANKS as $r): ?>
            <option value="<?= e($r) ?>" <?= $r === $dRank ? 'selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="payment_ledger.php?<?= buildQuery() ?>">Cancel</a>
        <button class="btn btn-primary" type="submit" style="margin-top:0;"><?= $isEdit ? 'Save' : 'Add' ?></button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($modal === 'manageUsers'): ?>
<div class="overlay" data-close-url="payment_ledger.php?<?= buildQuery() ?>">
  <div class="modal" id="manageUsersModal">
    <h3>Manage Users</h3>
    <?php if ($newUsername !== null): ?>
      <div class="toast" style="text-align:left;line-height:1.5;">
        Account created &mdash; share these with the member (shown once):<br>
        Username: <b><?= e($newUsername) ?></b>
        <?php if ($newPassword !== null): ?> &nbsp;&bull;&nbsp; Password: <b><?= e($newPassword) ?></b><?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Initial menu: show three action buttons and a close button -->
    <div id="manageMenu" style="display:block;">
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:12px;">
        <button class="btn btn-primary" id="btnShowAddAdmin" type="button">Add Admin</button>
        <button class="btn btn-primary" id="btnShowAddMember" type="button">Add Member</button>
        <button class="btn btn-primary" id="btnShowRemoveMember" type="button" style="background:var(--red);color:#fff;">Remove Member</button>
      </div>
      <div class="modal-actions">
        <a class="btn btn-secondary text" style="text-align:center;" href="payment_ledger.php?<?= buildQuery() ?>">Close</a>
      </div>
    </div>

    <!-- Add Admin form (initially hidden) -->
    <div id="formAddAdmin" class="manage-form" style="display:none;">
      <div class="divider"></div>
      <h4 style="margin:0 0 14px;color:var(--navy);font-size:13px;letter-spacing:1px;text-transform:uppercase;">Add Admin</h4>
      <form method="post" action="payment_ledger.php" id="addAdminForm">
        <input type="hidden" name="action" value="add_user">
        <input type="hidden" name="role" value="admin">
        <input type="hidden" name="year" value="<?= $year ?>">
        <input type="hidden" name="search" value="<?= e($search) ?>">
        <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
        <input type="hidden" name="fmonth" value="<?= $filterMonth === null ? '' : $filterMonth ?>">
        <input type="hidden" name="fstatus" value="<?= e($filterStatus) ?>">
        <div class="field"><label>New Username</label><input type="text" name="username" placeholder="cth. konst_amin" required></div>
        <div class="field"><label>Password</label><input type="text" name="password" placeholder="Password" required></div>
        <div class="modal-actions">
          <button class="btn btn-secondary" type="button" id="btnBackFromAdmin">Back</button>
          <button class="btn btn-primary" type="submit" style="margin-top:0;">Add Admin</button>
        </div>
      </form>
    </div>

    <!-- Add Member form (initially hidden) -->
    <div id="formAddMember" class="manage-form" style="display:none;">
      <div class="divider"></div>
      <h4 style="margin:0 0 14px;color:var(--navy);font-size:13px;letter-spacing:1px;text-transform:uppercase;">Add Member</h4>
      <form method="post" action="payment_ledger.php" id="addMemberForm">
        <input type="hidden" name="action" value="add_personnel">
        <input type="hidden" name="year" value="<?= $year ?>">
        <input type="hidden" name="search" value="<?= e($search) ?>">
        <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
        <input type="hidden" name="fmonth" value="<?= $filterMonth === null ? '' : $filterMonth ?>">
        <input type="hidden" name="fstatus" value="<?= e($filterStatus) ?>">
        <input type="hidden" name="modal" value="manageUsers">
        <div class="field">
          <label>Name / Call Sign</label>
          <input type="text" name="name" placeholder="cth. TANGO" required>
        </div>
        <div class="field">
          <label>Rank</label>
          <select name="rank">
            <?php foreach (RANKS as $r): ?>
              <option value="<?= e($r) ?>"><?= e($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-actions">
          <button class="btn btn-secondary" type="button" id="btnBackFromMember">Back</button>
          <button class="btn btn-primary" type="submit" style="margin-top:0;">Add Member</button>
        </div>
      </form>
    </div>

    <!-- Remove Member form (initially hidden) -->
    <div id="formRemoveMember" class="manage-form" style="display:none;">
      <div class="divider"></div>
      <h4 style="margin:0 0 14px;color:var(--navy);font-size:13px;letter-spacing:1px;text-transform:uppercase;">Remove Member</h4>
      <form method="post" action="payment_ledger.php" id="removeUserForm">
        <input type="hidden" name="action" value="delete_personnel">
        <input type="hidden" name="year" value="<?= $year ?>">
        <input type="hidden" name="search" value="<?= e($search) ?>">
        <input type="hidden" name="ranks" value="<?= e(implode(',', $selRanks)) ?>">
        <input type="hidden" name="fmonth" value="<?= $filterMonth === null ? '' : $filterMonth ?>">
        <input type="hidden" name="fstatus" value="<?= e($filterStatus) ?>">
        <input type="hidden" name="modal" value="manageUsers">
        <div class="field">
          <label>Name / Call Sign</label>
          <select name="id" id="removeNameSelect" class="combo-select" data-placeholder="&mdash; Select Name &mdash;" required disabled>
            <option value="">&mdash; Select Name &mdash;</option>
          </select>
        </div>
        <div class="field">
          <label>Rank</label>
          <select id="removeRankSelect" class="combo-select" data-placeholder="&mdash; All Ranks &mdash;">
            <option value="">&mdash; All Ranks &mdash;</option>
            <?php foreach (RANKS as $r): ?>
              <option value="<?= e($r) ?>"><?= e($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-actions">
          <button class="btn btn-secondary" type="button" id="btnBackFromRemove">Back</button>
          <button class="btn btn-primary" type="submit" style="margin-top:0;background:var(--red);color:#fff;">Remove Member</button>
        </div>
      </form>
    </div>

  </div>
</div>

<script>
  // Manage Users: overlay click closes modal (click outside modal)
  (function(){
    var overlay = document.querySelector('.overlay');
    if (!overlay) return;
    var modal = document.getElementById('manageUsersModal');
    overlay.addEventListener('click', function(ev){
      if (!modal.contains(ev.target)) {
        var url = overlay.getAttribute('data-close-url') || 'payment_ledger.php';
        window.location = url;
      }
    });
    // Prevent clicks inside modal from closing
    modal.addEventListener('click', function(ev){ ev.stopPropagation(); });

    // Toggle between menu and forms
    function showMenu(){ document.getElementById('manageMenu').style.display = 'block';
      document.querySelectorAll('.manage-form').forEach(function(d){ d.style.display = 'none'; });
    }
    function showForm(id){ document.getElementById('manageMenu').style.display = 'none';
      document.querySelectorAll('.manage-form').forEach(function(d){ d.style.display = 'none'; });
      var el = document.getElementById(id); if (el) el.style.display = 'block';
    }
    var btnAddAdmin = document.getElementById('btnShowAddAdmin');
    var btnAddMember = document.getElementById('btnShowAddMember');
    var btnRemoveMember = document.getElementById('btnShowRemoveMember');
    if (btnAddAdmin) btnAddAdmin.addEventListener('click', function(){ showForm('formAddAdmin'); window.comboEnhance && window.comboEnhance(); });
    if (btnAddMember) btnAddMember.addEventListener('click', function(){ showForm('formAddMember'); window.comboEnhance && window.comboEnhance(); });
    if (btnRemoveMember) btnRemoveMember.addEventListener('click', function(){ showForm('formRemoveMember'); window.comboEnhance && window.comboEnhance(); });

    // Back buttons
    var b1 = document.getElementById('btnBackFromAdmin'); if (b1) b1.addEventListener('click', showMenu);
    var b2 = document.getElementById('btnBackFromMember'); if (b2) b2.addEventListener('click', showMenu);
    var b3 = document.getElementById('btnBackFromRemove'); if (b3) b3.addEventListener('click', showMenu);

    // Add Admin: confirm before creating the new admin login.
    var addAdminForm = document.getElementById('addAdminForm');
    if (addAdminForm) {
      addAdminForm.addEventListener('submit', function (ev) {
        var uname = addAdminForm.elements['username'].value.trim();
        if (!confirm('Add "' + uname + '" as a new admin? They will have full access to SOPRA.')) {
          ev.preventDefault();
        }
      });
    }

    // Add Member: confirm before creating the new roster entry + login.
    var addMemberForm = document.getElementById('addMemberForm');
    if (addMemberForm) {
      addMemberForm.addEventListener('submit', function (ev) {
        var nameVal = addMemberForm.elements['name'].value.trim();
        var rankVal = addMemberForm.elements['rank'].value;
        if (!confirm('Add "' + nameVal + '" (' + rankVal + ') as a new member? A login will be generated for them.')) {
          ev.preventDefault();
        }
      });
    }

    // Initialize: show menu by default
    showMenu();
  })();
</script>
<?php endif; ?>

<script src="searchable_dropdown.js?v=1"></script>
<script>
  // Rank filter chips toggle by re-submitting the filter form with an
  // updated comma-separated list in the hidden "ranks" field.
  document.querySelectorAll('#rankChips .chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      var field = document.getElementById('ranksField');
      var current = field.value ? field.value.split(',') : [];
      var rank = chip.getAttribute('data-rank');
      var idx = current.indexOf(rank);
      if (idx === -1) current.push(rank); else current.splice(idx, 1);
      field.value = current.join(',');
      document.getElementById('filterForm').submit();
    });
  });

  // Manage-users modal: Remove Member — removes a personnel record (and
  // their payment/duty history) by personnel_id, same cascading Rank ->
  // Name pattern as the State -> District picker elsewhere. This does not
  // touch any login account the member may have (see Remove User below).
  var MEMBERS_BY_RANK = <?= json_encode($membersByRank) ?>;
  var ALL_MEMBERS = <?= json_encode(array_map(function ($m) {
      return ['personnel_id' => $m['personnel_id'], 'label' => $m['name'] . ' (' . $m['rank_name'] . ')'];
  }, $membersForRemoval)) ?>;
  var removeRankSelect = document.getElementById('removeRankSelect');
  if (removeRankSelect) {
    var rebuildRemoveNameSelect = function () {
      var oldSelect = document.getElementById('removeNameSelect');
      var wrap = oldSelect.closest('.combo') || oldSelect.parentNode;
      var rank = removeRankSelect.value;

      var fresh = document.createElement('select');
      fresh.name = 'id';
      fresh.id = 'removeNameSelect';
      fresh.className = 'combo-select';
      fresh.required = true;
      fresh.setAttribute('data-placeholder', '\u2014 Select Name \u2014');
      fresh.appendChild(new Option('\u2014 Select Name \u2014', ''));

      var list = rank ? (MEMBERS_BY_RANK[rank] || []) : ALL_MEMBERS;
      list.forEach(function (m) {
        fresh.appendChild(new Option(m.label, String(m.personnel_id)));
      });
      fresh.disabled = list.length === 0;

      if (wrap.classList && wrap.classList.contains('combo')) {
        wrap.parentNode.insertBefore(fresh, wrap);
        wrap.remove();
      } else {
        wrap.replaceChild(fresh, oldSelect);
      }
      window.comboEnhance(fresh);
    };
    removeRankSelect.addEventListener('change', rebuildRemoveNameSelect);
    rebuildRemoveNameSelect();
  }

  var removeUserForm = document.getElementById('removeUserForm');
  if (removeUserForm) {
    removeUserForm.addEventListener('submit', function (ev) {
      var nameSelect = document.getElementById('removeNameSelect');
      var chosen = nameSelect.options[nameSelect.selectedIndex];
      var label = chosen ? chosen.text : '';
      if (!nameSelect.value) {
        ev.preventDefault();
        return;
      }
      if (!confirm('Remove member "' + label + '" from the list? This also clears their payment/duty history and cannot be undone.')) {
        ev.preventDefault();
      }
    });
  }

  // Payment cells: members pay whatever RM amount they choose each
  // month, so marking a red cell as PAID asks the admin how much was
  // actually collected before it flips green. Reverting a green cell
  // back to red asks for confirmation, since it clears the recorded
  // amount and date.
  document.querySelectorAll('.pay-form').forEach(function(form){
    form.addEventListener('submit', function(ev){
      var isPaid = form.getAttribute('data-paid') === '1';
      var month  = form.getAttribute('data-month');
      if (!isPaid) {
        var amount = prompt('Payment amount for ' + month + ' (RM):', '');
        if (amount === null) { ev.preventDefault(); return; }
        amount = amount.trim().replace(',', '.');
        if (amount === '' || isNaN(amount) || Number(amount) < 0) {
          alert('Please enter a valid RM amount.');
          ev.preventDefault();
          return;
        }
        form.querySelector('.amount-field').value = amount;
      } else {
        if (!confirm('Reopen payment status for ' + month + '? The recorded amount & date will be deleted.')) {
          ev.preventDefault();
        }
      }
    });
  });
</script>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js').catch(function () {});
  }
</script>
</body>
</html>
