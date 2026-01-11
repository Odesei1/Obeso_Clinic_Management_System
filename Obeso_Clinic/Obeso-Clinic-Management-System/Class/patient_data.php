<?php
class Patient {
    private $conn;
    private $table = "patients";

    // Constructor receives the PDO database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // ➕ Add new patient
    public function add($full_name, $address, $birthday, $age, $sex, $civil_status = null, $religion = null, $occupation = null, $contact_person = null, $contact_person_age = null, $contact_number = null) {
        $sql = "INSERT INTO {$this->table} 
                (full_name, address, birthday, age, sex, civil_status, religion, occupation, contact_person, contact_person_age, contact_number)
                VALUES (:full_name, :address, :birthday, :age, :sex, :civil_status, :religion, :occupation, :contact_person, :contact_person_age, :contact_number)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":full_name" => $full_name,
            ":address" => $address,
            ":birthday" => $birthday,
            ":age" => $age,
            ":sex" => $sex,
            ":civil_status" => $civil_status,
            ":religion" => $religion,
            ":occupation" => $occupation,
            ":contact_person" => $contact_person,
            ":contact_person_age" => $contact_person_age,
            ":contact_number" => $contact_number
        ]);
    }

    // ✏️ Update patient details
    public function update($patient_id, $full_name, $address, $birthday, $age, $sex, $civil_status = null, $religion = null, $occupation = null, $contact_person = null, $contact_person_age = null, $contact_number = null) {
        $sql = "UPDATE {$this->table}
                SET full_name = :full_name,
                    address = :address,
                    birthday = :birthday,
                    age = :age,
                    sex = :sex,
                    civil_status = :civil_status,
                    religion = :religion,
                    occupation = :occupation,
                    contact_person = :contact_person,
                    contact_person_age = :contact_person_age,
                    contact_number = :contact_number
                WHERE patient_id = :patient_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":full_name" => $full_name,
            ":address" => $address,
            ":birthday" => $birthday,
            ":age" => $age,
            ":sex" => $sex,
            ":civil_status" => $civil_status,
            ":religion" => $religion,
            ":occupation" => $occupation,
            ":contact_person" => $contact_person,
            ":contact_person_age" => $contact_person_age,
            ":contact_number" => $contact_number,
            ":patient_id" => $patient_id
        ]);
    }
}
?>
