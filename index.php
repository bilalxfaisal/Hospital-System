<?php
$pageTitle = 'Dashboard';
include 'db.php';
include 'nav.php';

// Counts
$counts = [];
foreach ([
    'patients'   => 'SELECT COUNT(*) FROM PATIENT',
    'doctors'    => 'SELECT COUNT(*) FROM DOCTOR',
    'consultants'=> 'SELECT COUNT(*) FROM CONSULTANT',
    'wards'      => 'SELECT COUNT(*) FROM WARD',
    'nurses'     => 'SELECT COUNT(*) FROM NURSE',
    'complaints' => 'SELECT COUNT(*) FROM COMPLAINT',
    'treatments' => 'SELECT COUNT(*) FROM TREATMENT',
    'active_tx'  => 'SELECT COUNT(*) FROM RECEIVES_TREATMENT WHERE DateEnded IS NULL',
] as $k => $sql) {
    $r = sqlsrv_query($conn, $sql);
    $row = sqlsrv_fetch_array($r);
    $counts[$k] = $row[0];
}

// Recent patients
$recentRes = sqlsrv_query($conn,
    "SELECT TOP 5 p.PatientNo, p.PatientName, p.DateAdmitted, w.WardName
     FROM PATIENT p
     JOIN CARE_UNIT cu ON cu.CareUnitNo = p.CareUnitNo
     JOIN WARD w ON w.WardName = cu.WardName
     ORDER BY p.DateAdmitted DESC"
);
$recent = [];
while ($row = sqlsrv_fetch_array($recentRes, SQLSRV_FETCH_ASSOC)) $recent[] = $row;

// Active treatments
$activeTxRes = sqlsrv_query($conn,
    "SELECT TOP 5 p.PatientName, c.Description AS Complaint, t.Description AS Treatment, rt.DateStarted
     FROM RECEIVES_TREATMENT rt
     JOIN PATIENT p    ON p.PatientNo    = rt.PatientNo
     JOIN COMPLAINT c  ON c.ComplaintCode= rt.ComplaintCode
     JOIN TREATMENT t  ON t.TreatmentCode= rt.TreatmentCode
     WHERE rt.DateEnded IS NULL
     ORDER BY rt.DateStarted DESC"
);
$activeTx = [];
while ($row = sqlsrv_fetch_array($activeTxRes, SQLSRV_FETCH_ASSOC)) $activeTx[] = $row;
?>

<div class="page">
  <div class="page-header">
    <h1>Dashboard</h1>
    <p>Hospital-wide overview — Ivor Paine Memorial Hospital</p>
  </div>

  <div class="grid-4" style="margin-bottom:24px">
    <div class="stat-box teal">
      <div class="label">Patients</div>
      <div class="value"><?= $counts['patients'] ?></div>
      <div class="sub">Admitted</div>
    </div>
    <div class="stat-box navy">
      <div class="label">Doctors</div>
      <div class="value"><?= $counts['doctors'] ?></div>
      <div class="sub"><?= $counts['consultants'] ?> consultants</div>
    </div>
    <div class="stat-box green">
      <div class="label">Nurses</div>
      <div class="value"><?= $counts['nurses'] ?></div>
      <div class="sub">Across <?= $counts['wards'] ?> wards</div>
    </div>
    <div class="stat-box amber">
      <div class="label">Active Treatments</div>
      <div class="value"><?= $counts['active_tx'] ?></div>
      <div class="sub">Ongoing</div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-header">
        <h2>Recently Admitted Patients</h2>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>No</th><th>Name</th><th>Ward</th><th>Date Admitted</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
              <td><a href="patients.php?view=<?= $r['PatientNo'] ?>"><?= $r['PatientNo'] ?></a></td>
              <td><?= htmlspecialchars($r['PatientName']) ?></td>
              <td><span class="badge badge-teal"><?= $r['WardName'] ?></span></td>
              <td><?= fmtDate($r['DateAdmitted']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2>Active Ongoing Treatments</h2>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Patient</th><th>Complaint</th><th>Treatment</th><th>Since</th></tr></thead>
          <tbody>
            <?php foreach ($activeTx as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['PatientName']) ?></td>
              <td><?= htmlspecialchars($r['Complaint']) ?></td>
              <td><?= htmlspecialchars($r['Treatment']) ?></td>
              <td><?= fmtDate($r['DateStarted']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div style="margin-top:22px" class="grid-3">
    <div class="card">
      <div class="card-header"><h2>Quick Actions</h2></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
        <a href="patients.php?action=add"          class="btn btn-teal">Add New Patient</a>
        <a href="doctors.php?action=add"           class="btn btn-primary">Add New Doctor</a>
        <a href="complaints.php?action=assign"     class="btn btn-ghost">Assign Complaint to Patient</a>
        <a href="consultants.php?action=add_member" class="btn btn-ghost">Add Doctor to Consultant Team</a>
        <a href="queries.php"                      class="btn btn-ghost">View All Reports</a>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2>Hospital Stats</h2></div>
      <div class="card-body">
        <table class="data">
          <tbody>
            <tr><td>Wards</td><td><b><?= $counts['wards'] ?></b></td></tr>
            <tr><td>Care Units</td><td><b>15</b></td></tr>
            <tr><td>Complaints Registered</td><td><b><?= $counts['complaints'] ?></b></td></tr>
            <tr><td>Treatments Available</td><td><b><?= $counts['treatments'] ?></b></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2>Consultant Teams</h2></div>
      <?php
      $ctRes = sqlsrv_query($conn,
        "SELECT c.DoctorNo, d.Name, c.Specialty,
                COUNT(tm.DoctorNo) AS Members
         FROM CONSULTANT c
         JOIN DOCTOR d ON d.DoctorNo = c.DoctorNo
         LEFT JOIN DOCTOR tm ON tm.ConsultantNo = c.DoctorNo
         GROUP BY c.DoctorNo, d.Name, c.Specialty"
      );
      ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Consultant</th><th>Specialty</th><th>Team</th></tr></thead>
          <tbody>
            <?php while ($r = sqlsrv_fetch_array($ctRes, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
              <td><?= htmlspecialchars($r['Name']) ?></td>
              <td><span class="badge badge-teal"><?= $r['Specialty'] ?></span></td>
              <td><?= $r['Members'] ?> doctors</td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<footer>
  Ivor Paine Memorial Hospital &nbsp;·&nbsp; Hospital Management System &nbsp;·&nbsp;
  <span>ipmhDB</span>
</footer>
</body>
</html>
