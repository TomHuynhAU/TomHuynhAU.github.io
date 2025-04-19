<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['steamid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "webdev");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$steamid = $_SESSION['steamid'];
$stmt = $conn->prepare("SELECT * FROM characters WHERE steamid = ?");
$stmt->bind_param("s", $steamid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $character = $result->fetch_assoc();
    echo json_encode(['success' => true, 'character' => $character]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân vật']);
}

$stmt->close();
$conn->close();
