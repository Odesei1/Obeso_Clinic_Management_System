<?php
class Staff {
    private $conn;
    private $table = "staff";

    // Constructor receives the PDO database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // ➕ Add new staff
    public function add($fname, $lname, $mid_init = null, $contact = null, $email) {
        $sql = "INSERT INTO {$this->table} 
                (staff_first_name, staff_last_name, staff_middle_init, staff_contact_num, staff_email, staff_created_at)
                VALUES (:fname, :lname, :mid_init, :contact, :email, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":fname" => $fname,
            ":lname" => $lname,
            ":mid_init" => $mid_init,
            ":contact" => $contact,
            ":email" => $email
        ]);
    }

    // ✏️ Update staff details
    public function update($staff_id, $fname, $lname, $mid_init = null, $contact = null, $email) {
        $sql = "UPDATE {$this->table}
                SET staff_first_name = :fname,
                    staff_last_name = :lname,
                    staff_middle_init = :mid_init,
                    staff_contact_num = :contact,
                    staff_email = :email,
                    staff_updated_at = NOW()
                WHERE staff_id = :staff_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":fname" => $fname,
            ":lname" => $lname,
            ":mid_init" => $mid_init,
            ":contact" => $contact,
            ":email" => $email,
            ":staff_id" => $staff_id
        ]);
    }
}
?>
