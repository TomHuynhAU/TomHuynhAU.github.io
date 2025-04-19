<?php
session_start();

if (!isset($_SESSION['steamid'])) {
  header("Location: login.php");
  exit;
}

$db = new mysqli('localhost', 'root', '', 'webdev');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateRole') {
  if (!isset($_SESSION['steam_role']) || !in_array($_SESSION['steam_role'], ['admin', 'developer'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
    exit;
  }

  $steamid = $db->real_escape_string($_POST['steamid']);
  $role = $db->real_escape_string($_POST['role']);

  $query = "UPDATE steam_users SET role = '$role' WHERE steamid = '$steamid'";
  if ($db->query($query)) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật vai trò.']);
  }
  exit;
}

if ($db->connect_error) {
  die("Connection failed: " . $db->connect_error);
}

$steamid = $_SESSION['steamid'];
$steamAvatar = $_SESSION['steam_avatar'] ?? 'https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/xx/xxxxxxxxxxxx.jpg';
$steamName   = $_SESSION['steam_name'] ?? 'Guest';
$steamRole   = $_SESSION['steam_role'] ?? 'User';

function getRandomQuestions($db, $total = 2)
{
  $result = $db->query("SELECT COUNT(*) as total FROM quiz_questions");
  $maxQuestions = $result->fetch_assoc()['total'];

  // Không cần seed nữa, chỉ xáo trộn ngẫu nhiên
  $query = "SELECT * FROM quiz_questions ORDER BY RAND() LIMIT $total";
  $result = $db->query($query);

  return $result->fetch_all(MYSQLI_ASSOC);
}

// Lấy câu hỏi ngẫu nhiên mà không cần sử dụng steamid
$questions = getRandomQuestions($db);


