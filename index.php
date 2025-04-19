<?php
session_start();
$loggedIn = isset($_GET['loggedin']) && $_GET['loggedin'] === 'true';
if ($loggedIn) {
    // Chuyển hướng đến user_dashboard.php nếu đã đăng nhập
    header("Location: /user_dashboard.php");
    exit();
}
// Kiểm tra nếu session admin_id không tồn tại hoặc đã hết hạn (180 giây = 3 phút)
$timeout_duration = 900; // 3 phút (180 giây)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    // Nếu session hết hạn
    session_unset(); // Xóa session hiện tại
    session_destroy(); // Hủy session
    header("Location: index.php"); // Chuyển hướng về trang đăng nhập
    exit();
}

// Cập nhật thời gian hoạt động cuối cùng nếu session còn hoạt động
$_SESSION['last_activity'] = time();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Steam Whitelist Quiz</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
     <div class="content-box">
        <div class="flex-item">
            <div class="logo">
                <img src="assets/GTAS-Gif.gif" alt="#">
            </div>
            <form class="signup-form">
            <?php if (!$loggedIn && !isset($_SESSION['steamid'])): ?>
                <a href="/steamauth/login.php"><i class="fab fa-steam"></i>ĐĂNG NHẬP BẰNG STEAM</a>
            <?php endif; ?> 
            <br>
            <p style="text-transform: uppercase;"><br><b>Người chơi sử dụng tài khoản Steam để liên kết với hệ thống. Do đó, bạn cần đăng ký Whitelist bằng tài khoản Steam của mình.</b>
            <br><br>
            </form>
        </div>
        </div>

    </div>
    </div>
</body>
</html>
