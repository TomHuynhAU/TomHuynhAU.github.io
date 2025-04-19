<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra phương thức
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Chỉ chấp nhận phương thức POST']));
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['steamid'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']));
}

// Validate dữ liệu
$required = ['firstname', 'lastname', 'gender', 'bio', 'birthdate'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']));
    }
}

// Validate ngày sinh
$birthdate = $_POST['birthdate'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Ngày sinh không hợp lệ']));
}

$birthdateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
if (!$birthdateObj || $birthdateObj->format('Y-m-d') !== $birthdate) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Ngày sinh không hợp lệ']));
}

// Kiểm tra tuổi (ít nhất 15 tuổi)
$today = new DateTime();
$age = $today->diff($birthdateObj)->y;
if ($age < 15) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Nhân vật phải ít nhất 15 tuổi']));
}

// Kết nối database
$conn = new mysqli("localhost", "root", "", "webdev");
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Kết nối database thất bại']));
}

// Kiểm tra số từ trong bio
$bio = trim($_POST['bio']);
$wordCount = str_word_count($bio);

if ($wordCount > 2000) {
    echo json_encode([
        'success' => false,
        'message' => 'Tiểu sử không được vượt quá 2000 từ!'
    ]);
    exit;
}

// Kiểm tra số lượng nhân vật hiện có
$steamid = $conn->real_escape_string($_SESSION['steamid']);
$countQuery = $conn->query("SELECT COUNT(*) as count FROM characters WHERE steamid = '$steamid'");
$countResult = $countQuery->fetch_assoc();

if ($countResult['count'] >= 3) {
    $conn->close();
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Bạn đã đạt giới hạn 3/3 nhân vật']));
}

// Chuẩn bị dữ liệu
$firstname = $conn->real_escape_string($_POST['firstname']);
$lastname = $conn->real_escape_string($_POST['lastname']);
$gender = $conn->real_escape_string($_POST['gender']);
$bio = $conn->real_escape_string($_POST['bio']);
$birthdate = $conn->real_escape_string($_POST['birthdate']);

// Sử dụng transaction để đảm bảo toàn vẹn dữ liệu
$conn->begin_transaction();

try {
    $sql = "INSERT INTO characters (firstname, lastname, birthdate, gender, bio, status, steamid) 
            VALUES ('$firstname', '$lastname', '$birthdate', '$gender', '$bio', 'đang xử lý', '$steamid')";

    if (!$conn->query($sql)) {
        throw new Exception($conn->error);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Tạo nhân vật thành công']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
}

$conn->close();
