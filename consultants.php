<?php
$pageTitle = 'Consultants';
include 'db.php';

$action  = $_GET['action'] ?? '';
$msg = $msgType = '';

// ── PROMOTE DOCTOR TO CONSULTANT ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'make_consultant') {
    $docNo = (int)$_POST['DoctorNo'];
    // First update doctor position
    sqlsrv_query($conn, "UPDATE DOCTOR SET Position='Consultant' WHERE DoctorNo=?", [[$docNo, SQLSRV_PARAM_IN]]);
    $sql = "INSERT INTO CONSULTANT (DoctorNo, Specialty) VALUES (?, ?)";
    $params = [[$docNo, SQLSRV_PARAM_IN], [$_POST['Specialty'], SQLSRV_PARAM_IN]];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Doctor promoted to Consultant." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
    $action = '';
}

// ── ADD DOCTOR TO CONSULTANT TEAM ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'add_member') {
    $sql = "UPDATE DOCTOR SET ConsultantNo=? WHERE DoctorNo=?";
    $params = [
        [(int)$_POST['ConsultantNo'], SQLSRV_PARAM_IN],
        [(int)$_POST['DoctorNo'],     SQLSRV_PARAM_IN],
    ];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Doctor added to consultant team." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
    $action = '';
}

// ── FETCH ALL CONSULTANTS WITH TEAMS ─────────────────────────────────────────
$consultants = [];
$cRes = sqlsrv_query($conn,
    "SELECT c.DoctorNo AS ConsultantNo, d.Name AS ConsultantName,
            c.Specialty, d.DateJoinedTeam, d.InChargeCareUnitNo, cu.WardName
     FROM CONSULTANT c
     JOIN DOCTOR d ON d.DoctorNo = c.DoctorNo
     LEFT JOIN CARE_UNIT cu ON cu.CareUnitNo = d.InChargeCareUnitNo
     ORDER BY c.DoctorNo"
);
while ($r = sqlsrv_fetch_array($cRes, SQLSRV_FETCH_ASSOC)) {
    $consNo = $r['ConsultantNo'];
    $consultants[$consNo] = $r;
    $consultants[$consNo]['members'] = [];

    // Fetch team members
    $mRes = sqlsrv_query($conn,
        "SELECT d.DoctorNo, d.Name, d.Position, d.DateJoinedTeam,
                cu.WardName
         FROM DOCTOR d
         LEFT JOIN CARE_UNIT cu ON cu.CareUnitNo = d.InChargeCareUnitNo
         WHERE d.ConsultantNo = ?
         ORDER BY d.Position, d.Name",
        [[$consNo, SQLSRV_PARAM_IN]]
    );
    while ($m = sqlsrv_fetch_array($mRes, SQLSRV_FETCH_ASSOC)) {
        $consultants[$consNo]['members'][] = $m;
    }
}

// Dropdowns
$allDoctors = [];
$dRes = sqlsrv_query($conn,
    "SELECT d.DoctorNo, d.Name, d.Position,
            CASE WHEN c.DoctorNo IS NOT NULL THEN 1 ELSE 0 END AS IsConsultant
     FROM DOCTOR d LEFT JOIN CONSULTANT c ON c.DoctorNo = d.DoctorNo
     ORDER BY d.DoctorNo"
);
while ($r = sqlsrv_fetch_array($dRes, SQLSRV_FETCH_ASSOC)) $allDoctors[] = $r;
$nonConsultants = array_filter($allDoctors, fn($d) => !$d['IsConsultant']);

include 'nav.php';
?>

