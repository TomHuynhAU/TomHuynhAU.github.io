<?php
session_start();
require 'db.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['steam_role']) || $_SESSION['steam_role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Lấy dữ liệu từ POST
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

// Validate action
$validActions = ['approve', 'reject', 'pending'];
if (!in_array($action, $validActions)) {
    die(json_encode(['success' => false, 'message' => 'Invalid action']));
}

// Map action to status
$statusMap = [
    'approve' => 'đã duyệt',
    'reject' => 'từ chối',
    'pending' => 'đang xử lý'
];
$newStatus = $statusMap[$action];

// Kiểm tra nếu là hành động "reject" nhưng không có lý do
if ($action === 'reject' && (!$reason || strlen($reason) > 250)) {
    die(json_encode(['success' => false, 'message' => 'Lý do từ chối không hợp lệ (tối đa 250 ký tự)']));
}

// Cập nhật database
if ($action === 'reject') {
    // Nếu là từ chối, cập nhật cả lý do từ chối
    $stmt = $conn->prepare("UPDATE characters SET status = ?, reject_reason = ? WHERE id = ?");
    $stmt->bind_param("ssi", $newStatus, $reason, $id);
} else {
    // Nếu không phải từ chối, chỉ cập nhật trạng thái
    $stmt = $conn->prepare("UPDATE characters SET status = ?, reject_reason = NULL WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $conn->error]);
}

$stmt->close();
$conn->close();
