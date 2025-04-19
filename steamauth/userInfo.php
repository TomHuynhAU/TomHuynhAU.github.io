<?php
session_start();

require 'SteamAuth.class.php';
require 'SteamConfig.php';

// Cấu hình database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "webdev";

// Kết nối MySQL
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$auth = new SteamAuth();
if ($auth->validate()) {
    $steamID = $_SESSION['steamid'];
    $steamHEX = dechex($steamID);
    $_SESSION['steamHEX'] = $steamHEX;
    $_SESSION['last_activity'] = time();

    // 🔑 Lấy dữ liệu người dùng từ Steam API
    $apiKey = '67080849D059BF2A4E8408460E1F680E'; // <<< thay bằng API Key thật
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$apiKey&steamids=$steamID";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (!empty($data['response']['players'][0])) {
        $profile = $data['response']['players'][0];
        $_SESSION['steam_name'] = $profile['personaname'];
        $_SESSION['steam_avatar'] = $profile['avatarfull'];
    } else {
        $_SESSION['steam_name'] = "Unknown";
        $_SESSION['steam_avatar'] = "https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/xx/xxxxxxxxxxxx.jpg";
    }

    // 👉 Kiểm tra role trong DB
    $stmt = $conn->prepare("SELECT role FROM steam_users WHERE steamID = ?");
    $stmt->bind_param("s", $steamID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Nếu chưa có, thêm mới và set role mặc định là "User"
        $defaultRole = "player";
        $stmt = $conn->prepare("INSERT INTO steam_users (steamID, steamHEX, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $steamID, $steamHEX, $defaultRole);
        $stmt->execute();
        $_SESSION['steam_role'] = $defaultRole;
    } else {
        // Nếu đã tồn tại, lấy role từ DB
        $row = $result->fetch_assoc();
        $_SESSION['steam_role'] = $row['role'];
    }

    $stmt->close();
    $conn->close();

    // ✅ Quay về trang chính
    header("Location: " . $steamauth['loginpage'] . "?loggedin=true");
    exit;
} else {
    echo "Đăng nhập thất bại.";
}
?>

