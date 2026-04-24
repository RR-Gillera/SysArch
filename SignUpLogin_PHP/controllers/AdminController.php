<?php
// controllers/AdminController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Signup.php';

class AdminController {
    private $db;
    private $signupModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->signupModel = new Signup($this->db);
    }

    public function home() {
        // Check if logged in and is admin
        if (!isLoggedIn() || !isAdmin()) {
            redirect('login');
        }

        // Get all students
        $query = "SELECT * FROM signups WHERE Role = 'Student' ORDER BY CreatedAt DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $students = $stmt->fetchAll();

        include __DIR__ . '/../views/admin/home.php';
    }

    public function analytics() {
        if (!isLoggedIn() || !isAdmin()) {
            redirect('login');
        }

        // Get statistics
        $stats = [];
        
        // Total students
        $query = "SELECT COUNT(*) as count FROM signups WHERE Role = 'Student'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['total_students'] = $stmt->fetch()['count'];

        // Total sit-ins today
        $query = "SELECT COUNT(*) as count FROM sit_in_records WHERE DATE(TimeIn) = CURDATE()";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['sit_ins_today'] = $stmt->fetch()['count'];

        include __DIR__ . '/../views/admin/analytics.php';
    }

    public function announcements() {
        if (!isLoggedIn() || !isAdmin()) {
            redirect('login');
        }

        include __DIR__ . '/../views/admin/announcements.php';
    }

    public function feedback() {
        if (!isLoggedIn() || !isAdmin()) {
            redirect('login');
        }

        // Get all feedback
        $query = "SELECT f.*, s.FirstName, s.LastName FROM feedbacks f 
                  LEFT JOIN signups s ON f.StudentIdNumber = s.IdNumber 
                  ORDER BY f.CreatedAt DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $feedbacks = $stmt->fetchAll();

        include __DIR__ . '/../views/admin/feedback.php';
    }

    public function reservations() {
        if (!isLoggedIn() || !isAdmin()) {
            redirect('login');
        }

        include __DIR__ . '/../views/admin/reservations.php';
    }

    public function students() {
        if (!isLoggedIn() || !isAdmin()) {
            redirect('login');
        }

        // Get all students
        $query = "SELECT * FROM signups WHERE Role = 'Student' ORDER BY LastName, FirstName";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $students = $stmt->fetchAll();

        include __DIR__ . '/../views/admin/students.php';
    }
}
?>
