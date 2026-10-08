<?php 
require_once __DIR__ . '/../connect.php';
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
                $message = '<script>alert("Lỗi SQL: '.addslashes($stmt->error).'");</script>';
            }
        }
    }
}

// 2. Truy vấn danh sách hiển thị ra bảng
$sql = "SELECT * FROM taikhoan WHERE vaitro = 'khachhang'";
$result = mysqli_query($conn, $sql);
?>

<?php echo $message; ?>

<div>
    <h1>Quản lý người dùng</h1>
    <button id="btn" class="btn-add">+ Thêm người mới</button>

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

    <!-- Bảng danh sách người dùng -->
    <div>
        <table class="admin__table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên khách hàng</th>
                    <th>Sđt</th>
                    <th>Địa chỉ</th>
                    <th>Email</th>
                    <th>Ngày đăng ký</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr> 
                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['hoten'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['sdt'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['diachi'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['ngaytao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['trangthai'] ?? '') ?></td>
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
</div> 

