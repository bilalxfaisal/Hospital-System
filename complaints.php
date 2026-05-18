<?php
$pageTitle = 'Complaints';
include 'db.php';

$action = $_GET['action'] ?? '';
$msg = $msgType = '';

// ── ADD COMPLAINT ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'add_complaint') {
    $sql = "INSERT INTO COMPLAINT (ComplaintCode, Description) VALUES (?, ?)";
    $params = [[(int)$_POST['ComplaintCode'], SQLSRV_PARAM_IN], [$_POST['Description'], SQLSRV_PARAM_IN]];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Complaint registered." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
    $action = '';
}

// ── ASSIGN COMPLAINT ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'assign_complaint') {
    $dateEnded = !empty($_POST['DateEnded']) ? $_POST['DateEnded'] : null;
    $sql = "INSERT INTO RECEIVES_TREATMENT (PatientNo, ComplaintCode, TreatmentCode, DateStarted, DateEnded)
            VALUES (?, ?, ?, ?, ?)";
    $params = [
        [(int)$_POST['PatientNo'],     SQLSRV_PARAM_IN],
        [(int)$_POST['ComplaintCode'], SQLSRV_PARAM_IN],
        [(int)$_POST['TreatmentCode'], SQLSRV_PARAM_IN],
        [$_POST['DateStarted'],        SQLSRV_PARAM_IN],
        [$dateEnded,                   SQLSRV_PARAM_IN],
    ];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Complaint assigned to patient with treatment." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
    $action = '';
}

// ── DATA ──────────────────────────────────────────────────────────────────────
$complaints = [];
$cRes = sqlsrv_query($conn,
    "SELECT c.ComplaintCode, c.Description,
            COUNT(rt.PatientNo) AS PatientCount
     FROM COMPLAINT c
     LEFT JOIN RECEIVES_TREATMENT rt ON rt.ComplaintCode = c.ComplaintCode
     GROUP BY c.ComplaintCode, c.Description
     ORDER BY c.ComplaintCode"
);
while ($r = sqlsrv_fetch_array($cRes, SQLSRV_FETCH_ASSOC)) $complaints[] = $r;

$patients = [];
$pRes = sqlsrv_query($conn, "SELECT PatientNo, PatientName FROM PATIENT ORDER BY PatientNo");
while ($r = sqlsrv_fetch_array($pRes, SQLSRV_FETCH_ASSOC)) $patients[] = $r;

$treatments = [];
$tRes = sqlsrv_query($conn, "SELECT TreatmentCode, Description FROM TREATMENT ORDER BY TreatmentCode");
while ($r = sqlsrv_fetch_array($tRes, SQLSRV_FETCH_ASSOC)) $treatments[] = $r;

include 'nav.php';
?>

<div class="page">
  <div class="page-header">
    <h1>Complaints</h1>
    <p>Register new complaints and assign them to patients with treatments</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="tabs">
    <a class="tab <?= !$action?'active':'' ?>" href="complaints.php">All Complaints</a>
    <a class="tab <?= $action==='add'?'active':'' ?>" href="complaints.php?action=add">+ Add Complaint</a>
    <a class="tab <?= $action==='assign'?'active':'' ?>" href="complaints.php?action=assign">+ Assign to Patient</a>
  </div>

  <?php if ($action === 'add'): ?>
  <div class="card">
    <div class="card-header"><h2>Register New Complaint</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="add_complaint">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Complaint Code *</label>
            <input type="number" name="ComplaintCode" required placeholder="e.g. 521">
          </div>
          <div class="field">
            <label>Description *</label>
            <input type="text" name="Description" required placeholder="e.g. Chronic Back Pain">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Register Complaint</button>
          <a href="complaints.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($action === 'assign'): ?>
  <div class="card">
    <div class="card-header"><h2>Assign Complaint to Patient</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="assign_complaint">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Patient *</label>
            <select name="PatientNo" required>
              <option value="">— Select Patient —</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?= $p['PatientNo'] ?>"><?= $p['PatientNo'] ?> — <?= htmlspecialchars($p['PatientName']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Complaint *</label>
            <select name="ComplaintCode" required>
              <option value="">— Select Complaint —</option>
              <?php foreach ($complaints as $c): ?>
                <option value="<?= $c['ComplaintCode'] ?>"><?= $c['ComplaintCode'] ?> — <?= htmlspecialchars($c['Description']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Treatment *</label>
            <select name="TreatmentCode" required>
              <option value="">— Select Treatment —</option>
              <?php foreach ($treatments as $t): ?>
                <option value="<?= $t['TreatmentCode'] ?>"><?= $t['TreatmentCode'] ?> — <?= htmlspecialchars($t['Description']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Date Started *</label>
            <input type="date" name="DateStarted" required>
          </div>
          <div class="field">
            <label>Date Ended <small>(blank if ongoing)</small></label>
            <input type="date" name="DateEnded">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Assign</button>
          <a href="complaints.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Code</th><th>Description</th><th>Patients Affected</th></tr></thead>
      <tbody>
        <?php foreach ($complaints as $c): ?>
        <tr>
          <td><span class="badge badge-navy"><?= $c['ComplaintCode'] ?></span></td>
          <td><?= htmlspecialchars($c['Description']) ?></td>
          <td>
            <?php if ($c['PatientCount'] > 0): ?>
              <span class="badge badge-teal"><?= $c['PatientCount'] ?> patient<?= $c['PatientCount']>1?'s':'' ?></span>
            <?php else: ?>
              <span class="badge badge-amber">Unassigned</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<footer>Ivor Paine Memorial Hospital &nbsp;·&nbsp; <span>ipmhDB</span></footer>
</body></html>
