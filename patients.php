<?php
$pageTitle = 'Patients';
include 'db.php';

$action  = $_GET['action'] ?? '';
$viewId  = isset($_GET['view']) ? (int)$_GET['view'] : null;
$msg     = '';
$msgType = '';

// ── ADD PATIENT ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'add_patient') {
    $sql = "INSERT INTO PATIENT (PatientNo, PatientName, DateOfBirth, BedNo, DateAdmitted, CareUnitNo)
            VALUES (?, ?, ?, ?, ?, ?)";
    $params = [
        [(int)$_POST['PatientNo'],    SQLSRV_PARAM_IN],
        [$_POST['PatientName'],       SQLSRV_PARAM_IN],
        [$_POST['DateOfBirth'],       SQLSRV_PARAM_IN],
        [(int)$_POST['BedNo'],        SQLSRV_PARAM_IN],
        [$_POST['DateAdmitted'],      SQLSRV_PARAM_IN],
        [(int)$_POST['CareUnitNo'],   SQLSRV_PARAM_IN],
    ];
    $res = sqlsrv_query($conn, $sql, $params);
    if ($res) {
        $msg = "Patient {$_POST['PatientName']} added successfully.";
        $msgType = 'success';
        $action = '';
    } else {
        $msg = "Error: " . print_r(sqlsrv_errors(), true);
        $msgType = 'error';
    }
}

// ── ASSIGN COMPLAINT & TREATMENT ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'assign_complaint') {
    $sql = "INSERT INTO RECEIVES_TREATMENT (PatientNo, ComplaintCode, TreatmentCode, DateStarted, DateEnded)
            VALUES (?, ?, ?, ?, ?)";
    $dateEnded = !empty($_POST['DateEnded']) ? $_POST['DateEnded'] : null;
    $params = [
        [(int)$_POST['PatientNo'],    SQLSRV_PARAM_IN],
        [(int)$_POST['ComplaintCode'],SQLSRV_PARAM_IN],
        [(int)$_POST['TreatmentCode'],SQLSRV_PARAM_IN],
        [$_POST['DateStarted'],       SQLSRV_PARAM_IN],
        [$dateEnded,                  SQLSRV_PARAM_IN],
    ];
    $res = sqlsrv_query($conn, $sql, $params);
    if ($res) {
        $msg = "Complaint & treatment assigned successfully.";
        $msgType = 'success';
        $action = '';
    } else {
        $msg = "Error: " . print_r(sqlsrv_errors(), true);
        $msgType = 'error';
    }
}

// ── FETCH DROPDOWNS ───────────────────────────────────────────────────────────
$careUnits = [];
$cuRes = sqlsrv_query($conn, "SELECT cu.CareUnitNo, cu.WardName FROM CARE_UNIT cu ORDER BY cu.CareUnitNo");
while ($r = sqlsrv_fetch_array($cuRes, SQLSRV_FETCH_ASSOC)) $careUnits[] = $r;

$complaints = [];
$cRes = sqlsrv_query($conn, "SELECT ComplaintCode, Description FROM COMPLAINT ORDER BY ComplaintCode");
while ($r = sqlsrv_fetch_array($cRes, SQLSRV_FETCH_ASSOC)) $complaints[] = $r;

$treatments = [];
$tRes = sqlsrv_query($conn, "SELECT TreatmentCode, Description FROM TREATMENT ORDER BY TreatmentCode");
while ($r = sqlsrv_fetch_array($tRes, SQLSRV_FETCH_ASSOC)) $treatments[] = $r;

$patients = [];
$pRes = sqlsrv_query($conn,
    "SELECT p.PatientNo, p.PatientName, p.DateOfBirth, p.BedNo, p.DateAdmitted,
            cu.CareUnitNo, cu.WardName
     FROM PATIENT p
     JOIN CARE_UNIT cu ON cu.CareUnitNo = p.CareUnitNo
     ORDER BY p.PatientNo"
);
while ($r = sqlsrv_fetch_array($pRes, SQLSRV_FETCH_ASSOC)) $patients[] = $r;

