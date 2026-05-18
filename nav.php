<?php
$current = basename($_SERVER['PHP_SELF']);
function navActive($file) {
    global $current;
    return $current === $file ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IPMH — <?= $pageTitle ?? 'Hospital System' ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="topnav">
  <div class="brand">IVOR PAINE <span>MEMORIAL</span> HOSPITAL</div>
  <a href="index.php"              class="<?= navActive('index.php') ?>">Dashboard</a>
  <a href="patients.php"           class="<?= navActive('patients.php') ?>">Patients</a>
  <a href="doctors.php"            class="<?= navActive('doctors.php') ?>">Doctors</a>
  <a href="consultants.php"        class="<?= navActive('consultants.php') ?>">Consultants</a>
  <a href="wards.php"              class="<?= navActive('wards.php') ?>">Wards</a>
  <a href="complaints.php"         class="<?= navActive('complaints.php') ?>">Complaints</a>
  <a href="treatments.php"         class="<?= navActive('treatments.php') ?>">Treatments</a>
  <a href="queries.php"            class="<?= navActive('queries.php') ?>">Reports</a>
</nav>
