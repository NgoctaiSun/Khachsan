<?php
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin', 'letan']);
$message = '';

// 1. Xử lý lưu form POST trước khi SELECT dữ liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'them_nguoi_dung') {
    $hoten   = trim($_POST['hoten'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $matkhau = $_POST['matkhau'] ?? '';
    $sdt     = trim($_POST['sdt'] ?? '');
    $diachi  = trim($_POST['diachi'] ?? '');

    if ($hoten === '' || $email === '' || $matkhau === '') {
        $message = '<script>alert("Vui lòng nhập đầy đủ họ tên, email và mật khẩu.");</script>';
    } else {
        // Kiểm tra xem email hoặc họ tên đã tồn tại chưa
        $check = $conn->prepare("SELECT id FROM taikhoan WHERE email = ? OR hoten = ? LIMIT 1");
        $check->bind_param("ss", $email, $hoten);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            $message = '<script>alert("Email hoặc tên người dùng đã tồn tại!");</script>';
        } else {
            $hash   = password_hash($matkhau, PASSWORD_DEFAULT);
            $role   = 'khachhang';
            $status = 'hoatdong';

            $stmt = $conn->prepare("INSERT INTO taikhoan(hoten, matkhau, sdt, diachi, email, ngaytao, trangthai, vaitro) VALUES(?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->bind_param("sssssss", $hoten, $hash, $sdt, $diachi, $email, $status, $role);

            if ($stmt->execute()) {
                // Tải lại trang để cập nhật danh sách ngay lập tức
                echo "<script>alert('Thêm người dùng mới thành công!'); window.location.href=window.location.href;</script>";
                exit;
            } else {
                $message = '<script>alert("Lỗi SQL: ' . addslashes($stmt->error) . '");</script>';
            }
        }
    }
}

// 2. Truy vấn danh sách hiển thị ra bảng
// Số tài khoản hiển thị mỗi trang
$limit = 10;

// Lấy số trang hiện tại từ URL
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;

// Không cho phép trang nhỏ hơn 1
if ($page < 1) {
    $page = 1;
}

// Xác định vị trí bắt đầu lấy dữ liệu
$start = ($page - 1) * $limit;
$sql_count = "SELECT COUNT(*) AS total FROM taikhoan WHERE vaitro = 'khachhang'";

$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);

$total_records = $row_count['total'];

// Tính tổng số trang
$total_pages = ceil($total_records / $limit);
$sql = "SELECT * FROM taikhoan WHERE vaitro = 'khachhang' LIMIT $start, $limit";
$result = mysqli_query($conn, $sql);

// Thống kê tổng số khách hàng, khách hàng hoạt động và khách hàng bị khóa
$countResult = $conn->query("
    SELECT
      COUNT(*) total,
      SUM(trangthai='hoatdong') active,
      SUM(trangthai='khoa') blocked
    FROM taikhoan
    WHERE vaitro = 'khachhang'
");
$stats = $countResult->fetch_assoc();
// 3. Xử lý tìm kiếm

// Lấy từ khóa từ ô input có name="q"
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

// Điều kiện mặc định (Ví dụ: chỉ lấy tài khoản có vai trò là khách hàng)
$where_clauses = ["vaitro = 'khachhang'"]; 
$params = [];
$types = "";

// Nếu người dùng có nhập từ khóa tìm kiếm
if ($keyword !== '') {
    // Lưu ý: Sửa lại tên 3 cột (hoten, email, sdt) cho khớp với database của bạn
    $where_clauses[] = "(hoten LIKE ? OR email LIKE ? OR sdt LIKE ?)";
    $search_term = "%$keyword%";
    
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss"; // 3 tham số kiểu chuỗi (string)
}

$sql_where = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Truy vấn danh sách khách hàng (Thay `taikhoan` bằng tên bảng của bạn nếu khác)
$sql = "SELECT * FROM taikhoan" . $sql_where . " ORDER BY id DESC";

if (count($params) > 0) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($conn, $sql);
}
?>


<?php echo $message; ?>