$questionsPerPage = 10;
$pageCount = ceil(count($questions) / $questionsPerPage);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>LS-RP Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    @font-face {
      font-family: 'pricedown bl';
      src: url('./font/pricedown bl.ttf') format('truetype');
    }

    body {
      background-color: #1c1e21;
      color: #fff;
      overflow-x: hidden;
      /* Tắt cuộn ngang */
      zoom: 1.1;
      /* Phóng to toàn bộ giao diện */
    }

    .sidebar {
      background-color: #2e2f31;
      height: 100vh;
    }

    .sidebar a {
      color: #ccc;
      display: block;
      padding: 10px 20px;
      text-decoration: none;
    }

    .sidebar a:hover,
    .sidebar .active {
      background-color: #444;
      color: #fff;
    }

    .content {
      padding: 20px;
    }

    .card-custom {
      background-color: #f8f9fa;
      color: #000;
    }

    .user-dropdown-wrapper {
      position: absolute;
      top: 10px;
      right: 20px;
      cursor: pointer;
    }

    .user-box {
      display: flex;
      align-items: center;
      background-color: #2e2f31;
      padding: 10px 15px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
      transition: background-color 0.3s ease;
    }

    .user-box:hover {
      background-color: #444;
    }

    .user-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid #555;
    }

    .user-info {
      color: #fff;
    }

    .user-info strong {
      display: block;
      font-size: 16px;
    }

    .user-info small {
      font-size: 12px;
      color: #ccc;
    }

    .dropdown-menu-custom {
      display: none;
      position: absolute;
      top: 70px;
      right: 0;
      background-color: #1c1e21;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
      z-index: 999;
      overflow: hidden;
      animation: fadeIn 0.3s ease-in-out;
    }

    .dropdown-menu-custom a {
      display: block;
      padding: 10px 20px;
      color: #fff;
      text-decoration: none;
      font-size: 14px;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .dropdown-menu-custom a:hover {
      background-color: #444;
      color: #66b2ff;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Thiết lập phong cách chung cho tất cả các nút */
    .btn {
      position: relative;
      margin: 5px;
      /* Khoảng cách giữa các nút */
      padding: 10px 20px;
      /* Đảm bảo kích thước nút nhất quán */
      display: inline-block;
      /* Giúp dễ căn chỉnh */
      vertical-align: middle;
      /* Căn chỉnh theo chiều dọc */
    }

    /* Nút "Tiếp theo" */
    .btn-next {
      position: absolute;
      /* Đảm bảo vị trí tuyệt đối */
      right: 20px;
      /* Đẩy nút sát cạnh phải */
      bottom: 20px;
      /* Vẫn giữ vị trí ở phía dưới */
    }

    /* Nút "Huỷ Bỏ" */
    .btn-danger {
      margin-top: 5px;
      bottom: auto;
    }

    /* Căn giữa nút "Nộp bài" */
    .text-center .btn {
      margin: 0 auto;
      /* Căn giữa trong khung cha */
      vertical-align: middle;
    }

    .button-group {
      display: flex;
      /* Create a horizontal layout */
      justify-content: space-between;
      /* Ensure buttons are spaced as far apart as possible */
      align-items: center;
      /* Align buttons vertically */
      margin-top: 20px;
      /* Add some spacing above the button group */
    }

    .button-group .btn-center {
      margin: 0 auto;
      /* Center-align the middle button */
    }

    .d-flex {
      display: flex;
    }

    .justify-content-between {
      justify-content: space-between;
    }

    /* Thêm vào phần <style> */
    .btn-primary {
      margin-left: auto !important;
      /* Đẩy sát phải */
    }

    /* Đảm bảo nút "Tiếp theo" luôn sát phải */
    .flex-grow-1 {
      flex-grow: 1;
      /* Chiếm hết khoảng trống */
    }

    /* Thêm vào phần <style> */
    .table-responsive {
      overflow-x: auto;
    }

    .table {
      width: 100%;
      margin-bottom: 1rem;
      color: #212529;
      background-color: #fff;
      border-collapse: collapse;
    }

    .table th,
    .table td {
      padding: 0.75rem;
      vertical-align: top;
      border-top: 1px solid #dee2e6;
    }

    .table thead th {
      vertical-align: bottom;
      border-bottom: 2px solid #dee2e6;
    }

    .table-bordered {
      border: 1px solid #dee2e6;
    }

    .table-bordered th,
    .table-bordered td {
      border: 1px solid #dee2e6;
    }

    .table-dark {
      color: #fff;
      background-color: #343a40;
    }

    .badge {
      display: inline-block;
      padding: 0.25em 0.4em;
      font-size: 75%;
      font-weight: 700;
      line-height: 1;
      text-align: center;
      white-space: nowrap;
      vertical-align: baseline;
      border-radius: 0.25rem;
    }

    .bg-warning {
      background-color: #ffc107 !important;
    }

    .bg-success {
      background-color: #28a745 !important;
    }

    .bg-danger {
      background-color: #dc3545 !important;
    }

    .character-item {
      background-color: #f8f9fa;
      transition: all 0.3s ease;
    }

    .character-item:hover {
      background-color: #e9ecef;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Thêm vào phần style */
    .bio-preview {
      cursor: pointer;
      color: #7f8c8d;
      margin-top: 5px;
      transition: color 0.3s;
      max-width: 300px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .bio-preview:hover {
      color: #66b2ff;
    }

    .bio-preview small {
      font-size: 16px;
    }

    .bio-preview:hover {
      color: #66b2ff;
    }

    .bio-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.9);
      z-index: 1000;
      justify-content: center;
      align-items: flex-start;
      padding-top: 5%;
      animation: fadeIn 0.3s;
    }

    #bioFullText {
      white-space: pre-line;
      word-break: break-word;
      line-height: 1.7;
      color: #e0e0e0;
      font-size: 15px;
      padding: 10px 5px;
    }

    .bio-content {
      background-color: #2e2f31;
      padding: 25px;
      border-radius: 10px;
      width: 90%;
      max-width: 700px;
      max-height: 80vh;
      overflow-y: auto;
      overflow-x: hidden;
      box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
      transform: translateY(0);
      transition: transform 0.3s;
      border: 1px solid #444;
      transition: opacity 0.2s ease-out, transform 0.2s ease-out;
      will-change: opacity, transform;
      /* Tối ưu hiệu ứng */
    }

    .bio-content:hover {
      transform: translateY(-5px);
    }


    .bio-content h4 {
      color: #66b2ff;
      margin-bottom: 15px;
      border-bottom: 1px solid #444;
      padding-bottom: 10px;
    }

    .close-bio {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 24px;
      color: #ccc;
      cursor: pointer;
      transition: color 0.3s;
      transition: transform 0.2s, color 0.2s;
    }

    .close-bio:hover {
      transform: scale(1.1);
      color: #ff6b6b;
    }

    /* Thanh cuộn đẹp */
    .bio-content::-webkit-scrollbar {
      width: 6px;
    }

    .bio-content::-webkit-scrollbar-track {
      background: #2e2f31;
      border-radius: 10px;
    }

    .bio-content::-webkit-scrollbar-thumb {
      background: #66b2ff;
      border-radius: 3px;
    }

    .bio-content::-webkit-scrollbar-thumb:hover {
      background: #4d94ff;
    }

    #searchInput::placeholder {
      color: white;
      /* Đặt màu chữ placeholder thành màu trắng */
      opacity: 1;
      /* Đảm bảo độ trong suốt là 100% */
    }

    /* Hiệu ứng đóng/mở modal */
    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    /* Thêm vào phần style */
    .table-responsive {
      max-height: 70vh;
      overflow-y: auto;
    }

    .status-pending {
      color: #ffc107;
      font-weight: bold;
    }

    .status-approved {
      color: #28a745;
      font-weight: bold;
    }

    .status-rejected {
      color: #dc3545;
      font-weight: bold;
    }

    .action-btn {
      padding: 5px 10px;
      margin: 2px;
      font-size: 13px;
      min-width: 80px;
    }

    .badge-filter {
      cursor: pointer;
      margin-right: 5px;
    }

    #searchInput {
      background-color: #2e2f31;
      color: white;
      border: 1px solid #444;
    }

    #searchInput:focus {
      background-color: #2e2f31;
      color: white;
      border-color: #66b2ff;
      box-shadow: 0 0 0 0.25rem rgba(102, 178, 255, 0.25);
    }

    .logo {
      font-family: 'pricedown bl', sans-serif;
      font-size: 50px;
      text-align: center;
      margin-bottom: 20px;
    }

    a.btn-secondary {
      background-color: rgba(68, 81, 200, 0.78);
    }

    a.btn-secondary:hover {
      background-color: #4452c8;
      /* Màu nền khi hover */
      color: #ffffff;
      /* Màu chữ khi hover */
      transition: background-color 0.3s ease, color 0.3s ease;
      /* Hiệu ứng chuyển đổi */
    }

    .text-danger {
      color: #dc3545 !important;
      font-weight: bold;
    }

    #wordCount {
      display: block;
      margin-top: 5px;
      font-size: 0.875em;
    }

    .role-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
      text-transform: uppercase;
      animation: fadeIn 1s ease-in-out;
    }

    /* Developer Style */
    .role-badge.developer {
      background: none;
      /* Loại bỏ nền */
      color: #66b2ff;
      text-shadow: 0 0 5px #66b2ff, 0 0 10px #66b2ff, 0 0 20px #66b2ff, 0 0 40px #21a1f8, 0 0 80px #21a1f8;
      animation: neonGlowDeveloper 2s infinite alternate;
    }

    @keyframes neonGlowDeveloper {
      0% {
        text-shadow: 0 0 5px #66b2ff, 0 0 10px #66b2ff, 0 0 20px #66b2ff, 0 0 40px #21a1f8, 0 0 80px #21a1f8;
      }

      100% {
        text-shadow: 0 0 10px #66b2ff, 0 0 20px #66b2ff, 0 0 40px #21a1f8, 0 0 80px #21a1f8, 0 0 120px #21a1f8;
      }
    }

    /* Admin Style */
    .role-badge.admin {
      background: none;
      /* Loại bỏ nền */
      color: #ff4d4d;
      text-shadow: 0 0 5px #ff4d4d, 0 0 10px #ff4d4d, 0 0 20px #ff4d4d, 0 0 40px #ff6b6b, 0 0 80px #ff6b6b;
      animation: neonGlowAdmin 2s infinite alternate;
    }

    @keyframes neonGlowAdmin {
      0% {
        text-shadow: 0 0 5px #ff4d4d, 0 0 10px #ff4d4d, 0 0 20px #ff4d4d, 0 0 40px #ff6b6b, 0 0 80px #ff6b6b;
      }

      100% {
        text-shadow: 0 0 10px #ff4d4d, 0 0 20px #ff4d4d, 0 0 40px #ff6b6b, 0 0 80px #ff6b6b, 0 0 120px #ff6b6b;
      }
    }

    /* User Style */
    .role-badge.user {
      background: none;
      /* Loại bỏ nền */
      color: #34d058;
      text-shadow: 0 0 5px #34d058, 0 0 10px #34d058, 0 0 20px #34d058, 0 0 40px #28a745, 0 0 80px #28a745;
      animation: neonGlowUser 2s infinite alternate;
    }

    @keyframes neonGlowUser {
      0% {
        text-shadow: 0 0 5px #34d058, 0 0 10px #34d058, 0 0 20px #34d058, 0 0 40px #28a745, 0 0 80px #28a745;
      }

      100% {
        text-shadow: 0 0 10px #34d058, 0 0 20px #34d058, 0 0 40px #28a745, 0 0 80px #28a745, 0 0 120px #28a745;
      }
    }

    #characterBio {
      overflow-x: hidden !important;
      /* Ẩn thanh cuộn ngang */
      overflow-y: auto !important;
      /* Cho phép cuộn dọc nếu cần */
      resize: none;
      /* Tắt chức năng resize */
      word-wrap: break-word;
      /* Tự động ngắt dòng khi vượt quá chiều rộng */
      white-space: pre-wrap;
      /* Giữ nguyên các khoảng trắng và xuống dòng */
      width: 100%;
      /* Đảm bảo chiều rộng không vượt container */
    }

    #characterCreationSection .card-body {
      overflow-x: hidden;
      /* Ẩn tràn ngang */
    }
  </style>

  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

  <div class="user-dropdown-wrapper" onclick="toggleDropdown()">
    <div class="user-box">
      <img src="<?= htmlspecialchars($steamAvatar); ?>" alt="Steam Avatar" class="user-avatar">
      <div class="user-info">
        <strong><?= htmlspecialchars($steamName); ?></strong>
        <small class="role-badge <?= htmlspecialchars($steamRole); ?>"><?= htmlspecialchars($steamRole); ?></small>
      </div>
    </div>
    <div id="userDropdown" class="dropdown-menu-custom">
      <a href="/steamauth/logout.php">🚪 Đăng xuất</a>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-2 sidebar">
        <div class="p-3">
          <h2 class="logo">GTA <span style="color: #21a1f8;">STREET</span></h2>
        </div>
        <a href="user_dashboard.php" class="active">🏠 Home</a>
        <!-- Trong phần sidebar, thêm sự kiện onclick -->
        <a href="#" onclick="showCharacterData()">👫 Nhân Vật</a>
        <hr class="bg-light">
        <?php if (isset($_SESSION['steam_role']) && ($_SESSION['steam_role'] === 'admin' || $_SESSION['steam_role'] === 'developer')): ?>
          <h6 class="px-3">BẢNG ADMIN</h6>
          <a href="javascript:void(0)" onclick="showWhitelistSection()">👥 Kiểm duyệt whitelist</a>
        <?php endif; ?>
        <br>
        <?php if (isset($_SESSION['steam_role']) && $_SESSION['steam_role'] === 'developer'): ?>
          <h6 class="px-3">DEVELOPER</h6>
          <a href="javascript:void(0)" onclick="showRoleManagement()">⭕ Chỉnh Role</a>
        <?php endif; ?>
      </div>

      <!-- Main Content -->
      <div class="col-md-10 content">
        <h2>Welcome!</h2>

        <!-- Bảng developer -->
        <div id="roleManagementSection" style="display: none;">
          <div class="card my-3 card-custom">
            <div class="card-body">
              <h5 class="card-title">👥 Quản lý vai trò</h5>
              <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Steam ID</th>
                      <th>Vai trò</th>
                      <th>Hành động</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $result = $db->query("SELECT steamid, role FROM steam_users");
                    while ($user = $result->fetch_assoc()): ?>
                      <tr>
                        <td><?= htmlspecialchars($user['steamid']); ?></td>
                        <td>
                          <select class="form-select form-select-sm role-select" data-steamid="<?= htmlspecialchars($user['steamid']); ?>">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="developer" <?= $user['role'] === 'developer' ? 'selected' : ''; ?>>Developer</option>
                          </select>
                        </td>
                        <td>
                          <button class="btn btn-primary btn-sm" onclick="updateRole('<?= htmlspecialchars($user['steamid']); ?>')">Cập nhật</button>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- Admin --!- Chỉ hiển thị cho admin -->

        <div id="whitelistSection" style="display: none;">
          <div class="card my-3 card-custom">
            <div class="card-body">
              <h5 class="card-title">Danh sách đơn Whitelist
                <span class="badge bg-secondary" id="requestCount">0</span>
              </h5>

              <div class="row mb-3">
                <div class="col-md-4">
                  <input type="text" id="searchInput" class="form-control" placeholder="Tìm kiếm...">
                </div>
                <div class="col-md-4">
                  <select id="statusFilter" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="đang xử lý">Đang xử lý</option>
                    <option value="đã duyệt">Đã duyệt</option>
                    <option value="từ chối">Từ chối</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <button class="btn btn-secondary" onclick="refreshRequests()">⟳ Làm mới</button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                  <thead>
                    <tr>
                      <th width="5%">ID</th>
                      <th width="20%">Tên NV</th>
                      <th width="15%">Tiểu Sử</th>
                      <th width="15%">Ngày Sinh</th>
                      <th width="15%">SteamID</th>
                      <th width="20%">Ngày tạo</th>
                      <th width="15%">Trạng thái</th>
                      <th width="25%">Hành động</th>
                    </tr>
                  </thead>
                  <tbody id="whitelistTableBody">
                    <!-- Dữ liệu sẽ được load bằng AJAX -->
                  </tbody>
                </table>
              </div>

              <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center" id="pagination">
                  <!-- Phân trang sẽ được thêm tự động -->
                </ul>
              </nav>
            </div>
          </div>
        </div>

        <!-- Card Section -->
        <div id="characterDataSection" style="display: none;">
          <div class="card my-3 card-custom">
            <div class="card-body">
              <h5 class="card-title">Danh sách nhân vật (<?php
                                                          $count = $db->query("SELECT COUNT(*) as count FROM characters WHERE steamid = '" . $_SESSION['steamid'] . "'")->fetch_assoc()['count'];
                                                          echo $count . '/3';
                                                          ?>)</h5>

              <?php
              $steamid = $_SESSION['steamid'];
              $result = $db->query("SELECT * FROM characters WHERE steamid = '$steamid' ORDER BY id DESC");

              if ($result->num_rows > 0) {
                while ($character = $result->fetch_assoc()) {
                  echo '<div class="character-item mb-4 p-3 border rounded">';

                  echo '<p><strong>Họ và Tên</strong> : ' . htmlspecialchars($character['firstname'] . ' ' . $character['lastname']) . '</p>';
                  echo '<p><strong>Ngày sinh:</strong> ' . htmlspecialchars(date('d/m/Y', strtotime($character['birthdate']))) . '</p>';
                  echo '<p><strong>Giới tính:</strong> ' . htmlspecialchars($character['gender']) . '</p>';
                  echo '<p><strong>Tiểu sử:</strong> <span class="bio-preview" onclick="showFullBio(`' . htmlspecialchars($character['bio']) . '`, `' . htmlspecialchars($character['firstname'] . ' ' . $character['lastname']) . '`)">' .
                    (strlen($character['bio']) > 100 ? substr(htmlspecialchars($character['bio']), 0, 100) . '... [Xem thêm]' : htmlspecialchars($character['bio'])) .
                    '</span></p>';

                  // Hiển thị trạng thái với màu sắc khác nhau
                  $statusClass = 'text-warning';
                  if ($character['status'] == 'đã duyệt') {
                    $statusClass = 'text-success';
                  } elseif ($character['status'] == 'từ chối') {
                    $statusClass = 'text-danger';
                  }

                  echo '<p><strong>Trạng thái:</strong> <span class="' . $statusClass . '">' . htmlspecialchars($character['status']) . '</span></p>';

                  // Hiển thị lý do từ chối nếu trạng thái là "từ chối"
                  if ($character['status'] == 'từ chối' && !empty($character['reject_reason'])) {
                    echo '<p><strong>Lý do từ chối:</strong> ' . htmlspecialchars($character['reject_reason']) . '</p>';
                  }

                  echo '</div>';
                }
              } else {
                echo '<p class="text-danger">Bạn chưa tạo nhân vật nào!</p>';
              }
              ?>
            </div>
          </div>
        </div>
        <div id="cardSection">
          <!-- Card: Take the quiz -->

          <!-- Card: SA-MP Reopened -->
          <div class="card my-3 card-custom">
            <div class="card-body">
              <h5 class="card-title">📢 GTA STREET CHÍNH THỨC MỞ LẠI </h5>
              <p class="card-text">
                Xin chào tất cả mọi người,
                máy chủ GTA STREET đã chính thức hoạt động trở lại!
                Đây là một cột mốc quan trọng sau khoảng thời gian bảo trì và nâng cấp.
                Toàn bộ account của người chơi sẽ được giữ nguyên, không bị xóa, không reset nhân vật, tài sản, hay lịch sử, đảm bảo tính liên tục trong hành trình của bạn! .<br>
                Hẹn gặp mọi người trong thành phố!
              </p>
            </div>
          </div>

          <div class="card my-3 card-custom">
            <div class="card-body">
              <h5 class="card-title text-warning">⚠️ Câu hỏi trắc nghiệm!</h5>
              <p class="card-text">Bạn cần trả lời các câu hỏi trắc nghiệm trước khi bước vào GTA STREET.</p>
              <button class="btn btn-primary" onclick="startQuiz()">Làm trắc nghiệm !</button>
            </div>
          </div>

          <!-- Card: Verify email -->
          <div class="card my-3 card-custom">
            <div class="card-body">
              <h5 class="card-title">🔷 Cộng Đồng GTA STREET</h5>
              <p class="card-text">Hãy truy cập cộng đồng của chúng tôi qua nền tảng Discord.</p>
              <a href="https://discord.gg/gtastreet" class="btn btn-secondary" target="_blank;">Discord</a>
            </div>
          </div>

        </div>

        <!-- Quiz Section -->
        <div id="quizSection" style="display: none;">
          <div class="card my-3 card-custom">
            <div class="card-body">
              <h5 class="card-title text-info">📝 Roleplay Quiz</h5>
              <form id="quizForm">
                <?php
                $questions = getRandomQuestions($db);
                $questionsPerPage = 10;
                $pageCount = ceil(count($questions) / $questionsPerPage);

                for ($page = 1; $page <= $pageCount; $page++):
                ?>
                  <div id="quizPage<?= $page ?>" style="display: <?= $page === 1 ? 'block' : 'none' ?>;">
                    <?php
                    $start = ($page - 1) * $questionsPerPage;
                    $end = min($start + $questionsPerPage, count($questions));

                    for ($i = $start; $i < $end; $i++):
                      $q = $questions[$i];
                    ?>
                      <p><?= ($i + 1) ?>. <?= htmlspecialchars($q['question']) ?></p>
                      <input type="radio" name="answers[<?= $q['id'] ?>]" value="a"> <?= htmlspecialchars($q['option_a']) ?><br>
                      <input type="radio" name="answers[<?= $q['id'] ?>]" value="b"> <?= htmlspecialchars($q['option_b']) ?><br>
                      <input type="radio" name="answers[<?= $q['id'] ?>]" value="c"> <?= htmlspecialchars($q['option_c']) ?><br>
                      <input type="radio" name="answers[<?= $q['id'] ?>]" value="d"> <?= htmlspecialchars($q['option_d']) ?><br><br>
                    <?php endfor; ?>

                    <div class="button-group">
                      <?php if ($page > 1): ?>
                        <button type="button" class="btn btn-secondary" onclick="showQuizPage(<?= $page - 1 ?>)">Quay lại</button>
                      <?php endif; ?>

                      <?php if ($page < $pageCount): ?>
                        <div class="d-flex w-100"> <!-- Thêm w-100 để chiếm full width -->
                          <button type="button" class="btn btn-danger" onclick="cancelQuiz()">Huỷ Bỏ</button>
                          <div class="flex-grow-1"></div> <!-- Khoảng trống giữa 2 nút -->
                          <button type="button" class="btn btn-primary" onclick="showQuizPage(<?= $page + 1 ?>)">Tiếp theo</button>
                        </div>
                      <?php else: ?>
                        <button type="submit" id="submitQuiz" class="btn btn-success">Nộp bài</button>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endfor; ?>
              </form>
            </div>
          </div>
        </div>

        <!-- Character Creation Section -->
        <div id="characterCreationSection" style="display: none;">
          <div class="card my-3 card-custom">
            <div class="card-body">
              <h5 class="card-title text-info">📝 Tạo nhân vật</h5>
              <form id="characterForm">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="characterFirstname" class="form-label">Họ</label>
                    <input type="text" class="form-control" id="characterFirstname" name="firstname" required>
                  </div>
                  <div class="col-md-6">
                    <label for="characterLastname" class="form-label">Tên</label>
                    <input type="text" class="form-control" id="characterLastname" name="lastname" required>
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="characterBirthdate" class="form-label">Ngày sinh</label>
                    <input type="date" class="form-control" id="characterBirthdate" name="birthdate" required
                      max="<?php echo date('Y-m-d', strtotime('-16 years')); ?>">
                    <small class="text-muted">Nhân vật phải ít nhất 16 tuổi</small>
                  </div>
                  <div class="col-md-6">
                    <label for="characterGender" class="form-label">Giới tính</label>
                    <select class="form-control" id="characterGender" name="gender" required>
                      <option value="Nam">Nam</option>
                      <option value="Nữ">Nữ</option>
                    </select>
                  </div>
                </div>
                <div class="mb-3">
                  <label for="characterBio" class="form-label">Tiểu sử (tối đa 2000 từ)</label>
                  <textarea class="form-control" id="characterBio" name="bio" rows="3" required
                    oninput="countWords(this)"></textarea>
                  <small id="wordCount" class="text-muted">Số từ: 0/2000</small>
                </div>
                <button type="submit" class="btn btn-primary">Tạo nhân vật</button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
    <div id="bioModal" class="bio-modal">
      <div class="bio-content" style="opacity: 0; transform: translateY(-20px);">
        <span class="close-bio" onclick="closeBioModal()">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ccc">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </span>
        <h4 id="bioTitle" style="color: #66b2ff; margin-bottom: 15px;"></h4>
        <div id="bioFullText"></div>
      </div>
    </div>

    <div class="modal fade" id="characterDetailModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
          <div class="modal-header">
            <h5 class="modal-title">Chi tiết nhân vật</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Nội dung sẽ được load bằng AJAX -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal nhập lý do từ chối -->
  <div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title">Nhập lý do từ chối</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="rejectReasonForm">
            <input type="hidden" id="rejectRequestId" name="id">
            <div class="mb-3">
              <label for="rejectReason" class="form-label">Lý do từ chối (tối đa 250 ký tự)</label>
              <textarea class="form-control" id="rejectReason" name="reason" rows="3" maxlength="250" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Xác nhận từ chối</button>
          </form>
        </div>
      </div>
    </div>
  </div>

