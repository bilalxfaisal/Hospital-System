<?php
$pageTitle = 'Treatments';
include 'db.php';

$action = $_GET['action'] ?? '';
$msg = $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'add_treatment') {
    $sql = "INSERT INTO TREATMENT (TreatmentCode, Description) VALUES (?, ?)";
    $params = [[(int)$_POST['TreatmentCode'], SQLSRV_PARAM_IN], [$_POST['Description'], SQLSRV_PARAM_IN]];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Treatment added." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
    $action = '';
}

// End a treatment for a patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'end_treatment') {
    $sql = "UPDATE RECEIVES_TREATMENT SET DateEnded=? WHERE PatientNo=? AND ComplaintCode=? AND TreatmentCode=?";
    $params = [
        [$_POST['DateEnded'],          SQLSRV_PARAM_IN],
        [(int)$_POST['PatientNo'],     SQLSRV_PARAM_IN],
        [(int)$_POST['ComplaintCode'], SQLSRV_PARAM_IN],
        [(int)$_POST['TreatmentCode'], SQLSRV_PARAM_IN],
    ];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Treatment ended." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
}

$treatments = [];
$tRes = sqlsrv_query($conn,
    "SELECT t.TreatmentCode, t.Description,
            COUNT(rt.PatientNo) AS Uses
     FROM TREATMENT t
     LEFT JOIN RECEIVES_TREATMENT rt ON rt.TreatmentCode = t.TreatmentCode
     GROUP BY t.TreatmentCode, t.Description
     ORDER BY t.TreatmentCode"
);
while ($r = sqlsrv_fetch_array($tRes, SQLSRV_FETCH_ASSOC)) $treatments[] = $r;

// Active treatments (for end-treatment form)
$activeTx = [];
$atRes = sqlsrv_query($conn,
    "SELECT rt.PatientNo, p.PatientName, rt.ComplaintCode, c.Description AS Complaint,
            rt.TreatmentCode, t.Description AS Treatment, rt.DateStarted
     FROM RECEIVES_TREATMENT rt
     JOIN PATIENT p   ON p.PatientNo    = rt.PatientNo
     JOIN COMPLAINT c ON c.ComplaintCode= rt.ComplaintCode
     JOIN TREATMENT t ON t.TreatmentCode= rt.TreatmentCode
     WHERE rt.DateEnded IS NULL
     ORDER BY rt.DateStarted"
);
while ($r = sqlsrv_fetch_array($atRes, SQLSRV_FETCH_ASSOC)) $activeTx[] = $r;

include 'nav.php';
?>

<div class="page">
  <div class="page-header">
    <h1>Treatments</h1>
    <p>Manage treatments and close out ongoing patient treatments</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="tabs">
    <a class="tab <?= !$action?'active':'' ?>" href="treatments.php">All Treatments</a>
    <a class="tab <?= $action==='add'?'active':'' ?>" href="treatments.php?action=add">+ Add Treatment</a>
    <a class="tab <?= $action==='end'?'active':'' ?>" href="treatments.php?action=end">✓ End Active Treatment</a>
  </div>

  <?php if ($action === 'add'): ?>
  <div class="card">
    <div class="card-header"><h2>Add New Treatment</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="add_treatment">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Treatment Code *</label>
            <input type="number" name="TreatmentCode" required placeholder="e.g. 621">
          </div>
          <div class="field">
            <label>Description *</label>
            <input type="text" name="Description" required placeholder="e.g. Physical Therapy">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Add Treatment</button>
          <a href="treatments.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($action === 'end'): ?>
  <div class="card">
    <div class="card-header"><h2>End an Active Patient Treatment</h2></div>
    <div class="card-body">
      <?php if (empty($activeTx)): ?>
        <div class="alert alert-info">No active treatments to close.</div>
      <?php else: ?>
      <form method="POST">
        <input type="hidden" name="_form" value="end_treatment">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Active Treatment Record *</label>
            <select name="PatientNo" id="txSelect" required onchange="fillFields(this)">
              <option value="">— Select Active Treatment —</option>
              <?php foreach ($activeTx as $tx): ?>
                <option value="<?= $tx['PatientNo'] ?>"
                        data-cc="<?= $tx['ComplaintCode'] ?>"
                        data-tc="<?= $tx['TreatmentCode'] ?>">
                  <?= htmlspecialchars($tx['PatientName']) ?> — <?= htmlspecialchars($tx['Complaint']) ?> → <?= htmlspecialchars($tx['Treatment']) ?> (since <?= fmtDate($tx['DateStarted']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Date Ended *</label>
            <input type="date" name="DateEnded" required>
          </div>
          <input type="hidden" name="ComplaintCode" id="ccField">
          <input type="hidden" name="TreatmentCode" id="tcField">
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">End Treatment</button>
          <a href="treatments.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
      <script>
      function fillFields(sel) {
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('ccField').value = opt.getAttribute('data-cc') || '';
        document.getElementById('tcField').value = opt.getAttribute('data-tc') || '';
      }
      </script>
      <?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Code</th><th>Description</th><th>Times Used</th></tr></thead>
      <tbody>
        <?php foreach ($treatments as $t): ?>
        <tr>
          <td><span class="badge badge-navy"><?= $t['TreatmentCode'] ?></span></td>
          <td><?= htmlspecialchars($t['Description']) ?></td>
          <td><span class="badge badge-<?= $t['Uses']>0?'teal':'amber' ?>"><?= $t['Uses'] ?> time<?= $t['Uses']!=1?'s':'' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<footer>Ivor Paine Memorial Hospital &nbsp;·&nbsp; <span>ipmhDB</span></footer>
</body></html>
