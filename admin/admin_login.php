<?php
session_start();

// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "webdev"; // Đảm bảo database này tồn tại

$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý khi gửi form đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_username = trim($_POST["username"]);
    $admin_password = $_POST["password"];

    // Truy vấn tìm admin theo username
    $sql = "SELECT * FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Kiểm tra kết quả
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        // So sánh mật khẩu mã hóa
        if (password_verify($admin_password, $admin['password'])) {
            // Thành công -> lưu session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_message = "❌ Mật khẩu không đúng!";
        }
    } else {
        $error_message = "❌ Tài khoản không tồn tại!";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng Nhập Admin</title>
    <style>
    body {
        font-family: 'Roboto', 'Noto Sans', sans-serif;
        background-color: #121212;
        color: #e0e0e0;
        background-image: url('https://i.pinimg.com/736x/ea/4a/a0/ea4aa0285c0a6c81730889badbfd46b7.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    body::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: -1;
    }

    .login-box {
        background-color: rgba(30, 30, 30, 0.9);
        padding: 30px 40px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(102, 178, 255, 0.3);
        width: 100%;
        max-width: 400px;
        border: 1px solid #66b2ff;
        animation: glow 2s infinite alternate;
    }

    @keyframes glow {
        from {
            box-shadow: 0 0 10px rgba(102, 178, 255, 0.3);
        }
        to {
            box-shadow: 0 0 20px rgba(102, 178, 255, 0.6);
        }
    }

    h2 {
        font-family: 'Roboto', sans-serif;
        font-weight: 700;
        text-align: center;
        color: #66b2ff;
        margin-bottom: 20px;
        font-size: 24px;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-shadow: 1px 1px 2px #000;
    }

    label {
        font-family: 'Roboto', sans-serif;
        font-weight: 500;
        color: #66b2ff;
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        text-transform: uppercase;
    }

    input {
        font-family: 'Noto Sans', sans-serif;
        width: 100%;
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #444;
        background-color: #222;
        color: #fff;
        font-size: 14px;
        transition: all 0.3s;
    }

    input:focus {
        border-color: #66b2ff;
        outline: none;
        box-shadow: 0 0 5px rgba(102, 178, 255, 0.5);
    }

    button {
        font-family: 'Roboto', sans-serif;
        font-weight: 700;
        width: 100%;
        padding: 14px;
        background-color: #66b2ff;
        color: #000;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s;
    }

    button:hover {
        background-color: #3399ff;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 178, 255, 0.4);
    }

    .error {
        font-family: 'Roboto', sans-serif;
        color: #ff4d4d;
        margin-top: 15px;
        text-align: center;
        font-size: 14px;
    }
</style>

</head>
<body>

    <div class="login-box">
        <h2>Đăng Nhập Admin</h2>
        <form method="POST">
            <label for="username">Tài Khoản:</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Mật khẩu:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Đăng nhập</button>
        </form>

        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>

</body>
</html>
