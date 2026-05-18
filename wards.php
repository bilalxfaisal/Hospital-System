<?php
$pageTitle = 'Wards';
include 'db.php';

$selected = $_POST['WardName'] ?? $_GET['ward'] ?? '';
$msg = $msgType = '';

// ── ADD WARD ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_form'] ?? '') === 'add_ward') {
    $sql = "INSERT INTO WARD (WardName, Specialty) VALUES (?, ?)";
    $params = [[$_POST['WardName2'], SQLSRV_PARAM_IN], [$_POST['Specialty'], SQLSRV_PARAM_IN]];
    $res = sqlsrv_query($conn, $sql, $params);
    $msg = $res ? "Ward added." : "Error: ".print_r(sqlsrv_errors(),true);
    $msgType = $res ? 'success' : 'error';
}

// ── WARDS LIST ────────────────────────────────────────────────────────────────
$wards = [];
$wRes = sqlsrv_query($conn, "SELECT WardName, Specialty FROM WARD ORDER BY WardName");
while ($r = sqlsrv_fetch_array($wRes, SQLSRV_FETCH_ASSOC)) $wards[] = $r;

// ── WARD DETAIL ───────────────────────────────────────────────────────────────
$wardDetail = $nurses = $patients = $doctors = [];
if ($selected) {
    $wdRes = sqlsrv_query($conn, "SELECT * FROM WARD WHERE WardName=?", [[$selected,SQLSRV_PARAM_IN]]);
    $wardDetail = sqlsrv_fetch_array($wdRes, SQLSRV_FETCH_ASSOC);

    $nRes = sqlsrv_query($conn,
        "SELECT n.StaffNo, n.Name, n.Type, n.CareUnitNo
         FROM NURSE n JOIN CARE_UNIT cu ON cu.CareUnitNo=n.CareUnitNo
         WHERE cu.WardName=? ORDER BY n.Type, n.Name",
        [[$selected,SQLSRV_PARAM_IN]]
    );
    while ($r = sqlsrv_fetch_array($nRes, SQLSRV_FETCH_ASSOC)) $nurses[$r['Type']][] = $r;

    $pRes = sqlsrv_query($conn,
        "SELECT p.PatientNo, p.PatientName, p.BedNo, p.DateAdmitted, p.CareUnitNo
         FROM PATIENT p JOIN CARE_UNIT cu ON cu.CareUnitNo=p.CareUnitNo
         WHERE cu.WardName=? ORDER BY p.PatientNo",
        [[$selected,SQLSRV_PARAM_IN]]
    );
    while ($r = sqlsrv_fetch_array($pRes, SQLSRV_FETCH_ASSOC)) $patients[] = $r;

    $dRes2 = sqlsrv_query($conn,
        "SELECT d.DoctorNo, d.Name, d.Position, d.InChargeCareUnitNo
         FROM DOCTOR d JOIN CARE_UNIT cu ON cu.CareUnitNo=d.InChargeCareUnitNo
         WHERE cu.WardName=? ORDER BY d.Position",
        [[$selected,SQLSRV_PARAM_IN]]
    );
    while ($r = sqlsrv_fetch_array($dRes2, SQLSRV_FETCH_ASSOC)) $doctors[] = $r;
}

function nurseNames($list) {
    if (empty($list)) return '<span style="color:var(--muted)">None</span>';
    return implode(', ', array_map(fn($n)=>htmlspecialchars($n['Name']), $list));
}

include 'nav.php';
?>