</body>

<!-- Thêm SweetAlert và jQuery -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
  // Thêm vào phần script
  function countWords(textarea) {
    const text = textarea.value.trim();
    const wordCount = text ? text.split(/\s+/).filter(word => word.length > 0).length : 0; // Đếm từ chính xác hơn
    const counter = document.getElementById('wordCount');

    counter.textContent = `Số từ: ${wordCount}/2000`;

    if (wordCount > 2000) {
      counter.classList.add('text-danger');
      counter.classList.remove('text-muted');
      document.querySelector('button[type="submit"]').disabled = true;
    } else {
      counter.classList.remove('text-danger');
      counter.classList.add('text-muted');
      document.querySelector('button[type="submit"]').disabled = false;
    }
  }

  // Thêm validation khi submit form
  document.getElementById('characterForm').addEventListener('submit', function(e) {
    const bioText = document.getElementById('characterBio').value.trim();
    const wordCount = bioText ? bioText.split(/\s+/).length : 0;

    if (wordCount > 2000) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Lỗi',
        text: 'Tiểu sử không được vượt quá 2000 từ!'
      });
    }
  });

  // Thêm vào phần script
  document.getElementById('characterBirthdate').addEventListener('change', function() {
    const birthdate = new Date(this.value);
    const today = new Date();
    const minAgeDate = new Date(today.getFullYear() - 16, today.getMonth(), today.getDate());

    if (birthdate > minAgeDate) {
      Swal.fire({
        icon: 'error',
        title: 'Lỗi',
        text: 'Nhân vật phải ít nhất 16 tuổi'
      });
      this.value = '';
    }
  });

  // Gửi lý do từ chối qua AJAX
  $('#rejectReasonForm').submit(function(e) {
    e.preventDefault();

    const id = $('#rejectRequestId').val();
    const reason = $('#rejectReason').val();

    $.ajax({
      url: 'process_whitelist.php',
      type: 'POST',
      data: {
        id: id,
        action: 'reject',
        reason: reason
      },
      success: function(response) {
        const data = JSON.parse(response);
        if (data.success) {
          Swal.fire('Thành công', 'Đơn đã bị từ chối.', 'success');
          $('#rejectReasonModal').modal('hide');
          loadWhitelistRequests(currentPage);
        } else {
          Swal.fire('Lỗi', data.message, 'error');
        }
      }
    });
  });

  $('#characterForm').submit(function(e) {
    e.preventDefault();

    $.ajax({
      url: 'create_character.php',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Thành công',
            text: response.message,
            timer: 1500
          }).then(() => {
            $('#characterForm')[0].reset();
            $('#characterCreationSection').hide();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: response.message || 'Có lỗi xảy ra'
          });
        }
      },
      error: function(xhr) {
        let errorMsg = 'Lỗi hệ thống';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        Swal.fire({
          icon: 'error',
          title: 'Lỗi ' + xhr.status,
          text: errorMsg
        });
      }
    });
  });

  // Hiển thị tiểu sử đầy đủ
  function showFullBio(bio, firstname, lastname) {
    const modal = document.getElementById('bioModal');
    modal.style.display = 'flex';

    // Kích hoạt reflow để CSS transition hoạt động
    void modal.offsetWidth;

    document.getElementById('bioTitle').textContent = 'Tiểu sử nhân vật: ' + firstname + ' ' + lastname;
    document.getElementById('bioFullText').textContent = bio;

    modal.querySelector('.bio-content').style.opacity = '1';
    modal.querySelector('.bio-content').style.transform = 'translateY(0)';
  }

  function closeBioModal() {
    const modal = document.getElementById('bioModal');
    const content = modal.querySelector('.bio-content');

    content.style.opacity = '0';
    content.style.transform = 'translateY(-20px)';

    modal.addEventListener('transitionend', function handler() {
      modal.style.display = 'none';
      modal.removeEventListener('transitionend', handler);
    }, {
      once: true
    });
  }

  // Đóng khi click bên ngoài
  document.addEventListener('click', function(e) {
    const modal = document.getElementById('bioModal');
    if (e.target === modal) {
      closeBioModal();
    }
  });

  function showAdminWhitelistSection() {
    // Ẩn tất cả các section khác
    document.getElementById('cardSection').style.display = 'none';
    document.getElementById('quizSection').style.display = 'none';
    document.getElementById('characterDataSection').style.display = 'none';
    document.getElementById('characterCreationSection').style.display = 'none';

    // Hiển thị section admin whitelist
    document.getElementById('adminWhitelistSection').style.display = 'block';

    // Load dữ liệu whitelist
    loadWhitelistRequests();
  }

  function showRoleManagement() {
    // Ẩn tất cả sections
    const sections = ['cardSection', 'quizSection', 'characterDataSection', 'whitelistSection'];
    sections.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = 'none';
    });

    // Hiển thị role management
    const roleSection = document.getElementById('roleManagementSection');
    if (roleSection) {
      roleSection.style.display = 'block';
      console.log("Role section displayed"); // Kiểm tra trong Console
    } else {
      console.error("Role section not found");
    }
  }

  function updateRole(steamid) {
    const role = document.querySelector(`.role-select[data-steamid="${steamid}"]`).value;

    $.ajax({
      url: 'user_dashboard.php',
      type: 'POST',
      data: {
        action: 'updateRole',
        steamid,
        role
      },
      success: function(response) {
        const data = JSON.parse(response);
        if (data.success) {
          Swal.fire('Thành công', 'Vai trò đã được cập nhật.', 'success');
        } else {
          Swal.fire('Lỗi', data.message, 'error');
        }
      },
      error: function() {
        Swal.fire('Lỗi', 'Không thể cập nhật vai trò.', 'error');
      }
    });
  }

  let currentPage = 1;
  const itemsPerPage = 10;

  function loadWhitelistRequests(page = 1) {
    currentPage = page;
    const searchQuery = $('#searchInput').val();
    const statusFilter = $('#statusFilter').val();

    console.log("Loading whitelist requests with:", {
      page,
      search: searchQuery,
      status: statusFilter
    });

    $.ajax({
      url: 'get_whitelist_requests.php',
      type: 'GET',
      data: {
        page: page,
        per_page: itemsPerPage,
        search: searchQuery,
        status: statusFilter
      },
      success: function(response) {
        console.log("Raw response:", response);
        try {
          const data = typeof response === 'string' ? JSON.parse(response) : response;
          console.log("Parsed data:", data);

          if (data.error) {
            console.error("Error from server:", data.error);
            Swal.fire('Lỗi', data.error, 'error');
            return;
          }

          renderRequests(data.requests);
          renderPagination(data.total, page);
          $('#requestCount').text(data.total);
        } catch (e) {
          console.error("JSON parse error:", e, "Response:", response);
          Swal.fire('Lỗi', 'Dữ liệu trả về không hợp lệ', 'error');
        }
      },
      error: function(xhr, status, error) {
        console.error("AJAX error:", status, error);
        Swal.fire('Lỗi', 'Không thể tải dữ liệu whitelist', 'error');
      }
    });
  }

  function renderRequests(requests) {
    const tbody = $('#whitelistTableBody');
    tbody.empty();

    if (requests.length === 0) {
      tbody.append('<tr><td colspan="7" class="text-center">Không có đơn nào</td></tr>');
      return;
    }

    requests.forEach(request => {
      const statusClass = request.status === 'đã duyệt' ? 'status-approved' :
        request.status === 'từ chối' ? 'status-rejected' : 'status-pending';

      // Tạo preview bio (giới hạn 10 ký tự)
      const bioPreview = request.bio.length > 10 ?
        request.bio.substring(0, 10) + '...' :
        request.bio;

      const row = `
      <tr>
          <td>${request.id}</td>
          <td><strong>${request.firstname} ${request.lastname}</strong></td>
          <td>
              <div class="bio-preview" onclick="showFullBio('${escapeHtml(request.bio)}', '${escapeHtml(request.firstname)}', '${escapeHtml(request.lastname)}')">
                  <small>${escapeHtml(bioPreview)}</small>
                  ${request.bio.length > 10 ? '<span class="text-primary"></span>' : ''}
              </div>
          </td>
          <td><small>${request.birthdate}</small></td>
          <td><small>${request.steamid}</small></td>
          <td><small>${request.created_at}</small></td>
          <td><span class="${statusClass}">${request.status}</span></td>
          <td>
              <div class="btn-group">
                  <button onclick="processRequest(${request.id}, 'approve')" 
                          class="btn btn-sm btn-success action-btn" ${request.status === 'đã duyệt' ? 'disabled' : ''}>
                      <i class="fas fa-check"></i> Duyệt
                  </button>
                  <button onclick="processRequest(${request.id}, 'reject')" 
                          class="btn btn-sm btn-danger action-btn" ${request.status === 'từ chối' ? 'disabled' : ''}>
                      <i class="fas fa-times"></i> Từ chối
                  </button>
                  <button onclick="processRequest(${request.id}, 'pending')" 
                          class="btn btn-sm btn-warning action-btn" ${request.status === 'đang xử lý' ? 'disabled' : ''}>
                      <i class="fas fa-clock"></i> Xử lý
                  </button>
                  <button onclick="deleteRequest(${request.id})" 
                          class="btn btn-sm btn-secondary action-btn">
                      <i class="fas fa-trash"></i> Xóa
                  </button>
              </div>
          </td>
      </tr>`;
      tbody.append(row);
    });
  }

  // Thêm hàm escapeHtml để xử lý các ký tự đặc biệt trong bio
  function escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;")
      .replace(/\n/g, "<br>");
  }

  function renderPagination(totalItems, currentPage) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const pagination = $('#pagination');
    pagination.empty();

    if (totalPages <= 1) return;

    // Nút Previous
    pagination.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadWhitelistRequests(${currentPage - 1})">&laquo;</a>
        </li>
    `);

    // Các trang
    for (let i = 1; i <= totalPages; i++) {
      pagination.append(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadWhitelistRequests(${i})">${i}</a>
            </li>
        `);
    }

    // Nút Next
    pagination.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadWhitelistRequests(${currentPage + 1})">&raquo;</a>
        </li>
    `);
  }

  // Hiển thị modal nhập lý do từ chối
  function processRequest(id, action) {
    if (action === 'reject') {
      // Hiển thị modal nhập lý do từ chối
      $('#rejectRequestId').val(id);
      $('#rejectReason').val(''); // Reset giá trị cũ
      $('#rejectReasonModal').modal('show');
    } else {
      // Xử lý các hành động khác (duyệt, xử lý)
      Swal.fire({
        title: 'Xác nhận',
        text: `Bạn chắc chắn muốn ${action === 'approve' ? 'duyệt' : 'chuyển sang xử lý'} đơn này?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Xác nhận',
        cancelButtonText: 'Hủy'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: 'process_whitelist.php',
            type: 'POST',
            data: {
              id: id,
              action: action
            },
            success: function(response) {
              const data = JSON.parse(response);
              if (data.success) {
                Swal.fire('Thành công', data.message, 'success');
                loadWhitelistRequests(currentPage);
              } else {
                Swal.fire('Lỗi', data.message, 'error');
              }
            }
          });
        }
      });
    }
  }

  function deleteRequest(id) {
    Swal.fire({
      title: 'Xóa đơn whitelist',
      text: 'Bạn chắc chắn muốn xóa đơn này?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: 'delete_whitelist.php',
          type: 'POST',
          data: {
            id: id
          },
          success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
              Swal.fire('Đã xóa', data.message, 'success');
              loadWhitelistRequests(currentPage);
            } else {
              Swal.fire('Lỗi', data.message, 'error');
            }
          }
        });
      }
    });
  }

  function refreshRequests() {
    loadWhitelistRequests(currentPage);
  }

  function showWhitelistSection() {
    // Ẩn tất cả các section khác
    document.getElementById('cardSection').style.display = 'none';
    document.getElementById('quizSection').style.display = 'none';
    document.getElementById('characterDataSection').style.display = 'none';
    document.getElementById('characterCreationSection').style.display = 'none';

    // Hiển thị section whitelist
    const whitelistSection = document.getElementById('whitelistSection');
    whitelistSection.style.display = 'block';

    // Load dữ liệu
    loadWhitelistRequests();

    // Cuộn đến section
    whitelistSection.scrollIntoView({
      behavior: 'smooth'
    });
  }

  // Tìm kiếm và filter
  $('#searchInput, #statusFilter').on('change keyup', function() {
    loadWhitelistRequests(1);
  });

  // Hiển thị chi tiết nhân vật trong modal
  function showCharacterDetail(id) {
    $.ajax({
      url: 'get_character_detail.php',
      type: 'GET',
      data: {
        id: id
      },
      success: function(response) {
        const data = JSON.parse(response);
        $('#characterDetailModal .modal-body').html(`
                <p><strong>Họ và Tên:</strong> ${data.firstname} ${data.lastname}</p>
                <p><strong>SteamID:</strong> ${data.steamid}</p>
                <p><strong>Giới tính:</strong> ${data.gender}</p>
                <p><strong>Trạng thái:</strong> <span class="${data.status === 'đã duyệt' ? 'status-approved' : 
                  data.status === 'từ chối' ? 'status-rejected' : 'status-pending'}">${data.status}</span></p>
                <hr>
                <h5>Tiểu sử</h5>
                <div class="bio-content p-3">${data.bio.replace(/\n/g, '<br>')}</div>
            `);
        $('#characterDetailModal').modal('show');
      }
    });
  }

  // Toggle dropdown user info
  function toggleDropdown() {
    const menu = document.getElementById('userDropdown');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
  }

  function showCharacterCreation() {
    // Ẩn tất cả các section khác
    document.getElementById('cardSection').style.display = 'none';
    document.getElementById('quizSection').style.display = 'none';
    document.getElementById('characterDataSection').style.display = 'none';
    document.getElementById('whitelistSection').style.display = 'none'; // Thêm dòng này

    // Hiển thị section tạo nhân vật
    document.getElementById('characterCreationSection').style.display = 'block';
  }

  // Thêm vào phần script
  function showCharacterData() {
    // Ẩn tất cả các section khác
    document.getElementById('cardSection').style.display = 'none';
    document.getElementById('quizSection').style.display = 'none';
    document.getElementById('characterCreationSection').style.display = 'none';
    document.getElementById('whitelistSection').style.display = 'none'; // Thêm dòng này để ẩn phần kiểm duyệt

    // Hiển thị section dữ liệu nhân vật
    document.getElementById('characterDataSection').style.display = 'block';
  }
  // Đóng dropdown nếu click ra ngoài
  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const wrapper = document.querySelector('.user-dropdown-wrapper');
    if (!wrapper.contains(event.target)) {
      dropdown.style.display = 'none';
    }
  });

  // Bắt đầu quiz
  function startQuiz() {
    document.getElementById('cardSection').style.display = 'none';
    document.getElementById('characterDataSection').style.display = 'none';
    document.getElementById('characterCreationSection').style.display = 'none';
    document.getElementById('whitelistSection').style.display = 'none'; // Thêm dòng này
    document.getElementById('quizSection').style.display = 'block';
    showQuizPage(1); // reset về trang đầu
    window.scrollTo(0, document.body.scrollHeight); // cuộn xuống
  }

  // Hủy quiz
  function cancelQuiz() {
    // 1. Ẩn quiz, hiện lại card
    document.getElementById('cardSection').style.display = 'block';
    document.getElementById('quizSection').style.display = 'none';

    // 2. Reset tất cả radio buttons
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
      radio.checked = false;
    });

    // 3. Về trang 1
    showQuizPage(1);
  }

  // Hiện trang quiz theo số
  function showQuizPage(page) {
    // Ẩn tất cả các trang
    document.querySelectorAll('[id^="quizPage"]').forEach(el => {
      el.style.display = 'none';
    });

    // Hiển thị trang được chọn
    document.getElementById(`quizPage${page}`).style.display = 'block';

    // Cuộn lên đầu trang
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  }

  // Xử lý khi nộp bài
  document.getElementById("quizForm").addEventListener("submit", function(e) {
    e.preventDefault();

    // Lấy danh sách ID câu hỏi từ PHP
    const questionIds = <?= json_encode(array_column($questions, 'id')) ?>;

    // Kiểm tra số lượng câu đã trả lời
    let answeredCount = 0;
    questionIds.forEach(id => {
      const answer = document.querySelector(`input[name="answers[${id}]"]:checked`);
      if (answer) {
        answeredCount++;
      }
    });

    // Kiểm tra xem đã trả lời đủ 2 câu chưa
    if (answeredCount < 2) {
      Swal.fire({
        icon: 'warning',
        title: 'Thiếu câu trả lời!',
        text: `Bạn cần trả lời đủ 2 câu. Hiện tại bạn đã trả lời ${answeredCount} câu.`,
        confirmButtonText: 'OK'
      });
      return;
    }

    // Gửi yêu cầu AJAX đến server
    fetch('submit_quiz.php', {
        method: 'POST',
        body: new FormData(this)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          if (data.passed) {
            // Nếu trả lời đúng cả 2 câu
            Swal.fire({
              icon: "success",
              title: "Chúc mừng!",
              text: `Bạn đã hoàn thành với ${data.correct}/${data.total} câu đúng!`,
              confirmButtonText: "OK",
            }).then(() => {
              // Ẩn phần quiz
              document.getElementById('quizSection').style.display = 'none';
              // Hiển thị phần tạo nhân vật
              document.getElementById('characterCreationSection').style.display = 'block';
            });
          } else {
            // Nếu trả lời sai, hiển thị kết quả chi tiết
            showQuizResults(data.questions);
          }
        } else {
          Swal.fire({
            icon: "error",
            title: "Lỗi",
            text: "Đã xảy ra lỗi trong quá trình nộp bài. Vui lòng thử lại!",
            confirmButtonText: "OK"
          });
        }
      })
      .catch(err => {
        console.error("Lỗi khi gửi dữ liệu: ", err);
        Swal.fire({
          icon: "error",
          title: "Lỗi",
          text: "Đã xảy ra lỗi trong quá trình nộp bài. Vui lòng thử lại!",
          confirmButtonText: "OK"
        });
      });
  });

  function showQuizResults(questions) {
    const quizSection = document.getElementById('quizSection');
    quizSection.innerHTML = `
        <div class="card my-3 card-custom">
            <div class="card-body">
                <h5 class="card-title text-info">📝 Kết quả bài kiểm tra</h5>
                <div class="alert alert-danger">
                    Bạn đã trả lời sai một số câu hỏi. Vui lòng xem lại các câu trả lời dưới đây:
                </div>
                ${questions.map((q, index) => `
                    <div class="question-result mb-4 p-3 border rounded ${q.is_correct ? 'border-success' : 'border-danger'}">
                        <h6><strong>Câu ${index + 1}:</strong> ${q.question}</h6>
                        <div class="options">
                            ${Object.entries(q.options).map(([key, value]) => `
                                <div class="form-check ${getOptionClass(q, key)}">
                                    <input class="form-check-input" type="radio" name="answers[${q.id}]" 
                                        value="${key}" ${q.user_answer === key ? 'checked' : ''} disabled>
                                    <label class="form-check-label">
                                        ${value}
                                        ${q.correct_answer === key ? ' <i class="fas fa-check-circle text-success"></i>' : ''}
                                        ${!q.is_correct && q.user_answer === key ? ' <i class="fas fa-times-circle text-danger"></i>' : ''}
                                    </label>
                                </div>
                            `).join('')}
                        </div>
                        ${!q.is_correct ? `
                            <div class="explanation mt-3 p-2 bg-light text-dark rounded">
                                <strong>Giải thích:</strong> ${q.explanation}
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
                <div class="text-center mt-4">
                    <button class="btn btn-primary" onclick="location.reload()">Làm lại bài kiểm tra</button>
                </div>
            </div>
        </div>
    `;
  }

  // Hàm xác định class CSS cho các option
  function getOptionClass(question, optionKey) {
    if (question.correct_answer === optionKey) {
      return 'text-success fw-bold';
    }
    if (!question.is_correct && question.user_answer === optionKey) {
      return 'text-danger fw-bold';
    }
    return '';
  }
</script>


</body>

</html>