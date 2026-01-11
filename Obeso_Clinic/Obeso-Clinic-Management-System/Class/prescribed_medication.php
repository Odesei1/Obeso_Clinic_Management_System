<?php
class PrescribedMedication {
    private $conn;
    private $table = "prescribed_medications";

    // Constructor receives the PDO database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // ➕ Add a prescribed medication for a checkup
    public function add($checkup_id, $medication_id, $dose = null, $amount = null, $frequency = null, $duration = null) {
        $sql = "INSERT INTO {$this->table} 
                (checkup_id, medication_id, dose, amount, frequency, duration)
                VALUES (:checkup_id, :medication_id, :dose, :amount, :frequency, :duration)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":checkup_id" => $checkup_id,
            ":medication_id" => $medication_id,
            ":dose" => $dose,
            ":amount" => $amount,
            ":frequency" => $frequency,
            ":duration" => $duration
        ]);
    }

public function getLatestByPatient($checkup_id) {
    $stmt = $this->conn->prepare(
        "SELECT pm.*, m.generic_name, m.brand_name
         FROM prescribed_medications pm
         JOIN medications m ON pm.medication_id = m.medication_id
         WHERE pm.checkup_id=?"
    );
    $stmt->execute([$checkup_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    // ✏️ Update prescribed medication details
    public function update($prescription_id, $checkup_id, $medication_id, $dose = null, $amount = null, $frequency = null, $duration = null) {
        $sql = "UPDATE {$this->table}
                SET checkup_id = :checkup_id,
                    medication_id = :medication_id,
                    dose = :dose,
                    amount = :amount,
                    frequency = :frequency,
                    duration = :duration
                WHERE prescription_id = :prescription_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":checkup_id" => $checkup_id,
            ":medication_id" => $medication_id,
            ":dose" => $dose,
            ":amount" => $amount,
            ":frequency" => $frequency,
            ":duration" => $duration,
            ":prescription_id" => $prescription_id
        ]);
    }
}
?>
