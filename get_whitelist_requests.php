<?php
header('Content-Type: application/json');

// Kết nối database
$db = new mysqli('localhost', 'root', '', 'webdev');
if ($db->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Lấy dữ liệu từ request
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $db->real_escape_string($_GET['status']) : '';

// Tính toán offset
$offset = ($page - 1) * $perPage;

// Tạo câu truy vấn
$query = "SELECT id, firstname, lastname, birthdate, gender, bio, status, steamid, created_at FROM characters WHERE 1=1";
if (!empty($search)) {
    $query .= " AND id LIKE '%$search%'";
}
if (!empty($status)) {
    $query .= " AND status = '$status'";
}
$query .= " LIMIT $offset, $perPage";

try {
    $result = $db->query($query);
    if (!$result) {
        throw new Exception("Query failed: " . $db->error);
    }

    $requests = $result->fetch_all(MYSQLI_ASSOC);

    // Đếm tổng số bản ghi
    $countQuery = "SELECT COUNT(*) as total FROM characters WHERE 1=1";
    if (!empty($search)) {
        $countQuery .= " AND id LIKE '%$search%'";
    }
    if (!empty($status)) {
        $countQuery .= " AND status = '$status'";
    }
    $countResult = $db->query($countQuery);
    $total = $countResult->fetch_assoc()['total'];

    echo json_encode([
        'requests' => $requests,
        'total' => $total
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit; // Thêm dòng này
}
