<?php
$pageTitle = 'Doctors';
include 'db.php';

$action  = $_GET['action'] ?? '';
$viewId  = isset($_GET['view']) ? (int)$_GET['view'] : null;
$msg = $msgType = '';

// ── ADD DOCTOR ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'add_doctor') {
    $cuVal = !empty($_POST['InChargeCareUnitNo']) ? (int)$_POST['InChargeCareUnitNo'] : null;
    $sql = "INSERT INTO DOCTOR (DoctorNo, Name, Position, DateJoinedTeam, InChargeCareUnitNo)
            VALUES (?, ?, ?, ?, ?)";
    $params = [
        [(int)$_POST['DoctorNo'],  SQLSRV_PARAM_IN],
        [$_POST['Name'],           SQLSRV_PARAM_IN],
        [$_POST['Position'],       SQLSRV_PARAM_IN],
        [$_POST['DateJoinedTeam'], SQLSRV_PARAM_IN],
        [$cuVal,                   SQLSRV_PARAM_IN],
    ];
    $res = sqlsrv_query($conn, $sql, $params);
    if ($res) {
        // Also insert RECORD entry
        sqlsrv_query($conn, "INSERT INTO RECORD (DoctorNo) VALUES (?)", [[(int)$_POST['DoctorNo'], SQLSRV_PARAM_IN]]);
        $msg = "Doctor {$_POST['Name']} added successfully.";
        $msgType = 'success';
        $action = '';
    } else {
        $msg = "Error: " . print_r(sqlsrv_errors(), true);
        $msgType = 'error';
    }
}

// ── ADD PREV EXPERIENCE ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'add_exp') {
    $toDate = !empty($_POST['ToDate']) ? $_POST['ToDate'] : null;
    $sql = "INSERT INTO PREV_EXPERIENCE (DoctorNo, FromDate, Position, Establishment, ToDate)
            VALUES (?, ?, ?, ?, ?)";
    $params = [
        [(int)$_POST['DoctorNo'],   SQLSRV_PARAM_IN],
        [$_POST['FromDate'],        SQLSRV_PARAM_IN],
        [$_POST['Position'],        SQLSRV_PARAM_IN],
        [$_POST['Establishment'],   SQLSRV_PARAM_IN],
        [$toDate,                   SQLSRV_PARAM_IN],
    ];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Experience added." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
}

// ── ADD PERFORMANCE REVIEW ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_form'] === 'add_perf') {
    $sql = "INSERT INTO PERFORMANCE (DoctorNo, ReviewDate, Grade) VALUES (?, ?, ?)";
    $params = [
        [(int)$_POST['DoctorNo'],  SQLSRV_PARAM_IN],
        [$_POST['ReviewDate'],     SQLSRV_PARAM_IN],
        [$_POST['Grade'],          SQLSRV_PARAM_IN],
    ];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Performance review added." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
}

// ── DROPDOWNS ─────────────────────────────────────────────────────────────────
$careUnits = [];
$cuRes = sqlsrv_query($conn, "SELECT CareUnitNo, WardName FROM CARE_UNIT ORDER BY CareUnitNo");
while ($r = sqlsrv_fetch_array($cuRes, SQLSRV_FETCH_ASSOC)) $careUnits[] = $r;

$doctors = [];
$dRes = sqlsrv_query($conn,
    "SELECT d.DoctorNo, d.Name, d.Position, d.DateJoinedTeam,
            d.InChargeCareUnitNo, cu.WardName,
            CASE WHEN c.DoctorNo IS NOT NULL THEN 1 ELSE 0 END AS IsConsultant
     FROM DOCTOR d
     LEFT JOIN CARE_UNIT cu ON cu.CareUnitNo = d.InChargeCareUnitNo
     LEFT JOIN CONSULTANT c ON c.DoctorNo = d.DoctorNo
     ORDER BY d.DoctorNo"
);
while ($r = sqlsrv_fetch_array($dRes, SQLSRV_FETCH_ASSOC)) $doctors[] = $r;

