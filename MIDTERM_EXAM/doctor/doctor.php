<?php
require_once __DIR__ . "/../config/database.php";

class Doctor{
  private $conn;
  private $table = "doctor";

  public function __construct(){
    $database = new Database();
    $this->conn = $database->getConnection();
  }
  public function addDoctor($data){
    $sql = "INSERT INTO doctor(SPEC_ID, DOC_EMAIL, DOC_LASTNAME, DOC_FIRSTNAME, DOC_PHONE, DOC_AVAILABILITY)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
      $data['specID'],
      $data['email'],
      $data['lastname'],
      $data['firstname'],
      $data['phone'],
      $data['avail']
    ]);
  }
  public function readDoctors(){
    $sql = "SELECT d.DOC_ID,
                   d.DOC_FIRSTNAME,
                   d.DOC_LASTNAME,
                   s.SPEC_NAME
            FROM doctor d
            INNER JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            ORDER BY d.DOC_ID ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt;
  }
  public function updateDoctor($id, $data){
    $sql = "UPDATE doctor
            SET SPEC_ID = ?,
                DOC_EMAIL = ?,
                DOC_LASTNAME = ?,
                DOC_FIRSTNAME = ?,
                DOC_PHONE = ?,
                DOC_AVAILABILITY = ?
            WHERE DOC_ID = ?";

    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
      $data['specID'],
      $data['email'],
      $data['lastname'],
      $data['firstname'],
      $data['phone'],
      $data['avail'],
      $id3
    ]);
  }
  public function deleteDoctor($id){
    $sql = "DELETE FROM doctor WHERE DOC_ID = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt;
  }
  public function searchDoctor($id, $filter = null){
    $sql = "SELECT d.DOC_ID,
                   d.DOC_FIRSTNAME,
                   d.DOC_LASTNAME,
                   s.SPEC_NAME,
                   d.DOC_EMAIL,
                   d.DOC_PHONE,
                   d.DOC_AVAILABILITY,
                   COUNT(a.APPT_ID) AS total_appointments
            FROM doctor d
            INNER JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            LEFT JOIN appointment a ON d.DOC_ID = a.DOC_ID";

    if($filter === 'past'){
      $sql .= " AND a.APPT_DATE < CURDATE()";
    }elseif($filter === 'today'){
      $sql .= " AND DATE(a.APPT_DATE) = CURDATE()";
    }elseif($filter === 'future'){
      $sql .= " AND a.APPT_DATE > CURDATE()";
    }

    $sql .= "WHERE d.DOC_ID = :id
              GROUP BY d.DOC_ID";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    return $stmt;
  }
}
?>
