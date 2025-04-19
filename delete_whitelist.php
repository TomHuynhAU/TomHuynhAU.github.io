<?php
session_start();
require 'db.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['steam_role']) || $_SESSION['steam_role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$stmt = $conn->prepare("DELETE FROM characters WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đã xóa đơn thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $conn->error]);
}

$stmt->close();
$conn->close();
