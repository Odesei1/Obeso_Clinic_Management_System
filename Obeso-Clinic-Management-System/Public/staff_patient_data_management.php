<?php
session_start();

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

/* ================= DATABASE ================= */
require_once "../config/database.php";
require_once __DIR__ . "/../Class/checkups.php";
require_once __DIR__ . "/../Class/medications.php";
require_once __DIR__ . "/../Class/prescribed_medication.php";

$db = (new Database())->connect();
$checkupObj = new Checkup($db);
$medObj     = new Medication($db);
$presObj    = new PrescribedMedication($db);

/* ================= SAVE ALL (TRANSACTION) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all'])) {
    try {
        $db->beginTransaction();

        // ================= CHECK IF PATIENT EXISTS =================
        $stmt = $db->prepare("SELECT patient_id FROM patients WHERE full_name=? AND birthday=? LIMIT 1");
        $stmt->execute([$_POST['full_name'], $_POST['birthday']]);
        $existingPatient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingPatient) {
            $patient_id = $existingPatient['patient_id']; // Use existing patient
        } else {
            // Insert new patient
            $stmt = $db->prepare(
                "INSERT INTO patients
                (full_name,address,birthday,age,sex,civil_status,religion,occupation,
                 contact_person,contact_person_age,contact_number)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)"
            );

            $stmt->execute([
                $_POST['full_name'],
                $_POST['address'],
                $_POST['birthday'],
                $_POST['age'],
                $_POST['sex'],
                $_POST['civil_status'] ?? null,
                $_POST['religion'] ?? null,
                $_POST['occupation'] ?? null,
                $_POST['contact_person'] ?? null,
                $_POST['contact_person_age'] ?? null,
                $_POST['contact_number']
            ]);

            $patient_id = $db->lastInsertId();
        }

        // ================= ADD CHECKUP =================
        // Use the exists() method in Checkup class to prevent duplicates
        $existingCheckup = $checkupObj->exists(
            $patient_id,
            $_POST['checkup_date'],
            $_POST['doc_id'] ?? null,
            $_POST['diagnosis'] ?? null
        );

        if ($existingCheckup) {
            throw new Exception("Duplicate checkup detected for this patient on the same date with the same doctor and diagnosis.");
        }

        $checkup_id = $checkupObj->add(
            $patient_id,
            $_POST['checkup_date'],
            $_POST['doc_id'] ?? null,
            $_POST['chief_complaint'] ?? null,
            $_POST['history_present_illness'] ?? null,
            $_POST['diagnosis'] ?? null,
            $_POST['blood_pressure'] ?? null,
            $_POST['respiratory_rate'] ?? null,
            $_POST['weight'] ?? null,
            $_POST['heart_rate'] ?? null,
            $_POST['temperature'] ?? null,
            $_POST['doc_fullname'] ?? null
        );

        // ================= ADD MEDICATIONS =================
        if (!empty($_POST['generic_name'])) {
            foreach ($_POST['generic_name'] as $i => $generic) {
                if (trim($generic) === '') continue;

                $medObj->add($generic, $_POST['brand_name'][$i] ?? null);
                $med_id = $db->lastInsertId();

                $presObj->add(
                    $checkup_id,
                    $med_id,
                    $_POST['dose'][$i] ?? null,
                    $_POST['amount'][$i] ?? null,
                    $_POST['frequency'][$i] ?? null,
                    $_POST['duration'][$i] ?? null
                );
            }
        }

        $db->commit();
        header("Location: staff_patient_data_management.php?success=1");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Obeso's Clinic Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>
<link href="../Includes/sidebarStyle.css" rel="stylesheet">

<style>
.section-card { border-radius: 14px; }
.section-header {
    background: #062e6b;
    color: #fff;
    padding: 12px 18px;
    border-radius: 14px 14px 0 0;
}
.sb-sidenav .nav-link.active {
    background-color: #062e6bff !important;
    color: #fff !important;
    font-weight: 600;
}
</style>
</head>

<body class="sb-nav-fixed">

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff.html"; ?>

<div id="layoutSidenav">
<div id="layoutSidenav_nav"><?php include "../Includes/staffSidebar.php"; ?></div>

<div id="layoutSidenav_content">
<main class="container-fluid px-4 py-4">

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">Saved successfully!</div>
<?php endif; ?>

<form method="POST">

<!-- Hidden patient_id for future use -->
<input type="hidden" name="patient_id" id="patient_id">

<!-- ================= PATIENT INFO ================= -->
<div class="card section-card mb-4 shadow-sm">
<div class="section-header">
    <i class="fa-solid fa-user me-2"></i> Patient Information
</div>
<div class="card-body row g-3">
<div class="col-md-6"><input name="full_name" class="form-control" placeholder="Full Name" required></div>
<div class="col-md-3"><input type="date" name="birthday" class="form-control" required></div>
<div class="col-md-3"><input type="number" name="age" class="form-control" placeholder="Age" required></div>

<div class="col-md-3">
<select name="sex" class="form-select" required>
<option value="">Sex</option>
<option>Male</option><option>Female</option><option>Other</option>
</select>
</div>

<div class="col-md-3">
<select name="civil_status" class="form-select">
<option value="">Civil Status</option>
<option>Single</option><option>Married</option><option>Widowed</option><option>Divorced</option>
</select>
</div>

<div class="col-md-3"><input name="contact_number" class="form-control" placeholder="Contact Number" required></div>
<div class="col-md-3"><input name="occupation" class="form-control" placeholder="Occupation"></div>

<div class="col-md-6"><input name="contact_person" class="form-control" placeholder="Contact Person"></div>
<div class="col-md-3"><input type="number" name="contact_person_age" class="form-control" placeholder="Contact Person Age"></div>
<div class="col-md-3"><input name="religion" class="form-control" placeholder="Religion"></div>

<div class="col-12"><textarea name="address" class="form-control" placeholder="Address"></textarea></div>
</div>
</div>

<!-- ================= CHECKUP ================= -->
<div class="card section-card mb-4 shadow-sm">
<div class="section-header">
    <i class="fa-solid fa-stethoscope me-2"></i> Checkup
</div>
<div class="card-body">

<div class="row g-3">
<div class="col-md-4"><input type="date" name="checkup_date" class="form-control" required></div>
<div class="col-md-8"><input name="diagnosis" class="form-control" placeholder="Diagnosis"></div>
</div>

<textarea name="chief_complaint" class="form-control mt-3" placeholder="Chief Complaint"></textarea>
<textarea name="history_present_illness" class="form-control mt-2" placeholder="History of Present Illness"></textarea>

<div class="row g-2 mt-3">
<div class="col"><input name="blood_pressure" class="form-control" placeholder="BP"></div>
<div class="col"><input name="respiratory_rate" class="form-control" placeholder="RR"></div>
<div class="col"><input name="weight" class="form-control" placeholder="WT"></div>
<div class="col"><input name="heart_rate" class="form-control" placeholder="HR"></div>
<div class="col"><input name="temperature" class="form-control" placeholder="TEMP"></div>
</div>

<!-- ================= DOCTOR INFO ================= -->
<div class="row g-3 mt-3">
<div class="col-md-6"><input name="doc_id" class="form-control" placeholder="Doctor ID" required></div>
<div class="col-md-6"><input name="doc_fullname" class="form-control" placeholder="Doctor Full Name" required></div>
</div>

</div>
</div>

<!-- ================= MEDICATIONS ================= -->
<div class="card section-card mb-4 shadow-sm">
<div class="section-header">
    <i class="fa-solid fa-pills me-2"></i> Medications
</div>
<div class="card-body">

<div id="medications"></div>

<button type="button" class="btn btn-outline-secondary mt-2" onclick="addMedication()">
<i class="fa-solid fa-plus"></i> Add Medication
</button>

</div>
</div>

<button type="submit" name="save_all" class="btn btn-primary btn-lg">
<i class="fa-solid fa-floppy-disk"></i> Save Patient Data
</button>

</form>

</main>
<?php include "../Includes/footer.html"; ?>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function addMedication() {
document.getElementById('medications').insertAdjacentHTML('beforeend', `
<div class="row g-2 mb-2">
<div class="col"><input name="generic_name[]" class="form-control" placeholder="Generic"></div>
<div class="col"><input name="brand_name[]" class="form-control" placeholder="Brand"></div>
<div class="col"><input name="dose[]" class="form-control" placeholder="Dose"></div>
<div class="col"><input name="amount[]" class="form-control" placeholder="Amount"></div>
<div class="col"><input name="frequency[]" class="form-control" placeholder="Frequency"></div>
<div class="col"><input name="duration[]" class="form-control" placeholder="Duration"></div>
</div>
`);
}
</script>

</body>
</html>
