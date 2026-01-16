<?php
class Followups {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Create a new follow-up
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO follow_ups (patient_id, doc_id, checkup_id, followup_date, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['patient_id'],
            $data['doc_id'],
            $data['checkup_id'] ?? null,
            $data['followup_date'],
            $data['notes'],
            $data['status'] ?? 'Pending'
        ]);
    }

    // Get all follow-ups with patient and doctor details
    public function getAll() {
        $stmt = $this->db->query("
            SELECT f.*, p.full_name AS patient_name, d.doc_fullname AS doctor_name, c.checkup_date AS related_checkup_date
            FROM follow_ups f
            INNER JOIN patients p ON f.patient_id = p.patient_id
            INNER JOIN doctors d ON f.doc_id = d.doc_id
            LEFT JOIN checkups c ON f.checkup_id = c.checkup_id
            ORDER BY f.followup_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get follow-ups by patient ID
    public function getByPatient($patient_id) {
        $stmt = $this->db->prepare("
            SELECT f.*, p.full_name AS patient_name, d.doc_fullname AS doctor_name, c.checkup_date AS related_checkup_date
            FROM follow_ups f
            INNER JOIN patients p ON f.patient_id = p.patient_id
            INNER JOIN doctors d ON f.doc_id = d.doc_id
            LEFT JOIN checkups c ON f.checkup_id = c.checkup_id
            WHERE f.patient_id = ?
            ORDER BY f.followup_date DESC
        ");
        $stmt->execute([$patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single follow-up by ID
    public function getById($followup_id) {
        $stmt = $this->db->prepare("
            SELECT f.*, p.full_name AS patient_name, d.doc_fullname AS doctor_name, c.checkup_date AS related_checkup_date
            FROM follow_ups f
            INNER JOIN patients p ON f.patient_id = p.patient_id
            INNER JOIN doctors d ON f.doc_id = d.doc_id
            LEFT JOIN checkups c ON f.checkup_id = c.checkup_id
            WHERE f.followup_id = ?
        ");
        $stmt->execute([$followup_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update follow-up status or details
    public function update($followup_id, $data) {
        $stmt = $this->db->prepare("
            UPDATE follow_ups 
            SET followup_date = ?, notes = ?, status = ? 
            WHERE followup_id = ?
        ");
        return $stmt->execute([
            $data['followup_date'],
            $data['notes'],
            $data['status'],
            $followup_id
        ]);
    }

    // Delete a follow-up
    public function delete($followup_id) {
        $stmt = $this->db->prepare("DELETE FROM follow_ups WHERE followup_id = ?");
        return $stmt->execute([$followup_id]);
    }

    // Mark as completed
    public function markCompleted($followup_id) {
        $stmt = $this->db->prepare("UPDATE follow_ups SET status = 'Completed' WHERE followup_id = ?");
        return $stmt->execute([$followup_id]);
    }
}
?>