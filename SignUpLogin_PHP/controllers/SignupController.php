<?php
// controllers/SignupController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Signup.php';

class SignupController {
    private $db;
    private $signupModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->signupModel = new Signup($this->db);
    }

    public function index() {
        // If already logged in, redirect to home
        if (isLoggedIn()) {
            if (isAdmin()) {
                redirect('admin/home');
            } else {
                redirect('home');
            }
        }

        include __DIR__ . '/../views/signup/signup.php';
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'IdNumber' => isset($_POST['IdNumber']) ? trim($_POST['IdNumber']) : '',
                'FirstName' => isset($_POST['FirstName']) ? trim($_POST['FirstName']) : '',
                'LastName' => isset($_POST['LastName']) ? trim($_POST['LastName']) : '',
                'MiddleName' => isset($_POST['MiddleName']) ? trim($_POST['MiddleName']) : '',
                'CourseLevel' => isset($_POST['CourseLevel']) ? $_POST['CourseLevel'] : '',
                'Password' => isset($_POST['Password']) ? $_POST['Password'] : '',
                'ConfirmPassword' => isset($_POST['ConfirmPassword']) ? $_POST['ConfirmPassword'] : '',
                'Email' => isset($_POST['Email']) ? trim($_POST['Email']) : '',
                'Course' => isset($_POST['Course']) ? $_POST['Course'] : '',
                'Address' => isset($_POST['Address']) ? trim($_POST['Address']) : ''
            ];

            $errors = [];

            // Validation - ID Number (8 digits)
            if (empty($data['IdNumber'])) {
                $errors['IdNumber'] = "ID Number is required.";
            } elseif (!preg_match('/^\d{8}$/', $data['IdNumber'])) {
                $errors['IdNumber'] = "ID Number must be exactly 8 digits.";
            }

            // Validation - First Name (letters only)
            if (empty($data['FirstName'])) {
                $errors['FirstName'] = "First Name is required.";
            } elseif (!preg_match('/^[a-zA-Z\s\-\' ]+$/', $data['FirstName'])) {
                $errors['FirstName'] = "First Name must contain letters only.";
            }

            // Validation - Last Name (letters only)
            if (empty($data['LastName'])) {
                $errors['LastName'] = "Last Name is required.";
            } elseif (!preg_match('/^[a-zA-Z\s\-\' ]+$/', $data['LastName'])) {
                $errors['LastName'] = "Last Name must contain letters only.";
            }

            // Validation - Middle Name (letters only, optional)
            if (!empty($data['MiddleName']) && !preg_match('/^[a-zA-Z\s\-\' ]+$/', $data['MiddleName'])) {
                $errors['MiddleName'] = "Middle Name must contain letters only.";
            }

            // Validation - Course Level
            if (empty($data['CourseLevel'])) {
                $errors['CourseLevel'] = "Course Level is required.";
            }

            // Validation - Password
            if (empty($data['Password'])) {
                $errors['Password'] = "Password is required.";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $data['Password'])) {
                $errors['Password'] = "Password must have at least 8 characters, an uppercase, a lowercase, a number, and a symbol.";
            }

            // Validation - Confirm Password
            if (empty($data['ConfirmPassword'])) {
                $errors['ConfirmPassword'] = "Please confirm your password.";
            } elseif ($data['Password'] !== $data['ConfirmPassword']) {
                $errors['ConfirmPassword'] = "Passwords do not match.";
            }

            // Validation - Email
            if (empty($data['Email'])) {
                $errors['Email'] = "Email is required.";
            } elseif (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
                $errors['Email'] = "Enter a valid email address.";
            }

            // Validation - Course
            if (empty($data['Course'])) {
                $errors['Course'] = "Course is required.";
            }

            // Validation - Address
            if (empty($data['Address'])) {
                $errors['Address'] = "Address is required.";
            }

            // Check if ID number exists
            if (!isset($errors['IdNumber']) && $this->signupModel->idNumberExists($data['IdNumber'])) {
                $errors['IdNumber'] = "This ID Number is already registered.";
            }

            // Check if email exists
            if (!isset($errors['Email']) && $this->signupModel->emailExists($data['Email'])) {
                $errors['Email'] = "This Email is already registered.";
            }

            if (empty($errors)) {
                // Create user
                if ($this->signupModel->create($data)) {
                    $_SESSION['Success'] = "Registration successful!";
                    redirect('login');
                } else {
                    $errors['general'] = "Registration failed. Please try again.";
                }
            }

            // If we have errors, show the signup form again
            include __DIR__ . '/../views/signup/signup.php';
        } else {
            $this->index();
        }
    }
}
?>
