<?php
// controllers/HomeController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Signup.php';

class HomeController {
    private $db;
    private $signupModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->signupModel = new Signup($this->db);
    }

    public function index() {
        // Check if logged in
        if (!isLoggedIn()) {
            redirect('login');
        }

        // Check if admin trying to access student home
        if (isAdmin()) {
            redirect('admin/home');
        }

        // Get user data
        $user = $this->signupModel->getByIdNumber($_SESSION['IdNumber']);

        include __DIR__ . '/../views/home/index.php';
    }
}
?>
