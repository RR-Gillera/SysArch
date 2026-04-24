<?php
// models/Signup.php

class Signup {
    private $conn;
    private $table = 'signups';

    public $IdNumber;
    public $FirstName;
    public $LastName;
    public $MiddleName;
    public $CourseLevel;
    public $Password;
    public $Email;
    public $Course;
    public $Address;
    public $Role;
    public $RemainingSessions;
    public $LastAnnouncementsReadAt;
    public $ProfileImagePath;
    public $CreatedAt;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if ID number exists
    public function idNumberExists($idNumber) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE IdNumber = :idNumber";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idNumber', $idNumber);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    // Check if email exists
    public function emailExists($email) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE Email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    // Create new signup
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (IdNumber, FirstName, LastName, MiddleName, CourseLevel, Password, Email, Course, Address, Role, RemainingSessions, CreatedAt) 
                  VALUES (:idNumber, :firstName, :lastName, :middleName, :courseLevel, :password, :email, :course, :address, :role, :remainingSessions, :createdAt)";

        $stmt = $this->conn->prepare($query);

        $this->IdNumber = htmlspecialchars(strip_tags($data['IdNumber']));
        $this->FirstName = htmlspecialchars(strip_tags($data['FirstName']));
        $this->LastName = htmlspecialchars(strip_tags($data['LastName']));
        $this->MiddleName = htmlspecialchars(strip_tags($data['MiddleName']));
        $this->CourseLevel = htmlspecialchars(strip_tags($data['CourseLevel']));
        $this->Password = password_hash($data['Password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $this->Email = htmlspecialchars(strip_tags($data['Email']));
        $this->Course = htmlspecialchars(strip_tags($data['Course']));
        $this->Address = htmlspecialchars(strip_tags($data['Address']));
        $this->Role = 'Student';
        $this->RemainingSessions = 30;
        $this->CreatedAt = date('Y-m-d H:i:s');

        $stmt->bindParam(':idNumber', $this->IdNumber);
        $stmt->bindParam(':firstName', $this->FirstName);
        $stmt->bindParam(':lastName', $this->LastName);
        $stmt->bindParam(':middleName', $this->MiddleName);
        $stmt->bindParam(':courseLevel', $this->CourseLevel);
        $stmt->bindParam(':password', $this->Password);
        $stmt->bindParam(':email', $this->Email);
        $stmt->bindParam(':course', $this->Course);
        $stmt->bindParam(':address', $this->Address);
        $stmt->bindParam(':role', $this->Role);
        $stmt->bindParam(':remainingSessions', $this->RemainingSessions);
        $stmt->bindParam(':createdAt', $this->CreatedAt);

        return $stmt->execute();
    }

    // Get user by ID number
    public function getByIdNumber($idNumber) {
        $query = "SELECT * FROM {$this->table} WHERE IdNumber = :idNumber LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idNumber', $idNumber);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get user by first name (for admin login)
    public function getByNameAndRole($firstName, $role) {
        $query = "SELECT * FROM {$this->table} WHERE FirstName = :firstName AND Role = :role LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':firstName', $firstName);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Update user
    public function update($data) {
        $query = "UPDATE {$this->table} 
                  SET FirstName = :firstName, LastName = :lastName, MiddleName = :middleName, 
                      CourseLevel = :courseLevel, Email = :email, Course = :course, Address = :address,
                      ProfileImagePath = :profileImagePath
                  WHERE IdNumber = :idNumber";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':firstName', $data['FirstName']);
        $stmt->bindParam(':lastName', $data['LastName']);
        $stmt->bindParam(':middleName', $data['MiddleName']);
        $stmt->bindParam(':courseLevel', $data['CourseLevel']);
        $stmt->bindParam(':email', $data['Email']);
        $stmt->bindParam(':course', $data['Course']);
        $stmt->bindParam(':address', $data['Address']);
        $stmt->bindParam(':profileImagePath', $data['ProfileImagePath']);
        $stmt->bindParam(':idNumber', $data['IdNumber']);

        return $stmt->execute();
    }

    // Change password
    public function changePassword($idNumber, $newPassword) {
        $query = "UPDATE {$this->table} SET Password = :password WHERE IdNumber = :idNumber";
        $stmt = $this->conn->prepare($query);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':idNumber', $idNumber);
        return $stmt->execute();
    }
}
?>
