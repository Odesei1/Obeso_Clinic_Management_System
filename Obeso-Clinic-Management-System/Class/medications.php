<?php
class Medication {
    private $conn;
    private $table = "medications";

    // Constructor receives the PDO database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // ➕ Add new medication
    public function add($generic_name, $brand_name = null) {
        $sql = "INSERT INTO {$this->table} 
                (generic_name, brand_name)
                VALUES (:generic_name, :brand_name)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":generic_name" => $generic_name,
            ":brand_name" => $brand_name
        ]);
    }

    // ✏️ Update medication details
    public function update($medication_id, $generic_name, $brand_name = null) {
        $sql = "UPDATE {$this->table}
                SET generic_name = :generic_name,
                    brand_name = :brand_name
                WHERE medication_id = :medication_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":generic_name" => $generic_name,
            ":brand_name" => $brand_name,
            ":medication_id" => $medication_id
        ]);
    }
}
?>
