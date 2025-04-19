<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "webdev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$admin_username = "admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT); // mã hóa mật khẩu

$stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $admin_username, $admin_password);
$stmt->execute();

echo "✅ Tạo tài khoản admin thành công với username: admin và password: admin123";

$stmt->close();
$conn->close();
?>
