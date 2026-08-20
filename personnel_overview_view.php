<?php
/**
 * views/personnel_overview_view.php
 * SOPRA — HTML for the regular member's "My Ledger" page: profile
 * card, year stats, the 12-month strip, and this member's own duty
 * assignments only.
 *
 * Rendered by personnel_overview.php via include, so it shares that
 * controller's variable scope: $year, $person, $payMonths,
 * $paidCount, $yearTotal, $duties.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Ledger &mdash; <?= APP_NAME ?></title>
<link rel="icon" href="assets/logo.png">
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
          <h1>My Payment Ledger</h1>
          <small>Personal view &mdash; your own records only</small>
        </div>
      </div>
    </div>
    <div class="who">
      <span class="role-badge viewer">Member</span>
      <span><?= e($_SESSION['username']) ?></span>
      <a href="logout.php" class="btn btn-ghost" onclick="return confirm('Log out of SOPRA?');">Log Out</a>
    </div>
  </div>

  <div class="viewer-banner">
    <span>&#128274;</span>
    <span>You can only view your own information &mdash; name, rank, monthly payments and duty assignments. Other members' records are not shown.</span>
  </div>

  <?php if (!$person): ?>
    <div class="panel">
      <p>Your account is not yet linked to any member record. Please contact an admin for help.</p>
    </div>
  <?php else: ?>

    <div class="profile-card">
      <div class="profile-item">
        <div class="lbl">Name / Call Sign</div>
        <div class="val"><?= e($person['name']) ?></div>
      </div>
      <div class="profile-item">
        <div class="lbl">Rank</div>
        <div class="val"><?= e($person['rank_name']) ?></div>
      </div>
      <div class="profile-item">
        <div class="lbl">Total Contribution <?= $year ?></div>
        <div class="val fee">RM <?= number_format($yearTotal, 2) ?></div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-row">
        <div class="control">
          <label>Year</label>
          <div class="year-nav">
            <a class="btn" href="personnel_overview.php?year=<?= $year - 1 ?>" style="background:var(--paper-alt);border:1px solid var(--line);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--navy);text-decoration:none;">&#8592;</a>
            <span class="yr"><?= $year ?></span>
            <a class="btn" href="personnel_overview.php?year=<?= $year + 1 ?>" style="background:var(--paper-alt);border:1px solid var(--line);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--navy);text-decoration:none;">&#8594;</a>
          </div>
        </div>
      </div>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="num"><?= $paidCount ?>/12</div>
        <div class="lbl">Months Paid In <?= $year ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= round(($paidCount / 12) * 100) ?>%"></div></div>
      </div>
      <div class="stat-card">
        <div class="num"><?= 12 - $paidCount ?></div>
        <div class="lbl">Months Remaining Unpaid (<?= $year ?>)</div>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <?php foreach (MONTHS as $m): ?><th><?= $m ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <?php foreach (MONTHS as $idx => $m):
                $info = $payMonths[$idx] ?? ['paid' => false, 'amount' => null, 'paid_date' => null]; ?>
              <td class="cell" title="<?= e($m) ?>: <?= $info['paid'] ? fmtRM($info['amount']) . ' — paid ' . fmtDate($info['paid_date']) : 'Not paid' ?>">
                <div class="fill <?= $info['paid'] ? 'paid' : 'unpaid' ?>"><?= $info['paid'] && $info['amount'] !== null ? number_format($info['amount'], 0) : ($info['paid'] ? 'PAID' : '') ?></div>
              </td>
            <?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="legend">
      <span><span class="swatch" style="background:var(--green)"></span>Paid (figure = RM)</span>
      <span><span class="swatch" style="background:var(--red)"></span>Not Paid</span>
      <span style="color:var(--slate-soft)">View-only &mdash; contact an admin for any corrections.</span>
    </div>

    <div class="panel" style="margin-top:24px;">
      <h3 style="margin-top:0;font-family:'Oswald',sans-serif;color:var(--navy);letter-spacing:1px;text-transform:uppercase;font-size:15px;">My Duty Assignments</h3>
      <?php if (empty($duties)): ?>
        <p style="color:var(--slate-soft);font-size:13.5px;margin:0;">No duty assignments recorded for you yet.</p>
      <?php else: ?>
        <div class="table-wrap" style="margin-top:6px;">
          <table>
            <thead>
              <tr><th class="left">State / District</th><th class="left">Type</th><th>Departure</th><th>Return</th><th>Duration</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($duties as $d):
                $status = dutyStatus($d['date_start'], $d['date_end']);
                $tagClass = $status === 'Completed' ? '' : 'user';
              ?>
                <tr>
                  <td class="info"><?= e($d['district']) ?>, <?= e($d['state']) ?><?php if ($d['location']): ?><div style="color:var(--slate-soft);font-size:12px;margin-top:2px;"><?= e($d['location']) ?></div><?php endif; ?></td>
                  <td class="info"><?= e(DUTY_TYPES[$d['duty_type']] ?? $d['duty_type']) ?></td>
                  <td class="info" style="text-align:center;"><?= e(fmtDate($d['date_start'])) ?></td>
                  <td class="info" style="text-align:center;"><?= $d['date_end'] ? e(fmtDate($d['date_end'])) : '—' ?></td>
                  <td class="info" style="text-align:center;"><?= e(fmtDuration($d['date_start'], $d['date_end'])) ?></td>
                  <td class="info" style="text-align:center;">
                    <span class="rtag <?= $tagClass ?>" style="<?= $status === 'Completed' ? 'background:var(--paper-alt);color:var(--slate-soft);' : '' ?>"><?= e($status) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  <?php endif; ?>

  <footer class="note">SOPRA &mdash; personal view.</footer>
</div>
</body>
</html>
