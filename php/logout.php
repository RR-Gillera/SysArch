<?php
session_start();
session_destroy();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']);
header('Location: /php/login.php');
exit;
