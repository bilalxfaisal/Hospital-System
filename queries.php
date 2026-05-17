<?php
$pageTitle = 'Reports';
include 'db.php';

function getQueries() {
    return [
        1  => ['title'=>'Consultants and their doctor teams',
               'desc' =>'Lists each consultant with all doctors in their team.',
               'sql'  =>"SELECT c.DoctorNo AS ConsultantNo, d_c.Name AS ConsultantName, c.Specialty,
                                STRING_AGG(CAST(d.DoctorNo AS VARCHAR)+' '+d.Name, ', ') AS TeamDoctors
                         FROM CONSULTANT c
                         JOIN DOCTOR d_c ON d_c.DoctorNo = c.DoctorNo
                         LEFT JOIN DOCTOR d ON d.ConsultantNo = c.DoctorNo
                         GROUP BY c.DoctorNo, d_c.Name, c.Specialty
                         ORDER BY c.DoctorNo",
               'inputs'=>[]],

        2  => ['title'=>'Wards with sisters, care units and nurse in charge',
               'desc' =>'Ward structure: every care unit, its ward, and the nurse in charge.',
               'sql'  =>"SELECT w.WardName, w.Specialty, cu.CareUnitNo,
                                n.Name AS NurseInCharge, n.Type AS NurseType
                         FROM WARD w
                         JOIN CARE_UNIT cu ON cu.WardName = w.WardName
                         JOIN NURSE n      ON n.StaffNo   = cu.InChargeNurseNo
                         ORDER BY w.WardName, cu.CareUnitNo",
               'inputs'=>[]],

        3  => ['title'=>'Patients, complaints, treatments and dates',
               'desc' =>'All patient treatment records with associated complaints.',
               'sql'  =>"SELECT p.PatientNo, p.PatientName,
                                c.ComplaintCode, c.Description AS Complaint,
                                t.TreatmentCode, t.Description AS Treatment,
                                rt.DateStarted, rt.DateEnded
                         FROM PATIENT p
                         JOIN RECEIVES_TREATMENT rt ON rt.PatientNo    = p.PatientNo
                         JOIN COMPLAINT c            ON c.ComplaintCode = rt.ComplaintCode
                         JOIN TREATMENT t            ON t.TreatmentCode = rt.TreatmentCode
                         ORDER BY p.PatientNo",
               'inputs'=>[]],

        4  => ['title'=>'Junior doctors, their patients and care-unit nurse',
               'desc' =>'Every junior doctor linked to their patients and the care-unit nurse.',
               'sql'  =>"SELECT d.DoctorNo, d.Name AS DoctorName,
                                p.PatientNo, p.PatientName,
                                n.Name AS StaffNurse
                         FROM DOCTOR d
                         JOIN PATIENT   p  ON p.CareUnitNo   = d.InChargeCareUnitNo
                         JOIN CARE_UNIT cu ON cu.CareUnitNo  = p.CareUnitNo
                         JOIN NURSE     n  ON n.StaffNo      = cu.InChargeNurseNo
                         WHERE d.Position = 'Junior Doctor'
                         ORDER BY d.DoctorNo",
               'inputs'=>[]],

        5  => ['title'=>'Consultants with a unique specialty',
               'desc' =>'Only consultants whose specialty is held by no other consultant.',
               'sql'  =>"SELECT c.DoctorNo, d.Name, c.Specialty
                         FROM CONSULTANT c JOIN DOCTOR d ON d.DoctorNo = c.DoctorNo
                         WHERE c.Specialty IN (
                             SELECT Specialty FROM CONSULTANT
                             GROUP BY Specialty HAVING COUNT(*) = 1
                         )",
               'inputs'=>[]],

        6  => ['title'=>'Complaints, treatments and doctor experience history',
               'desc' =>'Cross-references complaint-treatment combos with the in-charge doctor\'s past experience.',
               'sql'  =>"SELECT c.ComplaintCode, c.Description AS Complaint,
                                t.TreatmentCode, t.Description AS Treatment,
                                d.Name AS DoctorName,
                                pe.FromDate, pe.ToDate,
                                pe.Position AS PrevPosition, pe.Establishment
                         FROM RECEIVES_TREATMENT rt
                         JOIN COMPLAINT c  ON c.ComplaintCode   = rt.ComplaintCode
                         JOIN TREATMENT t  ON t.TreatmentCode   = rt.TreatmentCode
                         JOIN PATIENT   p  ON p.PatientNo        = rt.PatientNo
                         JOIN DOCTOR    d  ON d.InChargeCareUnitNo = p.CareUnitNo
                         LEFT JOIN PREV_EXPERIENCE pe ON pe.DoctorNo = d.DoctorNo
                         ORDER BY c.ComplaintCode, t.TreatmentCode",
               'inputs'=>[]],

        7  => ['title'=>'Patients with more than one complaint and their treatments',
               'desc' =>'Patients who have multiple distinct complaints on record.',
               'sql'  =>"SELECT p.PatientNo, p.PatientName,
                                c.Description AS Complaint,
                                t.Description AS Treatment,
                                rt.DateStarted, rt.DateEnded
                         FROM PATIENT p
                         JOIN RECEIVES_TREATMENT rt ON rt.PatientNo    = p.PatientNo
                         JOIN COMPLAINT c            ON c.ComplaintCode = rt.ComplaintCode
                         JOIN TREATMENT t            ON t.TreatmentCode = rt.TreatmentCode
                         WHERE p.PatientNo IN (
                             SELECT PatientNo FROM RECEIVES_TREATMENT
                             GROUP BY PatientNo HAVING COUNT(DISTINCT ComplaintCode) > 1
                         )
                         ORDER BY p.PatientNo",
               'inputs'=>[]],

        8  => ['title'=>'Patients grouped by treatment within complaint',
               'desc' =>'Grouped view: for each complaint+treatment pair, who are the patients.',
               'sql'  =>"SELECT c.Description AS Complaint,
                                t.Description AS Treatment,
                                p.PatientNo, p.PatientName
                         FROM RECEIVES_TREATMENT rt
                         JOIN COMPLAINT c ON c.ComplaintCode = rt.ComplaintCode
                         JOIN TREATMENT t ON t.TreatmentCode = rt.TreatmentCode
                         JOIN PATIENT   p ON p.PatientNo     = rt.PatientNo
                         ORDER BY c.Description, t.Description, p.PatientNo",
               'inputs'=>[]],

        9  => ['title'=>'Performance history for a particular doctor',
               'desc' =>'All performance reviews for one doctor, oldest to newest.',
               'sql'  =>"SELECT d.DoctorNo, d.Name, perf.ReviewDate, perf.Grade
                         FROM PERFORMANCE perf JOIN DOCTOR d ON d.DoctorNo = perf.DoctorNo
                         WHERE perf.DoctorNo = ?
                         ORDER BY perf.ReviewDate",
               'inputs'=>[['name'=>'DoctorNo','label'=>'Doctor No','type'=>'number']]],

        10 => ['title'=>'Full medical details for a particular patient',
               'desc' =>'Complete profile: ward, doctor, complaints, treatments.',
               'sql'  =>"SELECT p.PatientNo, p.PatientName, p.DateOfBirth, p.BedNo, p.DateAdmitted,
                                cu.CareUnitNo, w.WardName,
                                d.Name AS DoctorName, d.Position,
                                c.Description AS Complaint,
                                t.Description AS Treatment,
                                rt.DateStarted, rt.DateEnded
                         FROM PATIENT p
                         JOIN CARE_UNIT cu ON cu.CareUnitNo = p.CareUnitNo
                         JOIN WARD      w  ON w.WardName    = cu.WardName
                         LEFT JOIN DOCTOR d  ON d.InChargeCareUnitNo = p.CareUnitNo
                         LEFT JOIN RECEIVES_TREATMENT rt ON rt.PatientNo    = p.PatientNo
                         LEFT JOIN COMPLAINT c            ON c.ComplaintCode = rt.ComplaintCode
                         LEFT JOIN TREATMENT t            ON t.TreatmentCode = rt.TreatmentCode
                         WHERE p.PatientNo = ?
                         ORDER BY rt.DateStarted",
               'inputs'=>[['name'=>'PatientNo','label'=>'Patient No','type'=>'number']]],

        11 => ['title'=>'Treatments for a complaint between two dates',
               'desc' =>'Filter: which treatments were given for a complaint within a date range.',
               'sql'  =>"SELECT c.ComplaintCode, c.Description AS Complaint,
                                t.TreatmentCode, t.Description AS Treatment,
                                rt.DateStarted, rt.DateEnded
                         FROM RECEIVES_TREATMENT rt
                         JOIN COMPLAINT c ON c.ComplaintCode = rt.ComplaintCode
                         JOIN TREATMENT t ON t.TreatmentCode = rt.TreatmentCode
                         WHERE rt.ComplaintCode = ? AND rt.DateStarted >= ? AND rt.DateStarted <= ?
                         ORDER BY t.Description",
               'inputs'=>[
                   ['name'=>'ComplaintCode','label'=>'Complaint Code','type'=>'number'],
                   ['name'=>'FromDate','label'=>'From Date','type'=>'date'],
                   ['name'=>'ToDate','label'=>'To Date','type'=>'date'],
               ]],

        12 => ['title'=>'Staff positions and count',
               'desc' =>'Summary of how many doctors hold each position.',
               'sql'  =>"SELECT Position, COUNT(*) AS StaffCount
                         FROM DOCTOR GROUP BY Position ORDER BY Position",
               'inputs'=>[]],

        13 => ['title'=>'All ongoing (active) treatments',
               'desc' =>'Every treatment that has not yet ended — DateEnded IS NULL.',
               'sql'  =>"SELECT p.PatientNo, p.PatientName,
                                c.Description AS Complaint, t.Description AS Treatment,
                                rt.DateStarted
                         FROM RECEIVES_TREATMENT rt
                         JOIN PATIENT p   ON p.PatientNo    = rt.PatientNo
                         JOIN COMPLAINT c ON c.ComplaintCode= rt.ComplaintCode
                         JOIN TREATMENT t ON t.TreatmentCode= rt.TreatmentCode
                         WHERE rt.DateEnded IS NULL
                         ORDER BY rt.DateStarted",
               'inputs'=>[]],

        14 => ['title'=>'Doctors with no patients assigned',
               'desc' =>'Doctors whose care unit currently has no admitted patients.',
               'sql'  =>"SELECT d.DoctorNo, d.Name, d.Position
                         FROM DOCTOR d
                         WHERE d.InChargeCareUnitNo NOT IN (
                             SELECT DISTINCT CareUnitNo FROM PATIENT
                         ) OR d.InChargeCareUnitNo IS NULL",
               'inputs'=>[]],

        15 => ['title'=>'Top complaints by patient count',
               'desc' =>'Which complaints are most common across all patients.',
               'sql'  =>"SELECT c.ComplaintCode, c.Description,
                                COUNT(DISTINCT rt.PatientNo) AS Patients
                         FROM COMPLAINT c
                         LEFT JOIN RECEIVES_TREATMENT rt ON rt.ComplaintCode = c.ComplaintCode
                         GROUP BY c.ComplaintCode, c.Description
                         ORDER BY Patients DESC",
               'inputs'=>[]],

        16 => ['title'=>'Doctors with highest performance grades',
               'desc' =>'Average grade-rank per doctor (A+=4, A=3, B+=2.5, B=2, C+=1.5, C=1).',
               'sql'  =>"SELECT d.DoctorNo, d.Name, d.Position,
                                COUNT(p.ReviewDate) AS Reviews,
                                STRING_AGG(p.Grade, ', ') AS Grades
                         FROM DOCTOR d
                         JOIN PERFORMANCE p ON p.DoctorNo = d.DoctorNo
                         GROUP BY d.DoctorNo, d.Name, d.Position
                         ORDER BY d.DoctorNo",
               'inputs'=>[]],
    ];
}

