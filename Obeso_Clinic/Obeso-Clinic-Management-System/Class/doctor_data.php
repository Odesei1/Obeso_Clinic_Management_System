<?php
class Doctor {
    private $conn;
    private $table = "doctors";

    // Constructor receives the PDO database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // ➕ Add new doctor
    public function add($fname, $lname, $mid_init = null, $spec_id = null, $contact = null, $email) {

        // Combine full name like: First M. Last
        $fullname = trim($fname . ' ' . ($mid_init ? $mid_init . '. ' : '') . $lname);

        $sql = "INSERT INTO {$this->table}
                (doc_fullname, spec_id, doc_contact_num, doc_email, doc_created_at)
                VALUES (:fullname, :spec_id, :contact, :email, NOW())";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":fullname" => $fullname,
            ":spec_id"  => $spec_id,
            ":contact"  => $contact,
            ":email"    => $email
        ]);
    }

    // ✏️ Update doctor details
    public function update($doc_id, $fname, $lname, $mid_init = null, $spec_id = null, $contact = null, $email) {

        $fullname = trim($fname . ' ' . ($mid_init ? $mid_init . '. ' : '') . $lname);

        $sql = "UPDATE {$this->table}
                SET doc_fullname = :fullname,
                    spec_id = :spec_id,
                    doc_contact_num = :contact,
                    doc_email = :email,
                    doc_updated_at = NOW()
                WHERE doc_id = :doc_id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":fullname" => $fullname,
            ":spec_id"  => $spec_id,
            ":contact"  => $contact,
            ":email"    => $email,
            ":doc_id"   => $doc_id
        ]);
    }
}
?>
