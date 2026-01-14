<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

require_once "../Config/database.php";
$database = new Database();
$conn = $database->connect();

// Fetch data as in your original code
// For brevity, I will assume the same data fetch queries you provided

// Fetch total patients
$stmt = $conn->prepare("SELECT COUNT(*) AS total_patients FROM patients");
$stmt->execute();
$totalPatients = $stmt->fetch(PDO::FETCH_ASSOC)['total_patients'];

// Fetch today's appointments
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT c.checkup_id, p.full_name AS patient_name, d.doc_first_name, d.doc_last_name, c.checkup_date
    FROM checkups c
    JOIN patients p ON c.patient_id = p.patient_id
    JOIN doctors d ON c.doc_id = d.doc_id
    WHERE c.checkup_date = ?
    ORDER BY c.checkup_date ASC
");
$stmt->execute([$today]);
$appointmentsToday = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch doctors on duty (distinct count)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT doc_id) AS doctors_on_duty 
    FROM checkups 
    WHERE checkup_date = ?
");
$stmt->execute([$today]);
$doctorsOnDuty = $stmt->fetch(PDO::FETCH_ASSOC)['doctors_on_duty'];

// Fetch recent patient info (latest 5)
$stmt = $conn->prepare("
    SELECT p.patient_id, p.full_name, p.sex, p.age, d.doc_first_name, d.doc_last_name
    FROM patients p
    LEFT JOIN checkups c ON p.patient_id = c.patient_id
    LEFT JOIN doctors d ON c.doc_id = d.doc_id
    ORDER BY p.patient_id DESC
    LIMIT 5
");
$stmt->execute();
$recentPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch one sample medical record
$stmt = $conn->prepare("
    SELECT p.full_name, p.age, p.sex, c.diagnosis
    FROM patients p
    JOIN checkups c ON p.patient_id = c.patient_id
    ORDER BY c.checkup_date DESC
    LIMIT 1
");
$stmt->execute();
$medicalRecord = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Clinic Staff Dashboard - Obeso's Clinic</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
<style>
  body, html {
    height: 100%;
    background-color: #f8fafc; /* light blue/gray */
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  /* Sidebar */
  .sidebar {
    width: 260px;
    background: #2c3e50;
    color: white;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }
  .sidebar .nav-link {
    color: #cbd5e1;
    font-weight: 600;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .sidebar .nav-link:hover,
  .sidebar .nav-link.active {
    background-color: #1f2937;
    color: white;
  }
  .sidebar .nav-link i {
    font-size: 1.2rem;
  }
  .sidebar-header {
    font-size: 1.4rem;
    font-weight: 700;
    padding: 20px;
    border-bottom: 1px solid #374151;
  }
  /* Top navbar */
  .topbar {
    height: 60px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 20px;
    gap: 15px;
  }
  .topbar .icon-btn {
    position: relative;
    font-size: 1.25rem;
    color: #4b5563;
    cursor: pointer;
    background: none;
    border: none;
  }
  .topbar .icon-btn .badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background: #ef4444;
    color: white;
    font-size: 0.6rem;
    padding: 2px 5px;
    border-radius: 50%;
  }
  .topbar .user-menu {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .topbar .user-menu img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
  }
  /* Main content */
  .content-wrapper {
    flex-grow: 1;
    padding: 25px 35px;
    overflow-y: auto;
  }
  /* Patient Detail Panel */
  .patient-info {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.05);
  }
  .patient-photo {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
  }
  .patient-name {
    font-size: 1.35rem;
    font-weight: 700;
  }
  .patient-contact {
    font-size: 0.9rem;
    color: #6b7280;
  }
  .info-row {
    display: flex;
    justify-content: space-between;
    margin: 8px 0;
    font-size: 0.9rem;
    color: #374151;
  }
  .info-label {
    font-weight: 600;
    color: #6b7280;
  }
  .section-title {
    margin-top: 25px;
    margin-bottom: 12px;
    font-weight: 700;
    font-size: 1rem;
    color: #111827;
  }
  /* Tabs */
  .nav-tabs .nav-link {
    font-weight: 600;
    color: #374151;
  }
  .nav-tabs .nav-link.active {
    color: #2563eb;
    border-color: #2563eb #2563eb white;
  }
  .tab-content {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.05);
    margin-top: 15px;
  }
  /* Buttons */
  .btn-admit {
    background-color: #10b981;
    color: white;
  }
  .btn-edit {
    background-color: #3b82f6;
    color: white;
  }
  .btn-discharge {
    background-color: #ef4444;
    color: white;
  }
  .btn-admit:hover {
    background-color: #059669;
  }
  .btn-edit:hover {
    background-color: #2563eb;
  }
  .btn-discharge:hover {
    background-color: #dc2626;
  }
  /* Table styles */
  table {
    width: 100%;
    border-collapse: collapse;
  }
  table thead tr {
    background-color: #e0e7ff;
  }
  table th, table td {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    font-size: 0.9rem;
  }
  /* Dropdown inside table */
  select {
    border: none;
    background: transparent;
    font-size: 0.9rem;
    padding: 2px 5px;
  }
</style>
</head>
<body>

<div class="d-flex">
  <!-- Sidebar -->
  <nav class="sidebar d-flex flex-column">
    <div class="sidebar-header text-white text-center">  
      <i class="bi bi-shield-lock-fill"></i> Obeso's Clinic
    </div>
    <a href="#" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="#" class="nav-link"><i class="bi bi-people-fill"></i> Patients</a>
    <a href="#" class="nav-link"><i class="bi bi-calendar-check-fill"></i> Appointments</a>
    <a href="#" class="nav-link"><i class="bi bi-person-badge-fill"></i> Doctors</a>
    <a href="#" class="nav-link"><i class="bi bi-receipt-cutoff"></i> Billing</a>
    <a href="#" class="nav-link"><i class="bi bi-bar-chart-fill"></i> Reports</a>
    <a href="#" class="nav-link"><i class="bi bi-gear-fill"></i> Settings</a>
    <a href="logout.php" class="nav-link mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>

  <!-- Main content -->
  <div class="content-wrapper d-flex flex-column flex-grow-1">

    <!-- Topbar -->
    <header class="topbar">
      <button class="icon-btn" aria-label="Search">
        <i class="bi bi-search"></i>
      </button>
      <button class="icon-btn position-relative" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <span class="badge">3</span>
      </button>
      <div class="user-menu">
        <span>Clinic Staff</span>
        <img src="https://i.pravatar.cc/40?img=5" alt="User Profile" />
      </div>
    </header>

    <!-- Dashboard Header -->
    <main class="flex-grow-1 d-flex gap-4 mt-4">
      <!-- Left Patient Info -->
      <section class="patient-info flex-shrink-0" style="width: 300px;">
        <img src="https://i.pravatar.cc/90?img=5" alt="Patient photo" class="patient-photo" />
        <div class="patient-name">Ms. Leyla Dixon</div>
        <div class="patient-contact mb-3">
          <i class="bi bi-telephone-fill"></i> 13808675<br />
          <i class="bi bi-phone-fill"></i> 09109795156
        </div>
        <div class="info-row"><div class="info-label">Patient ID:</div><div>138086075</div></div>
        <div class="info-row"><div class="info-label">Contact:</div><div>09109795156</div></div>
        <div class="info-row"><div class="info-label">Birthday:</div><div>July 31, 1971</div></div>
        <div class="info-row"><div class="info-label">Age:</div><div>53</div></div>
        <div class="info-row"><div class="info-label">Sex:</div><div>Female</div></div>
        <div class="info-row"><div class="info-label">Status:</div><div>Admitted</div></div>
        <div class="info-row"><div class="info-label">Religion/Occupation:</div><div>&nbsp;</div></div>
        <div class="info-row"><div class="info-label">Date Momacion:</div><div>Oct 30, 2023</div></div>

        <div class="section-title">Medications</div>
        <table>
          <thead>
            <tr><th>Generic Name</th><th>Brand Name</th></tr>
          </thead>
          <tbody>
            <tr>
              <td>Cefuroxime</td>
              <td>0.5 g</td>
            </tr>
            <tr>
              <td>Levofloxacin (Amopa)</td>
              <td>Amorpa</td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- Right Patient Tabs -->
      <section class="flex-grow-1 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4>Patient Details - Ms. Leyla Dixon</h4>
          <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm btn-admit">Admit Patient</button>
            <button class="btn btn-primary btn-sm btn-edit">Edit</button>
            <button class="btn btn-danger btn-sm btn-discharge">Discharge Patient</button>
          </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="patientTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="false">General Information</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="diagnosis-tab" data-bs-toggle="tab" data-bs-target="#diagnosis" type="button" role="tab" aria-controls="diagnosis" aria-selected="true">Diagnosis</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="vital-tab" data-bs-toggle="tab" data-bs-target="#vital" type="button" role="tab" aria-controls="vital" aria-selected="false">Vital Signs</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="medications-tab" data-bs-toggle="tab" data-bs-target="#medications" type="button" role="tab" aria-controls="medications" aria-selected="false">Medications</button>
          </li>
        </ul>
        <div class="tab-content flex-grow-1" id="patientTabContent">
          <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
            <p><strong>General Information content here...</strong></p>
          </div>
          <div class="tab-pane fade show active" id="diagnosis" role="tabpanel" aria-labelledby="diagnosis-tab">
            <div class="mb-3 d-flex justify-content-between">
              <div><strong>Oct: 30, 2023</strong></div>
              <div>
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Print</button>
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i></button>
              </div>
            </div>

            <table>
              <tbody>
                <tr><td><strong>Diagnosis</strong></td><td></td></tr>
                <tr><td>CC:</td><td>Fever, 2 days...</td></tr>
                <tr><td>HPI:</td><td>undocumented CBS</td></tr>
                <tr><td>Past Medical History:</td><td></td></tr>
                <tr><td>Doctor:</td><td>Dr. S. Miller</td></tr>
                <tr><td>BP:</td><td>110 / 80 mmHg</td></tr>
                <tr><td>RR:</td><td>21</td></tr>
                <tr><td>WT:</td><td>50.0 80 kg</td></tr>
                <tr><td>Notes:</td><td>s/o granulocytopenia plan Observation<br />TEMP: 38.1°C</td></tr>
              </tbody>
            </table>

            <div class="section-title mt-4">Medications</div>
            <table>
              <thead>
                <tr><th>Generic Name</th><th>Brand Name</th><th>Dose</th><th>Frequency</th><th>Route</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>Cefuroxime</td>
                  <td>0.5 g</td>
                  <td>PO</td>
                  <td>BID</td>
                  <td>PO</td>
                </tr>
                <tr>
                  <td>Levofloxacin</td>
                  <td>0.3 g</td>
                  <td>IV</td>
                  <td>Frequency</td>
                  <td>Antipyra</td>
                </tr>
              </tbody>
            </table>
            <div class="mt-3 d-flex justify-content-end gap-2">
              <button class="btn btn-outline-secondary btn-sm">Cancel</button>
              <button class="btn btn-primary btn-sm">Save</button>
            </div>
          </div>
          <div class="tab-pane fade" id="vital" role="tabpanel" aria-labelledby="vital-tab">
            <p><strong>Vital Signs content here...</strong></p>
          </div>
          <div class="tab-pane fade" id="medications" role="tabpanel" aria-labelledby="medications-tab">
            <p><strong>Medications content here...</strong></p>
          </div>
        </div>
      </section>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