$queries  = getQueries();
$activeQ  = isset($_GET['q']) ? (int)$_GET['q'] : null;
$results  = null;
$columns  = [];
$error    = null;

if ($activeQ && isset($queries[$activeQ])) {
    $query   = $queries[$activeQ];
    $hasIn   = !empty($query['inputs']);
    $submitted = $hasIn && $_SERVER['REQUEST_METHOD'] === 'POST';
    $runNow  = !$hasIn || $submitted;

    if ($runNow) {
        $params = [];
        foreach ($query['inputs'] as $inp) {
            $params[] = [$_POST[$inp['name']] ?? '', SQLSRV_PARAM_IN];
        }
        $res = empty($params)
            ? sqlsrv_query($conn, $query['sql'])
            : sqlsrv_query($conn, $query['sql'], $params);

        if ($res === false) {
            $error = print_r(sqlsrv_errors(), true);
        } else {
            $results = [];
            while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                foreach ($row as $k => $v) {
                    if ($v instanceof DateTime) $row[$k] = $v->format('Y-m-d');
                }
                $results[] = $row;
                if (empty($columns)) $columns = array_keys($row);
            }
        }
    }
}

include 'nav.php';
?>

<div class="page" style="max-width:1500px">
  <div class="page-header">
    <h1>Reports &amp; Queries</h1>
    <p>All 12 project queries plus additional analytical reports</p>
  </div>

  <div style="display:flex;gap:24px;align-items:flex-start">
    <!-- Sidebar -->
    <div style="width:290px;flex-shrink:0">
      <div class="card">
        <div class="card-header"><h2>Queries</h2></div>
        <div style="padding:8px 0">
          <?php foreach ($queries as $num => $q): ?>
          <a href="queries.php?q=<?= $num ?>"
             style="display:block;padding:10px 18px;font-size:13px;color:var(--text);
                    text-decoration:none;border-left:3px solid <?= $activeQ===$num?'var(--teal)':'transparent' ?>;
                    background:<?= $activeQ===$num?'var(--teal-lt)':'transparent' ?>;
                    font-weight:<?= $activeQ===$num?'600':'400' ?>;
                    transition:all .15s">
            <span style="color:var(--muted);font-size:11px;font-weight:700;margin-right:6px"><?= str_pad($num,2,'0',STR_PAD_LEFT) ?></span>
            <?= $q['title'] ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div style="flex:1;min-width:0">
      <?php if (!$activeQ): ?>
      <div class="card">
        <div class="card-body">
          <div class="empty">
            <p>Select a report from the sidebar to view results.</p>
          </div>
        </div>
      </div>

      <?php else:
        $query = $queries[$activeQ];
      ?>
      <div class="card">
        <div class="card-header">
          <h2><?= str_pad($activeQ,2,'0',STR_PAD_LEFT) ?>. <?= $query['title'] ?></h2>
        </div>
        <div class="card-body">
          <?php if (!empty($query['desc'])): ?>
            <div class="alert alert-info" style="margin-bottom:18px"><?= $query['desc'] ?></div>
          <?php endif; ?>

          <?php if (!empty($query['inputs'])): ?>
          <form method="POST" action="queries.php?q=<?= $activeQ ?>">
            <div class="form-grid cols-3" style="margin-bottom:16px">
              <?php foreach ($query['inputs'] as $inp): ?>
              <div class="field">
                <label><?= $inp['label'] ?></label>
                <input type="<?= $inp['type'] ?>" name="<?= $inp['name'] ?>"
                       value="<?= htmlspecialchars($_POST[$inp['name']] ?? '') ?>" required>
              </div>
              <?php endforeach; ?>
            </div>
            <button class="btn btn-primary" type="submit">Run Query</button>
          </form>
          <hr style="border:none;border-top:1px solid var(--border);margin:18px 0">
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="alert alert-error"><b>SQL Error:</b> <?= htmlspecialchars($error) ?></div>

          <?php elseif ($results === null && !empty($query['inputs'])): ?>
            <div class="alert alert-info">Fill in the parameters above and click Run Query.</div>

          <?php elseif (empty($results)): ?>
            <div class="alert alert-warning">No results found for this query.</div>

          <?php else: ?>
            <div style="font-size:12px;color:var(--muted);margin-bottom:10px"><?= count($results) ?> row<?= count($results)!=1?'s':'' ?> returned</div>
            <div class="table-wrap">
              <table class="data">
                <thead>
                  <tr><?php foreach ($columns as $col): ?><th><?= $col ?></th><?php endforeach; ?></tr>
                </thead>
                <tbody>
                  <?php foreach ($results as $row): ?>
                  <tr>
                    <?php foreach ($row as $val): ?>
                      <td><?= htmlspecialchars($val ?? 'NULL') ?></td>
                    <?php endforeach; ?>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<footer>Ivor Paine Memorial Hospital &nbsp;·&nbsp; <span>ipmhDB</span></footer>
</body></html>
