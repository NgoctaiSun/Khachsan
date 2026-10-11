<?php
require_once __DIR__ . '/../connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten   = trim($_POST['hoten'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $matkhau = $_POST['matkhau'] ?? '';
    $sdt     = trim($_POST['sdt'] ?? '');
    $diachi  = trim($_POST['diachi'] ?? '');

    if ($hoten === '' || $email === '' || $matkhau === '') {
        $message = '<div class="alert-notice error">⚠️ Vui lòng điền đầy đủ thông tin bắt buộc (*).</div>';
    } else {
        // Kiểm tra trùng lặp email hoặc tên đăng nhập
        $check = $conn->prepare("SELECT id FROM taikhoan WHERE email = ? OR hoten = ? LIMIT 1");
        $check->bind_param("ss", $email, $hoten);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            $message = '<div class="alert-notice error">⚠️ Email hoặc Họ tên / Tên đăng nhập này đã tồn tại trên hệ thống.</div>';
        } else {
            $hash   = password_hash($matkhau, PASSWORD_DEFAULT);
            $role   = 'khachhang';
            $status = 'hoatdong';
            
            $stmt = $conn->prepare("INSERT INTO taikhoan(hoten, matkhau, sdt, diachi, email, ngaytao, trangthai, vaitro) VALUES(?,?,?,?,?,NOW(),?,?)");
            $stmt->bind_param("sssssss", $hoten, $hash, $sdt, $diachi, $email, $status, $role);

            if ($stmt->execute()) {
                $message = '<div class="alert-notice success">🎉 Đăng ký tài khoản thành công! <a href="index.php?page=login" style="font-weight:bold; color:#15803d; text-decoration:underline;">Đăng nhập ngay</a></div>';
            } else {
                $message = '<div class="alert-notice error">❌ Đăng ký thất bại: ' . htmlspecialchars($stmt->error) . '</div>';
            }
        }
    }
}
?>

<div class="register-wrapper">
    <div class="register-card-luxury">
        
        <div class="register-header">
            <span class="luxury-badge">✦ HARMONY RESORT ✦</span>
            <h2>ĐĂNG KÝ TÀI KHOẢN</h2>
            <p>Trở thành thành viên để nhận ưu đãi đặt phòng đặc quyền và quản lý kỳ nghỉ dễ dàng.</p>
        </div>

        <?= $message ?>

        <form method="post" class="register-form-grid">
            
            <div class="register-field">
                <label>Họ và tên / Tên đăng nhập *</label>
                <input type="text" name="hoten" required placeholder="Nhập họ và tên đầy đủ" value="<?= htmlspecialchars($_POST['hoten'] ?? '') ?>">
            </div>

            <div class="form-row-2col">
                <div class="register-field">
                    <label>Địa chỉ Email *</label>
                    <input type="email" name="email" required placeholder="example@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="register-field">
                    <label>Số điện thoại</label>
                    <input type="tel" name="sdt" placeholder="0901234567" value="<?= htmlspecialchars($_POST['sdt'] ?? '') ?>">
                </div>
            </div>

            <div class="register-field">
                <label>Mật khẩu *</label>
                <div class="password-input-wrap">
                    <input type="password" name="matkhau" id="regPassword" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                    <button type="button" class="toggle-pwd-btn" onclick="toggleRegPassword()">👁️</button>
                </div>
            </div>

            <div class="register-field">
                <label>Địa chỉ liên hệ</label>
                <input type="text" name="diachi" placeholder="Nhập tỉnh/thành phố hoặc địa chỉ" value="<?= htmlspecialchars($_POST['diachi'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-register-submit">TẠO TÀI KHOẢN NGAY</button>
        </form>

        <div class="register-footer">
            <span>Đã có tài khoản thành viên?</span> 
            <a href="index.php?page=login">Đăng nhập tại đây</a>
        </div>

    </div>
</div>

<script>
function toggleRegPassword() {
    var pwdInput = document.getElementById('regPassword');
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
    } else {
        pwdInput.type = 'password';
    }
}
</script>