// ── DOCTOR DETAIL ─────────────────────────────────────────────────────────────
$doctorDetail = $experience = $performance = [];
if ($viewId) {
    $dr = sqlsrv_query($conn,
        "SELECT d.*, cu.WardName,
                CASE WHEN c.DoctorNo IS NOT NULL THEN 1 ELSE 0 END AS IsConsultant,
                c.Specialty AS ConsultantSpecialty,
                cons.Name AS ConsultantName
         FROM DOCTOR d
         LEFT JOIN CARE_UNIT cu ON cu.CareUnitNo = d.InChargeCareUnitNo
         LEFT JOIN CONSULTANT c ON c.DoctorNo = d.DoctorNo
         LEFT JOIN DOCTOR cons  ON cons.DoctorNo = d.ConsultantNo
         WHERE d.DoctorNo = ?",
        [[$viewId, SQLSRV_PARAM_IN]]
    );
    $doctorDetail = sqlsrv_fetch_array($dr, SQLSRV_FETCH_ASSOC);

    $expRes = sqlsrv_query($conn,
        "SELECT * FROM PREV_EXPERIENCE WHERE DoctorNo = ? ORDER BY FromDate",
        [[$viewId, SQLSRV_PARAM_IN]]
    );
    while ($r = sqlsrv_fetch_array($expRes, SQLSRV_FETCH_ASSOC)) $experience[] = $r;

    $perfRes = sqlsrv_query($conn,
        "SELECT * FROM PERFORMANCE WHERE DoctorNo = ? ORDER BY ReviewDate",
        [[$viewId, SQLSRV_PARAM_IN]]
    );
    while ($r = sqlsrv_fetch_array($perfRes, SQLSRV_FETCH_ASSOC)) $performance[] = $r;

    // Patients under this doctor
    $patRes = sqlsrv_query($conn,
        "SELECT p.PatientNo, p.PatientName, p.BedNo, cu.WardName
         FROM PATIENT p
         JOIN CARE_UNIT cu ON cu.CareUnitNo = p.CareUnitNo
         WHERE p.CareUnitNo = (SELECT InChargeCareUnitNo FROM DOCTOR WHERE DoctorNo = ?)",
        [[$viewId, SQLSRV_PARAM_IN]]
    );
    $docPatients = [];
    while ($r = sqlsrv_fetch_array($patRes, SQLSRV_FETCH_ASSOC)) $docPatients[] = $r;
}

include 'nav.php';

function gradeClass($g) {
    $map = ['A+'=>'Ap','A'=>'A','B+'=>'Bp','B'=>'B','C+'=>'Cp','C'=>'C'];
    return 'grade-'.($map[$g] ?? 'B');
}
?>

