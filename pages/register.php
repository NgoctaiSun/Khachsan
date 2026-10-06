<?php
require_once __DIR__ . '/../connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten = trim($_POST['hoten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matkhau = $_POST['matkhau'] ?? '';
    $sdt = trim($_POST['sdt'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');

    if ($hoten === '' || $email === '' || $matkhau === '') {
        $message = '<div class="notice error">Vui lòng nhập đầy đủ họ tên, email và mật khẩu.</div>';
    } else {
        $check = $conn->prepare("SELECT id FROM taikhoan WHERE email = ? OR hoten = ? LIMIT 1");
        $check->bind_param("ss", $email, $hoten);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            $message = '<div class="notice error">Email hoặc tên đăng nhập đã tồn tại.</div>';
        } else {
            $hash = password_hash($matkhau, PASSWORD_DEFAULT);
            $role = 'khachhang';
            $status = 'hoatdong';
            $stmt = $conn->prepare("INSERT INTO taikhoan(hoten, matkhau, sdt, diachi, email, ngaytao, trangthai, vaitro) VALUES(?,?,?,?,?,NOW(),?,?)");
            $stmt->bind_param("sssssss", $hoten, $hash, $sdt, $diachi, $email, $status, $role);

            if ($stmt->execute()) {
                $message = '<div class="notice success">Đăng ký thành công. <a href="../index.php?page=login">Đăng nhập ngay</a>.</div>';
            } else {
                $message = '<div class="notice error">Không thể tạo tài khoản: '.htmlspecialchars($stmt->error).'</div>';
            }
        }
    }
}
?>
<div class="page-wrap">
    <div class="profile-card">
        <div class="section-title"><h2>Đăng ký tài khoản</h2><p>Tạo tài khoản để đặt phòng và quản lý thông tin cá nhân.</p></div>
        <?= $message ?>
        <form method="post">
            <label>Họ và tên *</label>
            <input class="form-control" type="text" name="hoten" required value="<?= htmlspecialchars($_POST['hoten'] ?? '') ?>">
            <label>Email *</label>
            <input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <label>Mật khẩu *</label>
            <input class="form-control" type="password" name="matkhau" required minlength="6">
            <label>Số điện thoại</label>
            <input class="form-control" type="text" name="sdt" value="<?= htmlspecialchars($_POST['sdt'] ?? '') ?>">
            <label>Địa chỉ</label>
            <input class="form-control" type="text" name="diachi" value="<?= htmlspecialchars($_POST['diachi'] ?? '') ?>">
            <button class="btn" type="submit">Đăng ký</button>
        </form>
    </div>
</div>
