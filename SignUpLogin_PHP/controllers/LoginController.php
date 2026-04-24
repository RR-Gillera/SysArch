<?php
// controllers/LoginController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Signup.php';

class LoginController {
    private $db;
    private $signupModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->signupModel = new Signup($this->db);
    }

    public function index() {
        // Check if already logged in
        if (isLoggedIn()) {
            if (isAdmin()) {
                redirect('admin/home');
            } else {
                redirect('home');
            }
        }

        // Get remembered ID from cookie
        $rememberedIdNumber = '';
        if (isset($_COOKIE['RememberedIdNumber'])) {
            $rememberedIdNumber = $_COOKIE['RememberedIdNumber'];
        }

        include __DIR__ . '/../views/login/login.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idNumber = isset($_POST['IdNumber']) ? trim($_POST['IdNumber']) : '';
            $password = isset($_POST['Password']) ? $_POST['Password'] : '';
            $rememberMe = isset($_POST['RememberMe']);

            $errors = [];

            // Validation
            if (empty($idNumber)) {
                $errors[] = "ID Number is required.";
            }
            if (empty($password)) {
                $errors[] = "Password is required.";
            }

            if (empty($errors)) {
                // Try by IdNumber first (students), then by FirstName for admins
                $user = $this->signupModel->getByIdNumber($idNumber);
                
                if (!$user) {
                    $user = $this->signupModel->getByNameAndRole($idNumber, 'Admin');
                }

                if (!$user || !password_verify($password, $user['Password'])) {
                    $errors[] = "Invalid ID Number/Username or Password.";
                } else {
                    // Set session variables
                    $_SESSION['UserName'] = $user['FirstName'] . ' ' . $user['LastName'];
                    $_SESSION['IdNumber'] = $user['IdNumber'];
                    $_SESSION['Course'] = $user['Course'];
                    $_SESSION['CourseLevel'] = $user['CourseLevel'];
                    $_SESSION['Email'] = $user['Email'];
                    $_SESSION['Role'] = $user['Role'];
                    $_SESSION['ProfileImagePath'] = $user['ProfileImagePath'] ?? '';

                    // Handle remember me
                    if ($rememberMe) {
                        setcookie('RememberedIdNumber', $idNumber, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    } else {
                        setcookie('RememberedIdNumber', '', time() - 3600, '/');
                    }

                    // Redirect based on role
                    if ($user['Role'] === 'Admin') {
                        redirect('admin/home');
                    } else {
                        redirect('home');
                    }
                }
            }

            // If we have errors, show the login form again
            $rememberedIdNumber = $idNumber;
            include __DIR__ . '/../views/login/login.php';
        } else {
            $this->index();
        }
    }

    public function logout() {
        // Clear session
        $_SESSION = array();
        session_destroy();

        // Set logout message
        $_SESSION['LoggedOut'] = "You have been logged out successfully.";

        // Redirect to login
        redirect('login');
    }
}
?>
