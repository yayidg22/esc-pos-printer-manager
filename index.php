<?php
error_reporting(E_ERROR | E_PARSE);

// Allow requests from any origin
header("Access-Control-Allow-Origin: *");

// Allow specified methods
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Allow specified headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Get the requested URI
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];


// Route requests to the appropriate PHP file
if ($requestMethod === "GET" && $requestUri === '/printers') {
    // Route to printers.php
    require 'printers.php';
} elseif ($requestMethod === "POST" && $requestUri === '/print') {
    // Route to print.php
    require 'print.php';
}elseif ($requestMethod === "POST" && $requestUri === '/print-test') {
    // Route to print.php
    require 'print_example.php';
} else {
    // Handle other endpoints (if needed)
    http_response_code(404);
    echo "Endpoint not found";
}