// ── PATIENT DETAIL ────────────────────────────────────────────────────────────
$patientDetail = null;
$patientTx = [];
if ($viewId) {
    $dRes = sqlsrv_query($conn,
        "SELECT p.*, cu.WardName, w.Specialty,
                n.Name AS NurseInCharge,
                d.Name AS DoctorName, d.Position AS DoctorPosition
         FROM PATIENT p
         JOIN CARE_UNIT cu ON cu.CareUnitNo = p.CareUnitNo
         JOIN WARD w ON w.WardName = cu.WardName
         JOIN NURSE n ON n.StaffNo = cu.InChargeNurseNo
         LEFT JOIN DOCTOR d ON d.InChargeCareUnitNo = p.CareUnitNo
         WHERE p.PatientNo = ?",
        [[$viewId, SQLSRV_PARAM_IN]]
    );
    $patientDetail = sqlsrv_fetch_array($dRes, SQLSRV_FETCH_ASSOC);

    $txRes = sqlsrv_query($conn,
        "SELECT c.ComplaintCode, c.Description AS Complaint,
                t.TreatmentCode, t.Description AS Treatment,
                rt.DateStarted, rt.DateEnded
         FROM RECEIVES_TREATMENT rt
         JOIN COMPLAINT c ON c.ComplaintCode = rt.ComplaintCode
         JOIN TREATMENT t ON t.TreatmentCode = rt.TreatmentCode
         WHERE rt.PatientNo = ?
         ORDER BY rt.DateStarted DESC",
        [[$viewId, SQLSRV_PARAM_IN]]
    );
    while ($r = sqlsrv_fetch_array($txRes, SQLSRV_FETCH_ASSOC)) $patientTx[] = $r;
}

include 'nav.php';
?>

