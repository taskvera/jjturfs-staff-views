<?php
// core/middleware.php
/**
 * Example: Protecting a Staff Dashboard Route
 *
 * When defining your routes (or within your controller), you can call middleware functions
 * to protect routes based on user roles. For instance, if you have a route intended only for
 * staff users, you can enforce the protection like so:
 *
 * Usage:
 *
 * if ($requestUri === '/staff-dashboard') {
 *     // Call requireStaff() to ensure the user is both authenticated and has a 'staff' role.
 *     // This function will log unauthorized access attempts and redirect as necessary.
 *     requireStaff();
 *
 *     // If the user passes the check, include the staff dashboard view.
 *     require_once __DIR__ . '/../app/Views/StaffDashboardView.html';
 * }
 *
 * The requireStaff() function internally calls requireAuth() to verify that the user is logged in,
 * and then checks that $_SESSION['user']['type'] equals 'staff'. If these conditions are not met,
 * it logs the event and redirects the user to a safe page (such as a login or error page).
 */


require_once __DIR__ . '/Logger.php';  // Ensure Logger is loaded for logging unauthorized events.

function requireAuth() {
    if (!isset($_SESSION['user'])) {
        // Log unauthorized access attempt.
        Logger::getInstance()->warning("Unauthorized access attempt", [
            'uri' => $_SERVER['REQUEST_URI'],
            'ip'  => $_SERVER['REMOTE_ADDR']
        ]);
        header("Location: /login");
        exit;
    }
}

function requireStaff() {
    // First, ensure the user is authenticated.
    requireAuth();
    
    // Now check if the user has a staff role.
    if ($_SESSION['user']['type'] !== 'staff') {
        Logger::getInstance()->warning("Staff-only access violation", [
            'user' => $_SESSION['user'],
            'uri'  => $_SERVER['REQUEST_URI']
        ]);
        // Optionally, you can redirect them to an error page or back to the previous page.
        header("Location: /unauthorized");
        exit;
    }
}
