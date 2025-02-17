<?php
// public/index.php

// Start the session.
session_start();

// Include required files.
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/middleware.php';

// ----------------------------
// Configuration
// ----------------------------

// Define a constant for development mode.
// In production, you might disable DEBUG-level logging.
define('DEV_MODE', true);

// Set log file path (make sure the directory exists).
$logFile = __DIR__ . '/../logs/app.log';

// Set log level: use DEBUG level for development, INFO or higher for production.
$logLevel = DEV_MODE ? Logger::LEVEL_DEBUG : Logger::LEVEL_INFO;

// Initialize the global logger.
Logger::init($logFile, $logLevel);
$logger = Logger::getInstance();
$logger->info("Application bootstrap started", [
    'ip'  => $_SERVER['REMOTE_ADDR'],
    'uri' => $_SERVER['REQUEST_URI']
]);



// ----------------------------
// DEV ONLY: Simulate a logged-in user (comment out in production)
// ----------------------------
if (DEV_MODE && !isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id'            => 1,
        'username'      => 'devUser',
        'type'          => 'staff',  // Change to 'staff' to test staff routes
        'customer_type' => 'tenant',
    ];
    $logger->debug("Simulated a logged-in user", $_SESSION['user']);
}

// ----------------------------
// Basic Routing Functions
// ----------------------------

// Get the current request URI (ignoring query parameters)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// A simple router function.
function route($path, $callback) {
    global $requestUri;
    if ($requestUri === $path) {
        $callback();
        exit; // Stop further processing once the route is matched.
    }
}

/**
 * Groups routes by a common URL prefix. If the current URI starts with the provided prefix,
 * the callback is invoked with the remaining URI (sub-route) passed as a parameter.
 *
 * @param string   $prefix   The URL prefix (e.g., '/staff' or '/customer')
 * @param callable $callback A callback function that accepts one parameter: the remaining URI.
 */
function routeGroup($prefix, $callback) {
    global $requestUri;
    if (strpos($requestUri, $prefix) === 0) {
        // Remove the prefix to get the sub-route.
        $subUri = substr($requestUri, strlen($prefix));
        // Ensure subUri always starts with a slash.
        if ($subUri === '' || $subUri[0] !== '/') {
            $subUri = '/' . ltrim($subUri, '/');
        }
        $callback($subUri);
        exit;
    }
}

// ----------------------------
// Define Routes
// ----------------------------

// Home route: determine dashboard based on user type.
route('/', function() use ($logger) {
    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        $user = $_SESSION['user'];
        if (isset($user['type']) && $user['type'] === 'staff') {
            $logger->info("Loading staff dashboard", ['user' => $user]);
            require_once __DIR__ . '/../app/Views/DashboardView.html';
        } else {
            $logger->info("Loading customer dashboard", ['user' => $user]);
            require_once __DIR__ . '/../app/Views/CustomerPortal/CustomerDashboardView.html';
        }
    } else {
        $logger->info("No user session found, redirecting to login.");
        require_once __DIR__ . '/../app/Views/Auth/LoginView.html';
    }
});

// Explicit login route.
route('/login', function() {
    require_once __DIR__ . '/../app/Views/Auth/LoginView.html';
});

/**
 * Staff Routes Group
 *
 * All routes that begin with '/staff' will be handled here.
 * The group is protected by the requireStaff() middleware.
 */
routeGroup('/staff', function($subUri) use ($logger) {
    requireStaff(); // Ensure the user is authenticated and is a staff member.
    
    // When the sub-route is '/' or empty, load the staff dashboard.
    if ($subUri === '/' || $subUri === '') {
        $logger->info("Loading staff dashboard", ['user' => $_SESSION['user']]);
        require_once __DIR__ . '/../app/Views/StaffDashboardView.html';
    }
    // Example sub-route: Staff Orders Page
    elseif ($subUri === '/orders') {
        $logger->info("Loading staff orders page", ['user' => $_SESSION['user']]);
        require_once __DIR__ . '/../app/Views/StaffOrdersView.html';
    }
    // Example sub-route: Staff Reports Page
    elseif ($subUri === '/reports') {
        $logger->info("Loading staff reports page", ['user' => $_SESSION['user']]);
        require_once __DIR__ . '/../app/Views/StaffReportsView.html';
    }
    else {
        http_response_code(404);
        echo "<h1>404 Staff Section Not Found</h1><p>The requested staff page doesn't exist.</p>";
        $logger->warning("Staff sub-route not found", ['subUri' => $subUri]);
    }
});

/**
 * Customer Routes Group
 *
 * All routes that begin with '/customer' will be handled here.
 * The group is protected by a general authentication check.
 */
routeGroup('/customer', function($subUri) use ($logger) {
    // General authentication middleware can be called if needed.
    // For example, you might have a requireCustomer() function that checks further customer permissions.
    if (!isset($_SESSION['user'])) {
        header("Location: /login");
        exit;
    }
    
    // When the sub-route is '/' or empty, load the customer dashboard.
    if ($subUri === '/' || $subUri === '') {
        $logger->info("Loading customer dashboard", ['user' => $_SESSION['user']]);
        require_once __DIR__ . '/../app/Views/CustomerPortal/CustomerDashboardView.html';
    }
    // Example sub-route: Customer Orders Page
    elseif ($subUri === '/orders') {
        $logger->info("Loading customer orders page", ['user' => $_SESSION['user']]);
        require_once __DIR__ . '/../app/Views/CustomerPortal/CustomerOrdersView.html';
    }
    // Example sub-route: Customer Profile Page
    elseif ($subUri === '/profile') {
        $logger->info("Loading customer profile page", ['user' => $_SESSION['user']]);
        require_once __DIR__ . '/../app/Views/CustomerPortal/CustomerProfileView.html';
    }
    else {
        http_response_code(404);
        echo "<h1>404 Customer Section Not Found</h1><p>The requested customer page doesn't exist.</p>";
        $logger->warning("Customer sub-route not found", ['subUri' => $subUri]);
    }
});

// Logout route: Destroy session and redirect to the root index.
route('/logout', function() use ($logger) {
    $logger->info("User logging out", ['user' => $_SESSION['user'] ?? 'Guest']);

    // Unset all session variables.
    $_SESSION = [];

    // Destroy the session.
    session_destroy();

    // Redirect to home.
    header("Location: /");
    exit;
});

// ----------------------------
// Fallback: 404 Not Found
// ----------------------------
http_response_code(404);
echo "<h1>404 Not Found</h1><p>The page you're looking for doesn't exist.</p>";
$logger->warning("Page not found", ['uri' => $_SERVER['REQUEST_URI']]);