<div class="hh-page">
    <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL / ADMIN</span>
            <h1>Quản lý người dùng</h1>
            <p>Theo dõi tình trạng và thông tin từng người dùng trong khách sạn.</p>
        </div>
        <button class="hh-btn hh-btn-primary" id="btn" class="btn-add">+ Thêm người mới</button>
    </div>
    <div class="hh-stat-grid">
        <div class="hh-stat-card"><span>Tổng số khách hàng</span><strong><?= (int)$stats['total'] ?></strong><small>Toàn hệ thống</small></div>
        <div class="hh-stat-card"><span>Khách hàng hoạt động</span><strong><?= (int)$stats['active'] ?></strong><small>Đang hoạt động</small></div>
        <div class="hh-stat-card"><span>Khách hàng bị khóa</span><strong><?= (int)$stats['blocked'] ?></strong><small>Đang bị khóa</small></div>
    </div>


    <!-- Modal Popup Form -->
    <div id="popup">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Thêm người dùng mới</h3>
                <button type="button" class="close-btn" id="btnCloseX">&times;</button>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="them_nguoi_dung">

                <div class="form-group">
                    <label>Họ và tên *</label>
                    <input class="form-control" type="text" name="hoten" required placeholder="Nhập họ và tên">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input class="form-control" type="email" name="email" required placeholder="example@gmail.com">
                </div>

                <div class="form-group">
                    <label>Mật khẩu *</label>
                    <input class="form-control" type="password" name="matkhau" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                </div>

                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input class="form-control" type="text" name="sdt" placeholder="Nhập số điện thoại">
                </div>

                <div class="form-group">
                    <label>Địa chỉ</label>
                    <input class="form-control" type="text" name="diachi" placeholder="Nhập địa chỉ">
                </div>

                <div class="popup-actions">
                    <button type="button" class="btn-cancel" id="btnClose">Hủy</button>
                    <button type="submit" class="btn-submit">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>
    <section>
        <div class="hh-card-heading">
            <div>
                <h2>Danh sách khách hàng</h2>
            </div>
        </div>
        <form method="GET" action="admin.php" class="hh-filter-bar">
            <!-- Giữ lại tên page hiện tại trên URL để không bị đá ra trang chủ khi submit -->
            <?php if (isset($_GET['page'])): ?>
                <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
            <?php endif; ?>

            <input type="text"
                name="q"
                placeholder="Nhập tên, email hoặc số điện thoại..."
                value="<?= htmlspecialchars($keyword) ?>">

            <button type="submit" class="hh-btn hh-btn-primary">Lọc dữ liệu</button>

            <!-- Nút xóa bộ lọc, chỉ hiện khi đang có từ khóa tìm kiếm -->
            <?php if ($keyword !== ''): ?>
                <a href="admin.php<?= isset($_GET['page']) ? '?page=' . urlencode($_GET['page']) : '' ?>" class="hh-btn">Đặt lại</a>
            <?php endif; ?>
        </form>
        <div class="hh-table-wrap">
            <table class="hh-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Địa chỉ</th>
                        <th>Email</th>
                        <th>Ngày đăng ký</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['hoten'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['sdt'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['diachi'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ngaytao'] ?? '') ?></td>
                            <td><span class="hh-status hh-status-<?= $row['trangthai'] === 'hoatdong' ? 'green' : ($row['trangthai'] === 'khoa' ? 'red' : 'gold') ?>"><?= htmlspecialchars($row['trangthai'] ?? '') ?></span></td>
                            <td>
                                <?php if (($row['trangthai'] ?? '') === 'khoa'): ?>
                                    <a href="process_blockUser.php?id=<?= $row['id'] ?>&trangthai=hoatdong"
                                        onclick="return confirm('Bạn có chắc chắn muốn MỞ KHÓA tài khoản này?')"
                                        style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                                        Mở khóa
                                    </a>
                                <?php else: ?>
                                    <a href="process_blockUser.php?id=<?= $row['id'] ?>&trangthai=khoa"
                                        onclick="return confirm('Bạn có chắc chắn muốn KHÓA tài khoản này?')"
                                        style="background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                                        Khóa
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
    <!-- Bảng danh sách người dùng -->

</div>

<div class="pagination">

    <?php if ($page > 1) { ?>
        <a href="?page=userManagement&page_num=<?= $page - 1 ?>">
            Trước
        </a>
    <?php } ?>

    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
        <a href="?page=userManagement&page_num=<?= $i ?>"
            class="<?= ($page == $i) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php } ?>

    <?php if ($page < $total_pages) { ?>
        <a href="?page=userManagement&page_num=<?= $page + 1 ?>">
            Sau
        </a>
    <?php } ?>

</div>