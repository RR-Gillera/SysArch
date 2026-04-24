<?php
// controllers/ProfileController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Signup.php';

class ProfileController {
    private $db;
    private $signupModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->signupModel = new Signup($this->db);
    }

    public function index() {
        if (!isLoggedIn()) {
            redirect('login');
        }

        // Get user data
        $user = $this->signupModel->getByIdNumber($_SESSION['IdNumber']);

        include __DIR__ . '/../views/profile/index.php';
    }

    public function edit() {
        if (!isLoggedIn()) {
            redirect('login');
        }

        // Get user data
        $user = $this->signupModel->getByIdNumber($_SESSION['IdNumber']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'IdNumber' => $_SESSION['IdNumber'],
                'FirstName' => isset($_POST['FirstName']) ? trim($_POST['FirstName']) : '',
                'LastName' => isset($_POST['LastName']) ? trim($_POST['LastName']) : '',
                'MiddleName' => isset($_POST['MiddleName']) ? trim($_POST['MiddleName']) : '',
                'CourseLevel' => isset($_POST['CourseLevel']) ? $_POST['CourseLevel'] : '',
                'Email' => isset($_POST['Email']) ? trim($_POST['Email']) : '',
                'Course' => isset($_POST['Course']) ? $_POST['Course'] : '',
                'Address' => isset($_POST['Address']) ? trim($_POST['Address']) : '',
                'ProfileImagePath' => $user['ProfileImagePath']
            ];

            // Handle file upload
            if (isset($_FILES['ProfileImage']) && $_FILES['ProfileImage']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../public/images/profiles/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExtension = pathinfo($_FILES['ProfileImage']['name'], PATHINFO_EXTENSION);
                $fileName = 'profile_' . $_SESSION['IdNumber'] . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['ProfileImage']['tmp_name'], $uploadPath)) {
                    $data['ProfileImagePath'] = '/images/profiles/' . $fileName;
                }
            }

            // Validation
            $errors = [];

            if (empty($data['FirstName'])) {
                $errors['FirstName'] = "First Name is required.";
            } elseif (!preg_match('/^[a-zA-Z\s\-\' ]+$/', $data['FirstName'])) {
                $errors['FirstName'] = "First Name must contain letters only.";
            }

            if (empty($data['LastName'])) {
                $errors['LastName'] = "Last Name is required.";
            } elseif (!preg_match('/^[a-zA-Z\s\-\' ]+$/', $data['LastName'])) {
                $errors['LastName'] = "Last Name must contain letters only.";
            }

            if (empty($data['Email'])) {
                $errors['Email'] = "Email is required.";
            } elseif (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
                $errors['Email'] = "Enter a valid email address.";
            }

            if (empty($errors)) {
                if ($this->signupModel->update($data)) {
                    // Update session
                    $_SESSION['UserName'] = $data['FirstName'] . ' ' . $data['LastName'];
                    $_SESSION['Email'] = $data['Email'];
                    $_SESSION['Course'] = $data['Course'];
                    $_SESSION['CourseLevel'] = $data['CourseLevel'];
                    $_SESSION['ProfileImagePath'] = $data['ProfileImagePath'];
                    
                    $_SESSION['ProfileUpdated'] = "Profile updated successfully!";
                    redirect('profile');
                } else {
                    $errors['general'] = "Update failed. Please try again.";
                }
            }

            $user = array_merge($user, $data);
            include __DIR__ . '/../views/profile/edit.php';
        } else {
            include __DIR__ . '/../views/profile/edit.php';
        }
    }

    public function changePassword() {
        if (!isLoggedIn()) {
            redirect('login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = isset($_POST['CurrentPassword']) ? $_POST['CurrentPassword'] : '';
            $newPassword = isset($_POST['NewPassword']) ? $_POST['NewPassword'] : '';
            $confirmPassword = isset($_POST['ConfirmPassword']) ? $_POST['ConfirmPassword'] : '';

            $errors = [];

            // Get current user
            $user = $this->signupModel->getByIdNumber($_SESSION['IdNumber']);

            // Verify current password
            if (empty($currentPassword)) {
                $errors['CurrentPassword'] = "Current password is required.";
            } elseif (!password_verify($currentPassword, $user['Password'])) {
                $errors['CurrentPassword'] = "Current password is incorrect.";
            }

            // Validate new password
            if (empty($newPassword)) {
                $errors['NewPassword'] = "New password is required.";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPassword)) {
                $errors['NewPassword'] = "Password must have at least 8 characters, an uppercase, a lowercase, a number, and a symbol.";
            }

            // Validate confirm password
            if (empty($confirmPassword)) {
                $errors['ConfirmPassword'] = "Please confirm your password.";
            } elseif ($newPassword !== $confirmPassword) {
                $errors['ConfirmPassword'] = "Passwords do not match.";
            }

            if (empty($errors)) {
                if ($this->signupModel->changePassword($_SESSION['IdNumber'], $newPassword)) {
                    $_SESSION['PasswordChanged'] = "Password changed successfully!";
                    redirect('profile');
                } else {
                    $errors['general'] = "Password change failed. Please try again.";
                }
            }

            include __DIR__ . '/../views/profile/change_password.php';
        } else {
            include __DIR__ . '/../views/profile/change_password.php';
        }
    }
}
?>