<div class="page">
  <div class="page-header">
    <h1>Doctors</h1>
    <p>Staff records, experience history, performance reviews</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="tabs">
    <a class="tab <?= (!$action && !$viewId) ? 'active' : '' ?>" href="doctors.php">All Doctors</a>
    <a class="tab <?= $action==='add' ? 'active' : '' ?>" href="doctors.php?action=add">+ Add Doctor</a>
    <a class="tab <?= $action==='exp' ? 'active' : '' ?>" href="doctors.php?action=exp">+ Add Experience</a>
    <a class="tab <?= $action==='perf' ? 'active' : '' ?>" href="doctors.php?action=perf">+ Add Performance Review</a>
  </div>

  <?php if ($viewId && $doctorDetail): ?>
  <!-- ── DOCTOR DETAIL ── -->
  <div style="margin-bottom:14px">
    <a href="doctors.php" class="btn btn-ghost btn-sm">← Back</a>
  </div>
  <div class="card" style="margin-bottom:22px">
    <div class="card-header">
      <div class="icon">🩺</div>
      <h2><?= htmlspecialchars($doctorDetail['Name']) ?>
        <span class="badge badge-navy">#<?= $viewId ?></span>
        <?php if ($doctorDetail['IsConsultant']): ?>
          <span class="badge badge-teal">Consultant</span>
        <?php endif; ?>
      </h2>
    </div>
    <div class="card-body">
      <div class="grid-3">
        <div>
          <div class="section-title" style="margin-top:0">Details</div>
          <p><b>Position:</b> <?= $doctorDetail['Position'] ?? 'N/A' ?></p>
          <p><b>Date Joined:</b> <?= fmtDate($doctorDetail['DateJoinedTeam']) ?></p>
          <?php if ($doctorDetail['IsConsultant']): ?>
            <p><b>Specialty:</b> <?= $doctorDetail['ConsultantSpecialty'] ?></p>
          <?php endif; ?>
          <?php if ($doctorDetail['ConsultantName']): ?>
            <p><b>Under Consultant:</b> <?= htmlspecialchars($doctorDetail['ConsultantName']) ?></p>
          <?php endif; ?>
        </div>
        <div>
          <div class="section-title" style="margin-top:0">Assigned Care Unit</div>
          <?php if ($doctorDetail['InChargeCareUnitNo']): ?>
            <p><b>Care Unit:</b> <?= $doctorDetail['InChargeCareUnitNo'] ?></p>
            <p><b>Ward:</b> <span class="badge badge-teal"><?= $doctorDetail['WardName'] ?></span></p>
          <?php else: ?>
            <p class="badge badge-amber">No care unit assigned</p>
          <?php endif; ?>
        </div>
        <div>
          <div class="section-title" style="margin-top:0">Patients Under Care</div>
          <?php if (empty($docPatients)): ?>
            <p style="color:var(--muted);font-size:13px">None</p>
          <?php else: ?>
            <?php foreach ($docPatients as $dp): ?>
              <p style="font-size:13px">
                <a href="patients.php?view=<?= $dp['PatientNo'] ?>"><?= htmlspecialchars($dp['PatientName']) ?></a>
                <span class="badge badge-navy" style="margin-left:4px">Bed <?= $dp['BedNo'] ?></span>
              </p>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="section-title">Previous Experience</div>
      <?php if (empty($experience)): ?>
        <div class="alert alert-info">No experience records.</div>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>From</th><th>To</th><th>Position</th><th>Establishment</th></tr></thead>
          <tbody>
            <?php foreach ($experience as $e): ?>
            <tr>
              <td><?= fmtDate($e['FromDate']) ?></td>
              <td><?= fmtDate($e['ToDate']) ?></td>
              <td><?= htmlspecialchars($e['Position']) ?></td>
              <td><?= htmlspecialchars($e['Establishment']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <div class="section-title">Performance Reviews</div>
      <?php if (empty($performance)): ?>
        <div class="alert alert-info">No performance records.</div>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Review Date</th><th>Grade</th></tr></thead>
          <tbody>
            <?php foreach ($performance as $p): ?>
            <tr>
              <td><?= fmtDate($p['ReviewDate']) ?></td>
              <td><span class="<?= gradeClass($p['Grade']) ?>" style="font-size:16px"><?= $p['Grade'] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php elseif ($action === 'add'): ?>
  <div class="card">
    <div class="card-header"><h2>Register New Doctor</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="add_doctor">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Doctor No *</label>
            <input type="number" name="DoctorNo" required placeholder="e.g. 211">
          </div>
          <div class="field">
            <label>Full Name *</label>
            <input type="text" name="Name" required placeholder="Dr. Firstname Lastname">
          </div>
          <div class="field">
            <label>Position *</label>
            <select name="Position" required>
              <option value="">— Select —</option>
              <option>Consultant</option>
              <option>Registrar</option>
              <option>Junior Doctor</option>
            </select>
          </div>
          <div class="field">
            <label>Date Joined Team</label>
            <input type="date" name="DateJoinedTeam">
          </div>
          <div class="field">
            <label>In-Charge Care Unit</label>
            <select name="InChargeCareUnitNo">
              <option value="">— None —</option>
              <?php foreach ($careUnits as $cu): ?>
                <option value="<?= $cu['CareUnitNo'] ?>">Unit <?= $cu['CareUnitNo'] ?> — <?= $cu['WardName'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Save Doctor</button>
          <a href="doctors.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($action === 'exp'): ?>
  <div class="card">
    <div class="card-header"><h2>Add Previous Experience</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="add_exp">
        <div class="form-grid cols-2">
          <div class="field">
            <label>Doctor *</label>
            <select name="DoctorNo" required>
              <option value="">— Select Doctor —</option>
              <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['DoctorNo'] ?>"><?= $d['DoctorNo'] ?> — <?= htmlspecialchars($d['Name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Position *</label>
            <input type="text" name="Position" required placeholder="e.g. Registrar">
          </div>
          <div class="field">
            <label>Establishment *</label>
            <input type="text" name="Establishment" required placeholder="e.g. City Hospital">
          </div>
          <div class="field">
            <label>From Date *</label>
            <input type="date" name="FromDate" required>
          </div>
          <div class="field">
            <label>To Date <span style="font-weight:400">(blank if current)</span></label>
            <input type="date" name="ToDate">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Save Experience</button>
          <a href="doctors.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($action === 'perf'): ?>
  <div class="card">
    <div class="card-header"><h2>Add Performance Review</h2></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_form" value="add_perf">
        <div class="form-grid cols-3">
          <div class="field">
            <label>Doctor *</label>
            <select name="DoctorNo" required>
              <option value="">— Select Doctor —</option>
              <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['DoctorNo'] ?>"><?= $d['DoctorNo'] ?> — <?= htmlspecialchars($d['Name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Review Date *</label>
            <input type="date" name="ReviewDate" required>
          </div>
          <div class="field">
            <label>Grade *</label>
            <select name="Grade" required>
              <option value="">— Select —</option>
              <?php foreach (['A+','A','B+','B','C+','C'] as $g): ?>
                <option><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-teal" type="submit">Save Review</button>
          <a href="doctors.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php else: ?>
  <!-- ── DOCTOR LIST ── -->
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr><th>No</th><th>Name</th><th>Position</th><th>Date Joined</th><th>Care Unit</th><th>Ward</th><th>Role</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($doctors as $d): ?>
        <tr>
          <td><span class="badge badge-navy"><?= $d['DoctorNo'] ?></span></td>
          <td><?= htmlspecialchars($d['Name']) ?></td>
          <td><?= $d['Position'] ?></td>
          <td><?= fmtDate($d['DateJoinedTeam']) ?></td>
          <td><?= $d['InChargeCareUnitNo'] ?? '—' ?></td>
          <td><?php if ($d['WardName']): ?><span class="badge badge-teal"><?= $d['WardName'] ?></span><?php else: ?>—<?php endif; ?></td>
          <td>
            <?php if ($d['IsConsultant']): ?>
              <span class="badge badge-teal">Consultant</span>
            <?php else: ?>
              <span class="badge badge-navy">Doctor</span>
            <?php endif; ?>
          </td>
          <td><a href="doctors.php?view=<?= $d['DoctorNo'] ?>" class="btn btn-ghost btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<footer>Ivor Paine Memorial Hospital &nbsp;·&nbsp; <span>ipmhDB</span></footer>
</body></html>
