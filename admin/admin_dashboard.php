<?php
session_start();

// Kiểm tra nếu session admin_id không tồn tại hoặc đã hết hạn (180 giây = 3 phút)
$timeout_duration = 900; // 3 phút (180 giây)

// Nếu session chưa tồn tại hoặc session đã hết hạn
if (!isset($_SESSION['admin_id'])) {
    // Nếu chưa đăng nhập, chuyển hướng về trang login
    header("Location: admin_login.php");
    exit();
} elseif (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    // Nếu session hết hạn
    session_unset(); // Xóa session hiện tại
    session_destroy(); // Hủy session
    header("Location: admin_login.php"); // Chuyển hướng về trang đăng nhập
    exit();
}

// Cập nhật thời gian hoạt động cuối cùng nếu session còn hoạt động
$_SESSION['last_activity'] = time();

$conn = new mysqli("localhost", "root", "", "webdev");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý duyệt/từ chối
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['character_id'], $_POST['new_status'])) {
    $char_id = intval($_POST['character_id']);
    $new_status = trim($_POST['new_status']);

    if (!empty($new_status)) {
        // Cập nhật status cho nhân vật
        $stmt = $conn->prepare("UPDATE characters SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $char_id);
        $stmt->execute();
        $stmt->close();

        // Nếu được duyệt thì lấy steamid từ session và thêm vào bảng users
        if ($new_status === "Duyệt") {
            // Kiểm tra xem steamid đã có trong session chưa
            if (isset($_SESSION['steamid'])) {
                $steamid = $_SESSION['steamid'];  // Lấy steamid từ session

                // Kiểm tra nếu steamid chưa tồn tại trong bảng users
                $stmt = $conn->prepare("SELECT id FROM users WHERE identifier = ?");
                $stmt->bind_param("s", $steamid);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    // Nếu chưa có, thêm vào bảng users
                    $stmt = $conn->prepare("INSERT INTO users (identifier) VALUES (?)");
                    $stmt->bind_param("s", $steamid);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    header("Location: admin_dashboard.php");
    exit();
}

 // Xử lý xóa đơn
 if (isset($_POST['delete_character_id'])) {
    $delete_id = intval($_POST['delete_character_id']);

    // Lấy steamid từ bảng characters trước khi xóa
    $stmt = $conn->prepare("SELECT steamid FROM characters WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($steamid);
    $stmt->fetch();
    $stmt->close();

    // Xóa dòng trong bảng users nếu steamid tồn tại
    if (!empty($steamid)) {
        $stmt = $conn->prepare("DELETE FROM users WHERE identifier = ?");
        $stmt->bind_param("s", $steamid);
        $stmt->execute();
        $stmt->close();
    }

    // Xóa dòng trong bảng characters
    $stmt = $conn->prepare("DELETE FROM characters WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // Reload lại trang sau khi xử lý
    header("Location: admin_dashboard.php");
    exit();
}


$result = $conn->query("SELECT id, name, bio, status, steamid FROM characters");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <!-- filepath: c:\xampp\htdocs\admin\admin_dashboard.php -->
<!-- filepath: c:\xampp\htdocs\admin\admin_dashboard.php -->
<style>
    body {
        font-family: 'Roboto', 'Noto Sans', sans-serif; /* Sử dụng font Roboto và Noto Sans */
        background-color: #121212; /* Màu nền tối */
        color: #e0e0e0; /* Màu chữ sáng */
        margin: 0;
        padding: 0;
    }

    h2 {
        text-align: center;
        color: #66b2ff; /* Màu xanh biển */
        margin-top: 20px;
        font-size: 28px;
        text-shadow: 1px 1px 2px #000;
    }

    .logout-container {
        text-align: center; /* Căn giữa nút đăng xuất */
        margin: 20px 0;
    }

    a button {
        background-color: #66b2ff; /* Màu xanh biển */
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    a button:hover {
        background-color: #5599e6; /* Màu xanh biển đậm hơn khi hover */
    }

    table {
        width: 90%;
        margin: 20px auto;
        border-collapse: collapse;
        background-color: #1e1e1e; /* Màu nền bảng tối */
        box-shadow: 0 0 10px rgba(102, 178, 255, 0.5); /* Ánh sáng xanh biển */
        border-radius: 10px;
        overflow: hidden;
    }

    th, td {
        border: 1px solid #66b2ff; /* Viền xanh biển */
        padding: 15px;
        text-align: center;
        color: #e0e0e0;
        word-wrap: break-word;
        word-break: break-word;
    }

    th {
        background-color: #66b2ff; /* Màu xanh biển */
        color: #fff;
        font-size: 18px;
    }

    td:nth-child(3) { /* Cột "Tiểu Sử" */
        max-width: 300px;
        white-space: normal;
    }

    button {
        background-color: #66b2ff; /* Màu xanh biển */
        color: #fff;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #5599e6; /* Màu xanh biển đậm hơn khi hover */
    }

    button[style*="background-color: #dc3545"] {
        background-color: #dc3545; /* Màu đỏ cho nút Xóa Đơn */
    }

    button[style*="background-color: #dc3545"]:hover {
        background-color: #c82333; /* Màu đỏ đậm hơn khi hover */
    }

</style>
</head>
<body>

<h2>Chào Admin: <?= htmlspecialchars($_SESSION['admin_username']); ?></h2>

<!-- Thêm nút "Đăng xuất" -->
<div class="logout-container">
    <a href="admin_login.php"><button>Đăng xuất</button></a>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Tên Nhân Vật</th>
        <th>Tiểu Sử</th>
        <th>Trạng Thái</th>
        <th>SteamID</th>
        <th>Hành Động</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= htmlspecialchars($row['name']); ?></td>
            <td><?= nl2br(htmlspecialchars($row['bio'])); ?></td>
            <td><?= htmlspecialchars($row['status']); ?></td>
            <td><?= $row['steamid']; ?></td> <!-- Hiển thị steamID -->
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="character_id" value="<?= $row['id']; ?>">
                    <input type="hidden" name="new_status" value="Duyệt">
                    <button type="submit">✔️ Duyệt</button>
                </form>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="character_id" value="<?= $row['id']; ?>">
                    <input type="hidden" name="new_status" value="Không duyệt">
                    <button type="submit">❌ Từ chối</button>
                </form>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="character_id" value="<?= $row['id']; ?>">
                    <input type="hidden" name="new_status" value="Đang xử lý">
                    <button type="submit">📝 Đang xử lí</button>
                </form>

                <!-- Nút Xóa Đơn -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_character_id" value="<?= $row['id']; ?>">
                    <button type="submit" style="background-color: #dc3545; color: #fff;">🗑️ Xóa Đơn</button>
                </form>

            </td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