<div class="page">
  <div class="page-header">
    <h1>Consultants &amp; Teams</h1>
    <p>All consultant doctors and their assigned doctor teams</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="tabs">
    <a class="tab <?= !$action ? 'active' : '' ?>" href="consultants.php">All Teams</a>
    <a class="tab <?= $action==='add_member' ? 'active' : '' ?>" href="consultants.php?action=add_member">+ Add Doctor to Team</a>
    <a class="tab <?= $action==='promote' ? 'active' : '' ?>" href="consultants.php?action=promote">+ Promote to Consultant</a>
  </div>

  <?php if ($action === 'add_member'): ?>
  <div class="card">
    <div class="card-header"><h2>Add Doctor to a Consultant's Team</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="add_member">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Consultant (Team Leader) *</label>
            <select name="ConsultantNo" required>
              <option value="">— Select Consultant —</option>
              <?php foreach ($consultants as $c): ?>
                <option value="<?= $c['ConsultantNo'] ?>"><?= $c['ConsultantNo'] ?> — <?= htmlspecialchars($c['ConsultantName']) ?> (<?= $c['Specialty'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Doctor to Add *</label>
            <select name="DoctorNo" required>
              <option value="">— Select Doctor —</option>
              <?php foreach ($allDoctors as $d): ?>
                <option value="<?= $d['DoctorNo'] ?>"><?= $d['DoctorNo'] ?> — <?= htmlspecialchars($d['Name']) ?> (<?= $d['Position'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Add to Team</button>
          <a href="consultants.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($action === 'promote'): ?>
  <div class="card">
    <div class="card-header"><div class="icon">⬆</div><h2>Promote Doctor to Consultant</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="make_consultant">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Doctor *</label>
            <select name="DoctorNo" required>
              <option value="">— Select Non-Consultant Doctor —</option>
              <?php foreach ($nonConsultants as $d): ?>
                <option value="<?= $d['DoctorNo'] ?>"><?= $d['DoctorNo'] ?> — <?= htmlspecialchars($d['Name']) ?> (<?= $d['Position'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Specialty *</label>
            <input type="text" name="Specialty" required placeholder="e.g. Cardiology">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Promote to Consultant</button>
          <a href="consultants.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php else: ?>
  <!-- ── CONSULTANT TEAM CARDS ── -->
  <div class="grid-2">
    <?php foreach ($consultants as $c): ?>
    <div class="team-card">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <div class="consultant-name"><?= htmlspecialchars($c['ConsultantName']) ?></div>
          <div class="specialty">
            <span class="badge badge-teal"><?= $c['Specialty'] ?></span>
            &nbsp;
            <?php if ($c['WardName']): ?>
              <span class="badge badge-navy"><?= $c['WardName'] ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div style="text-align:right">
          <div style="font-size:11px;color:var(--muted)">Consultant #<?= $c['ConsultantNo'] ?></div>
          <div style="font-size:11px;color:var(--muted)">Joined <?= fmtDate($c['DateJoinedTeam']) ?></div>
          <a href="doctors.php?view=<?= $c['ConsultantNo'] ?>" class="btn btn-ghost btn-sm" style="margin-top:6px">Profile</a>
        </div>
      </div>

      <div style="margin-top:14px">
        <div style="font-size:11px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--muted);margin-bottom:8px">
          Team Members (<?= count($c['members']) ?>)
        </div>
        <?php if (empty($c['members'])): ?>
          <p style="font-size:13px;color:var(--muted);font-style:italic">No doctors assigned to this team yet.</p>
        <?php else: ?>
          <?php foreach ($c['members'] as $m): ?>
          <div class="team-member">
            <div class="avatar"><?= strtoupper(substr(explode(' ', $m['Name'])[1] ?? $m['Name'], 0, 2)) ?></div>
            <div style="flex:1">
              <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($m['Name']) ?></div>
              <div style="font-size:11px;color:var(--muted)"><?= $m['Position'] ?><?= $m['WardName'] ? ' · '.$m['WardName'] : '' ?></div>
            </div>
            <a href="doctors.php?view=<?= $m['DoctorNo'] ?>" class="btn btn-ghost btn-sm">View</a>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── FLAT TABLE VIEW ── -->
  <div class="section-title" style="margin-top:36px">All Consultants — Table View</div>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr><th>Consultant No</th><th>Name</th><th>Specialty</th><th>Team Members</th><th>Ward</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($consultants as $c): ?>
        <tr>
          <td><span class="badge badge-teal"><?= $c['ConsultantNo'] ?></span></td>
          <td><?= htmlspecialchars($c['ConsultantName']) ?></td>
          <td><?= $c['Specialty'] ?></td>
          <td>
            <?php if (empty($c['members'])): ?>
              <span class="badge badge-amber">No members</span>
            <?php else: ?>
              <?= implode(', ', array_map(fn($m)=>htmlspecialchars($m['Name']), $c['members'])) ?>
            <?php endif; ?>
          </td>
          <td><?= $c['WardName'] ?? '—' ?></td>
          <td><a href="doctors.php?view=<?= $c['ConsultantNo'] ?>" class="btn btn-ghost btn-sm">Profile</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<footer>Ivor Paine Memorial Hospital &nbsp;·&nbsp; <span>ipmhDB</span></footer>
</body></html>
