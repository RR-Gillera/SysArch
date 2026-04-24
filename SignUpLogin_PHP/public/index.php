<?php
// public/index.php - Main entry point (Router)

require_once __DIR__ . '/../config/config.php';

// Simple router
$request = $_SERVER['REQUEST_URI'];
$basePath = '/SignUpLogin_PHP/public/';

// Remove base path and query string
$path = str_replace($basePath, '', $request);
$path = strtok($path, '?');

// Remove trailing slash
$path = rtrim($path, '/');

// Default controller and action
$controller = 'LoginController';
$action = 'index';

// Parse the path
if (!empty($path)) {
    $parts = explode('/', $path);
    
    if (!empty($parts[0])) {
        // Map URL segments to controllers
        switch ($parts[0]) {
            case 'login':
                $controller = 'LoginController';
                $action = isset($parts[1]) ? $parts[1] : 'index';
                break;
            case 'signup':
                $controller = 'SignupController';
                $action = isset($parts[1]) ? $parts[1] : 'index';
                break;
            case 'home':
                $controller = 'HomeController';
                $action = 'index';
                break;
            case 'profile':
                $controller = 'ProfileController';
                $action = isset($parts[1]) ? $parts[1] : 'index';
                break;
            case 'admin':
                $controller = 'AdminController';
                $action = isset($parts[1]) ? $parts[1] : 'home';
                break;
            default:
                // Try to use the first part as controller name
                $controllerName = ucfirst($parts[0]) . 'Controller';
                if (file_exists(__DIR__ . "/../controllers/{$controllerName}.php")) {
                    $controller = $controllerName;
                    $action = isset($parts[1]) ? $parts[1] : 'index';
                }
                break;
        }
    }
}

// Load controller
$controllerPath = __DIR__ . "/../controllers/{$controller}.php";
if (file_exists($controllerPath)) {
    require_once $controllerPath;
    
    if (class_exists($controller)) {
        $controllerInstance = new $controller();
        
        if (method_exists($controllerInstance, $action)) {
            $controllerInstance->$action();
        } else {
            http_response_code(404);
            echo "Action not found: {$action}";
        }
    } else {
        http_response_code(500);
        echo "Controller class not found: {$controller}";
    }
} else {
    http_response_code(404);
    echo "Controller not found: {$controller}";
}
?>
