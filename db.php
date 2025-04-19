<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "webdev";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode([
        'error' => 'Database connection failed',
        'debug' => [
            'error' => $conn->connect_error,
            'server' => $servername,
            'user' => $username,
            'db' => $dbname
        ]
    ]));
}

// Debug: Kiểm tra bảng characters tồn tại
$checkTable = $conn->query("SHOW TABLES LIKE 'characters'");
if (!$checkTable || $checkTable->num_rows == 0) {
    error_log("Table 'characters' does not exist");
    die(json_encode(['error' => "Table 'characters' not found"]));
}