<div class="page">
  <div class="page-header">
    <h1>Wards</h1>
    <p>Ward overview: care units, nurses, patients and doctors</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="grid-2" style="margin-bottom:24px">
    <!-- Select ward -->
    <div class="card">
      <div class="card-header"><h2>Select Ward to View</h2></div>
      <div class="card-body">
        <form method="POST">
          <div class="field">
            <label>Ward</label>
            <select name="WardName" onchange="this.form.submit()">
              <option value="">— Choose a ward —</option>
              <?php foreach ($wards as $w): ?>
                <option value="<?= $w['WardName'] ?>" <?= $w['WardName']===$selected?'selected':'' ?>>
                  <?= $w['WardName'] ?> — <?= $w['Specialty'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>
    </div>

    <!-- Add ward -->
    <div class="card">
      <div class="card-header"><h2>Add New Ward</h2></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="_form" value="add_ward">
          <div class="form-grid cols-2">
            <div class="field">
              <label>Ward Name *</label>
              <input type="text" name="WardName2" required placeholder="e.g. Ward_I">
            </div>
            <div class="field">
              <label>Specialty</label>
              <input type="text" name="Specialty" placeholder="e.g. Geriatrics">
            </div>
          </div>
          <div class="btn-row">
            <button class="btn btn-teal" type="submit">Add Ward</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if ($wardDetail): ?>
  <!-- ── WARD DETAIL ── -->
  <div class="card" style="margin-bottom:22px">
    <div class="card-header">
      
      <h2><?= $wardDetail['WardName'] ?> &nbsp;<span class="badge badge-teal"><?= $wardDetail['Specialty'] ?></span></h2>
    </div>
    <div class="card-body">
      <div class="section-title" style="margin-top:0">Nursing Staff</div>
      <div class="table-wrap" style="margin-bottom:22px">
        <table class="data">
          <thead><tr><th>Type</th><th>Names</th></tr></thead>
          <tbody>
            <tr><td><span class="badge badge-teal">Day Sister(s)</span></td><td><?= nurseNames($nurses['DaySister'] ?? []) ?></td></tr>
            <tr><td><span class="badge badge-navy">Night Sister(s)</span></td><td><?= nurseNames($nurses['NightSister'] ?? []) ?></td></tr>
            <tr><td><span class="badge badge-amber">Non-Registered</span></td><td><?= nurseNames($nurses['NonReg'] ?? []) ?></td></tr>
          </tbody>
        </table>
      </div>

      <div class="section-title">Doctors in This Ward</div>
      <?php if (empty($doctors)): ?>
        <div class="alert alert-info">No doctors assigned to this ward.</div>
      <?php else: ?>
      <div class="table-wrap" style="margin-bottom:22px">
        <table class="data">
          <thead><tr><th>No</th><th>Name</th><th>Position</th><th>Care Unit</th></tr></thead>
          <tbody>
            <?php foreach ($doctors as $d): ?>
            <tr>
              <td><a href="doctors.php?view=<?= $d['DoctorNo'] ?>"><?= $d['DoctorNo'] ?></a></td>
              <td><?= htmlspecialchars($d['Name']) ?></td>
              <td><?= $d['Position'] ?></td>
              <td><?= $d['InChargeCareUnitNo'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <div class="section-title">Patients</div>
      <?php if (empty($patients)): ?>
        <div class="alert alert-info">No patients in this ward.</div>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>No</th><th>Name</th><th>Bed</th><th>Care Unit</th><th>Admitted</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($patients as $p): ?>
            <tr>
              <td><?= $p['PatientNo'] ?></td>
              <td><?= htmlspecialchars($p['PatientName']) ?></td>
              <td><?= $p['BedNo'] ?></td>
              <td><?= $p['CareUnitNo'] ?></td>
              <td><?= fmtDate($p['DateAdmitted']) ?></td>
              <td><a href="patients.php?view=<?= $p['PatientNo'] ?>" class="btn btn-ghost btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── ALL WARDS SUMMARY ── -->
  <div class="section-title">All Wards Summary</div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Ward</th><th>Specialty</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($wards as $w): ?>
        <tr>
          <td><b><?= $w['WardName'] ?></b></td>
          <td><span class="badge badge-teal"><?= $w['Specialty'] ?></span></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="WardName" value="<?= $w['WardName'] ?>">
              <button class="btn btn-ghost btn-sm" type="submit">View Details</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<footer>Ivor Paine Memorial Hospital &nbsp;·&nbsp; <span>ipmhDB</span></footer>
</body></html>
