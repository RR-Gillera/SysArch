<?php
/**
 * setup.php – Run this once to create the required database tables.
 * Access at: http://localhost/php/setup.php
 */
require_once __DIR__ . '/config.php';

$pdo = getDB();

$pdo->exec("CREATE TABLE IF NOT EXISTS signups (
    IdNumber           VARCHAR(20)  NOT NULL,
    FirstName          VARCHAR(100) NOT NULL,
    LastName           VARCHAR(100) NOT NULL,
    MiddleName         VARCHAR(100) NOT NULL DEFAULT '',
    CourseLevel        VARCHAR(20)  NOT NULL,
    Password           VARCHAR(255) NOT NULL,
    Email              VARCHAR(255) NOT NULL,
    Course             VARCHAR(20)  NOT NULL,
    Address            VARCHAR(255) NOT NULL,
    Role               VARCHAR(20)  NOT NULL DEFAULT 'Student',
    RemainingSessions  INT          NOT NULL DEFAULT 30,
    LastAnnouncementsReadAt DATETIME NULL,
    ProfileImagePath   VARCHAR(255) NULL,
    CreatedAt          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IdNumber),
    UNIQUE KEY uq_email (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS sit_in_records (
    Id               INT          NOT NULL AUTO_INCREMENT,
    StudentIdNumber  VARCHAR(20)  NOT NULL,
    Purpose          VARCHAR(120) NOT NULL,
    Laboratory       VARCHAR(50)  NOT NULL,
    TimeIn           DATETIME     NOT NULL,
    TimeOut          DATETIME     NULL,
    PRIMARY KEY (Id),
    KEY idx_student (StudentIdNumber),
    CONSTRAINT fk_sitin_student
        FOREIGN KEY (StudentIdNumber) REFERENCES signups(IdNumber)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
    Id        INT           NOT NULL AUTO_INCREMENT,
    Title     VARCHAR(120)  NOT NULL,
    Message   VARCHAR(1000) NOT NULL,
    PostedBy  VARCHAR(80)   NOT NULL DEFAULT 'CCS Admin',
    PostedAt  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

echo '<p style="font-family:sans-serif;color:green;">✔ Tables created (or already exist). <a href="login.php">Go to Login</a></p>';