<div class="page">
  <div class="page-header">
    <h1>Patients</h1>
    <p>Manage patient records, admissions, complaints and treatments</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- ── TABS ── -->
  <div class="tabs">
    <a class="tab <?= (!$action && !$viewId) ? 'active' : '' ?>" href="patients.php">All Patients</a>
    <a class="tab <?= $action==='add' ? 'active' : '' ?>" href="patients.php?action=add">+ Add Patient</a>
    <a class="tab <?= $action==='assign' ? 'active' : '' ?>" href="patients.php?action=assign">+ Assign Complaint / Treatment</a>
  </div>

  <?php if ($viewId && $patientDetail): ?>
  <!-- ── PATIENT DETAIL VIEW ── -->
  <div style="margin-bottom:14px">
    <a href="patients.php" class="btn btn-ghost btn-sm">← Back to All Patients</a>
  </div>
  <div class="card" style="margin-bottom:22px">
    <div class="card-header">
      <div class="icon">👤</div>
      <h2><?= htmlspecialchars($patientDetail['PatientName']) ?> &nbsp;<span class="badge badge-navy">#<?= $viewId ?></span></h2>
    </div>
    <div class="card-body">
      <div class="grid-3">
        <div><div class="section-title" style="margin-top:0">Personal</div>
          <p><b>DOB:</b> <?= fmtDate($patientDetail['DateOfBirth']) ?></p>
          <p><b>Bed No:</b> <?= $patientDetail['BedNo'] ?></p>
          <p><b>Admitted:</b> <?= fmtDate($patientDetail['DateAdmitted']) ?></p>
        </div>
        <div><div class="section-title" style="margin-top:0">Ward / Care Unit</div>
          <p><b>Ward:</b> <span class="badge badge-teal"><?= $patientDetail['WardName'] ?></span></p>
          <p><b>Specialty:</b> <?= $patientDetail['Specialty'] ?></p>
          <p><b>Care Unit:</b> <?= $patientDetail['CareUnitNo'] ?></p>
          <p><b>Nurse In Charge:</b> <?= htmlspecialchars($patientDetail['NurseInCharge']) ?></p>
        </div>
        <div><div class="section-title" style="margin-top:0">Responsible Doctor</div>
          <?php if ($patientDetail['DoctorName']): ?>
            <p><b>Doctor:</b> <?= htmlspecialchars($patientDetail['DoctorName']) ?></p>
            <p><b>Position:</b> <?= $patientDetail['DoctorPosition'] ?></p>
          <?php else: ?>
            <p class="badge badge-amber">No doctor assigned to this care unit</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="section-title">Complaints & Treatments</div>
      <?php if (empty($patientTx)): ?>
        <div class="alert alert-info">No complaints or treatments on record for this patient.</div>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Complaint</th><th>Treatment</th><th>Date Started</th><th>Date Ended</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($patientTx as $tx): ?>
            <tr>
              <td><?= htmlspecialchars($tx['Complaint']) ?></td>
              <td><?= htmlspecialchars($tx['Treatment']) ?></td>
              <td><?= fmtDate($tx['DateStarted']) ?></td>
              <td><?= fmtDate($tx['DateEnded']) ?></td>
              <td>
                <?php if (!$tx['DateEnded'] || $tx['DateEnded'] === 'N/A'): ?>
                  <span class="badge badge-amber">Active</span>
                <?php else: ?>
                  <span class="badge badge-green">Completed</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php elseif ($action === 'add'): ?>
  <!-- ── ADD PATIENT FORM ── -->
  <div class="card">
    <div class="card-header"><h2>Register New Patient</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="add_patient">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Patient No *</label>
            <input type="number" name="PatientNo" required placeholder="e.g. 1031">
          </div>
          <div class="field">
            <label>Full Name *</label>
            <input type="text" name="PatientName" required placeholder="e.g. Ahmed Khan">
          </div>
          <div class="field">
            <label>Date of Birth</label>
            <input type="date" name="DateOfBirth">
          </div>
          <div class="field">
            <label>Bed No</label>
            <input type="number" name="BedNo" placeholder="e.g. 5">
          </div>
          <div class="field">
            <label>Date Admitted *</label>
            <input type="date" name="DateAdmitted" required>
          </div>
          <div class="field">
            <label>Care Unit *</label>
            <select name="CareUnitNo" required>
              <option value="">— Select Care Unit —</option>
              <?php foreach ($careUnits as $cu): ?>
                <option value="<?= $cu['CareUnitNo'] ?>">Unit <?= $cu['CareUnitNo'] ?> — <?= $cu['WardName'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Save Patient</button>
          <a href="patients.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($action === 'assign'): ?>
  <!-- ── ASSIGN COMPLAINT / TREATMENT ── -->
  <div class="card">
    <div class="card-header"><h2>Assign Complaint & Treatment to Patient</h2></div>
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
            <label>Date Ended <span style="font-weight:400">(leave blank if ongoing)</span></label>
            <input type="date" name="DateEnded">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Assign</button>
          <a href="patients.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php else: ?>
  <!-- ── PATIENT LIST ── -->
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>No</th><th>Name</th><th>DOB</th><th>Bed</th>
          <th>Care Unit</th><th>Ward</th><th>Admitted</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($patients as $p): ?>
        <tr>
          <td><span class="badge badge-navy"><?= $p['PatientNo'] ?></span></td>
          <td><?= htmlspecialchars($p['PatientName']) ?></td>
          <td><?= fmtDate($p['DateOfBirth']) ?></td>
          <td><?= $p['BedNo'] ?></td>
          <td><?= $p['CareUnitNo'] ?></td>
          <td><span class="badge badge-teal"><?= $p['WardName'] ?></span></td>
          <td><?= fmtDate($p['DateAdmitted']) ?></td>
          <td><a href="patients.php?view=<?= $p['PatientNo'] ?>" class="btn btn-ghost btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<footer>Ivor Paine Memorial Hospital &nbsp;·&nbsp; <span>ipmhDB</span></footer>
</body></html>
