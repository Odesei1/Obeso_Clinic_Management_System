<?php
class Checkup {
    private $conn;
    private $table = "checkups";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add new checkup (doc_id and doc_fullname can be null)
    public function add(
        $patient_id,
        $checkup_date,
        $doc_id = null,
        $chief_complaint = null,
        $history_present_illness = null,
        $diagnosis = null,
        $blood_pressure = null,
        $respiratory_rate = null,
        $weight = null,
        $heart_rate = null,
        $temperature = null,
        $doc_fullname = null
    ) {
        $sql = "INSERT INTO {$this->table} 
                (patient_id, checkup_date, doc_id, doc_fullname, chief_complaint, history_present_illness, diagnosis,
                 blood_pressure, respiratory_rate, weight, heart_rate, temperature)
                VALUES 
                (:patient_id, :checkup_date, :doc_id, :doc_fullname, :chief_complaint, :history_present_illness, :diagnosis,
                 :blood_pressure, :respiratory_rate, :weight, :heart_rate, :temperature)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->bindValue(':checkup_date', $checkup_date);
        $stmt->bindValue(':doc_id', $doc_id, $doc_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':doc_fullname', $doc_fullname);
        $stmt->bindValue(':chief_complaint', $chief_complaint);
        $stmt->bindValue(':history_present_illness', $history_present_illness);
        $stmt->bindValue(':diagnosis', $diagnosis);
        $stmt->bindValue(':blood_pressure', $blood_pressure);
        $stmt->bindValue(':respiratory_rate', $respiratory_rate);
        $stmt->bindValue(':weight', $weight);
        $stmt->bindValue(':heart_rate', $heart_rate);
        $stmt->bindValue(':temperature', $temperature);

        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    // Update checkup
    public function update(
        $checkup_id,
        $patient_id,
        $checkup_date,
        $doc_id = null,
        $chief_complaint = null,
        $history_present_illness = null,
        $diagnosis = null,
        $blood_pressure = null,
        $respiratory_rate = null,
        $weight = null,
        $heart_rate = null,
        $temperature = null,
        $doc_fullname = null
    ) {
        $sql = "UPDATE {$this->table}
                SET patient_id = :patient_id,
                    checkup_date = :checkup_date,
                    doc_id = :doc_id,
                    doc_fullname = :doc_fullname,
                    chief_complaint = :chief_complaint,
                    history_present_illness = :history_present_illness,
                    diagnosis = :diagnosis,
                    blood_pressure = :blood_pressure,
                    respiratory_rate = :respiratory_rate,
                    weight = :weight,
                    heart_rate = :heart_rate,
                    temperature = :temperature
                WHERE checkup_id = :checkup_id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->bindValue(':checkup_date', $checkup_date);
        $stmt->bindValue(':doc_id', $doc_id, $doc_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':doc_fullname', $doc_fullname);
        $stmt->bindValue(':chief_complaint', $chief_complaint);
        $stmt->bindValue(':history_present_illness', $history_present_illness);
        $stmt->bindValue(':diagnosis', $diagnosis);
        $stmt->bindValue(':blood_pressure', $blood_pressure);
        $stmt->bindValue(':respiratory_rate', $respiratory_rate);
        $stmt->bindValue(':weight', $weight);
        $stmt->bindValue(':heart_rate', $heart_rate);
        $stmt->bindValue(':temperature', $temperature);
        $stmt->bindValue(':checkup_id', $checkup_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Get latest checkup by patient
    public function getByCheckup($patient_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM checkups 
             WHERE patient_id=? 
             ORDER BY checkup_date DESC LIMIT 1"
        );
        $stmt->execute([$patient_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
