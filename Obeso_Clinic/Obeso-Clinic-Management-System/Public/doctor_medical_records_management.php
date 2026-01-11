<?php
session_start();

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: access_denied.php");
    exit();
}

/* ================= DATABASE ================= */
require_once "../config/database.php";
$db = (new Database())->connect();

/* ================= SEARCH PATIENT ================= */
$search = $_GET['search'] ?? '';
$limit  = 9;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ================= FETCH PATIENTS ================= */
if ($search) {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE full_name LIKE :search");
    $countStmt->execute([':search' => "%$search%"]);
    $totalPatients = $countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM patients WHERE full_name LIKE :search ORDER BY full_name LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
} else {
    $totalPatients = $db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $stmt = $db->prepare("SELECT * FROM patients ORDER BY full_name LIMIT :limit OFFSET :offset");
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = max(1, ceil($totalPatients / $limit));

/* ================= FETCH PATIENT RECORD ================= */
$patient = null;
$checkups = [];

if (isset($_GET['patient_id'])) {
    $pid = (int)$_GET['patient_id'];

    // Fetch patient info
    $pstmt = $db->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $pstmt->execute([$pid]);
    $patient = $pstmt->fetch(PDO::FETCH_ASSOC);

    // Fetch checkups
    $cstmt = $db->prepare("SELECT * FROM checkups WHERE patient_id = ? ORDER BY checkup_date DESC");
    $cstmt->execute([$pid]);
    $checkups = $cstmt->fetchAll(PDO::FETCH_ASSOC);

    // For each checkup, fetch prescribed medications
    foreach ($checkups as $i => $c) {
        $mstmt = $db->prepare("
            SELECT pm.*, m.generic_name, m.brand_name
            FROM prescribed_medications pm
            INNER JOIN medications m ON pm.medication_id = m.medication_id
            WHERE pm.checkup_id = ?
        ");
        $mstmt->execute([$c['checkup_id']]);
        $checkups[$i]['medications'] = $mstmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Obeso's Clinic Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>
<link href="../Includes/sidebarStyle.css" rel="stylesheet">
<style>
.section-header { background:#062e6b; color:#fff; padding:12px 18px; border-radius:14px 14px 0 0; }
.folder-card { transition:.2s; } .folder-card:hover { transform: translateY(-4px); }
.sb-sidenav .nav-link.active { background-color: #062e6bff !important; color: #fff !important; font-weight: 600; }
</style>
</head>
<body class="sb-nav-fixed">

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_doctor.html"; ?>

<div id="layoutSidenav">
<div id="layoutSidenav_nav"><?php include "../Includes/doctorSidebar.php"; ?></div>
<div id="layoutSidenav_content">
<main class="container-fluid px-4 py-4">

<form class="row g-2 mb-4">
<div class="col-md-4">
<input type="text" name="search" class="form-control" placeholder="Search patient..." autocomplete="off" value="<?= htmlspecialchars($search) ?>">
</div>
<div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa fa-search"></i> Search</button></div>
</form>

<?php if (!$patient): ?>
<!-- ================= PATIENT FOLDERS ================= -->
<div class="row g-4">
<?php foreach ($patients as $p): ?>
<div class="col-md-4">
<div class="card shadow folder-card">
<div class="section-header"><i class="fa fa-folder me-2"></i><?= htmlspecialchars($p['full_name']) ?></div>
<div class="card-body">
<p><strong>Sex:</strong> <?= $p['sex'] ?><br><strong>Age:</strong> <?= $p['age'] ?><br><strong>Contact:</strong> <?= $p['contact_number'] ?></p>
<a href="?patient_id=<?= $p['patient_id'] ?>" class="btn btn-outline-primary w-100"><i class="fa fa-folder-open"></i> Open Records</a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- ================= PAGINATION ================= -->
<nav class="mt-4"><ul class="pagination justify-content-center">
<li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a></li>
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
<li class="page-item <?= ($i == $page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a></li>
<?php endfor; ?>
<li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a></li>
</ul></nav>

<?php else: ?>
<!-- ================= PATIENT RECORD ================= -->
<a href="doctor_medical_records_management.php" class="btn btn-secondary mb-3"><i class="fa fa-arrow-left"></i> Back</a>

<div class="card shadow mb-4">
<div class="section-header"><i class="fa fa-user me-2"></i> Patient Information</div>
<div class="card-body">
<div class="row">
<div class="col-md-4"><strong>Name:</strong> <?= htmlspecialchars($patient['full_name']) ?></div>
<div class="col-md-2"><strong>Age:</strong> <?= $patient['age'] ?></div>
<div class="col-md-2"><strong>Sex:</strong> <?= $patient['sex'] ?></div>
<div class="col-md-4"><strong>Contact:</strong> <?= $patient['contact_number'] ?></div>
</div>
<div class="mt-2"><strong>Address:</strong> <?= htmlspecialchars($patient['address']) ?></div>
</div>
</div>

<?php foreach ($checkups as $c): ?>
<div class="card shadow mb-4">
<div class="section-header"><i class="fa fa-stethoscope me-2"></i> Checkup — <?= $c['checkup_date'] ?> (Doctor: <?= htmlspecialchars($c['doc_fullname']) ?>)</div>
<div class="card-body">
<p><strong>Diagnosis:</strong> <?= htmlspecialchars($c['diagnosis']) ?></p>
<p><strong>Chief Complaint:</strong> <?= htmlspecialchars($c['chief_complaint']) ?></p>
<p><strong>HPI:</strong> <?= htmlspecialchars($c['history_present_illness']) ?></p>

<hr>
<div class="row text-center">
<div class="col">BP<br><strong><?= $c['blood_pressure'] ?></strong></div>
<div class="col">RR<br><strong><?= $c['respiratory_rate'] ?></strong></div>
<div class="col">WT<br><strong><?= $c['weight'] ?></strong></div>
<div class="col">HR<br><strong><?= $c['heart_rate'] ?></strong></div>
<div class="col">TEMP<br><strong><?= $c['temperature'] ?></strong></div>
</div>

<?php if (!empty($c['medications'])): ?>
<hr>
<h5>Medications:</h5>
<table class="table table-bordered mt-2">
<thead><tr><th>Generic</th><th>Brand</th><th>Dose</th><th>Amount</th><th>Frequency</th><th>Duration</th></tr></thead>
<tbody>
<?php foreach ($c['medications'] as $m): ?>
<tr>
<td><?= htmlspecialchars($m['generic_name']) ?></td>
<td><?= htmlspecialchars($m['brand_name']) ?></td>
<td><?= htmlspecialchars($m['dose']) ?></td>
<td><?= htmlspecialchars($m['amount']) ?></td>
<td><?= htmlspecialchars($m['frequency']) ?></td>
<td><?= htmlspecialchars($m['duration']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

</div>
</div>
<?php endforeach; ?>

<?php endif; ?>

</main>
<?php include "../Includes/footer.html"; ?>
</div>
</div>
</body>
</html>
