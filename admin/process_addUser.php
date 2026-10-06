<?php 
$conn =mysqli_connect('localhost','root','','khachsan_db');

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
                echo "<script>
                        alert('Thêm người dùng mới thành công!');
                window.location.href=window.location.href;
                </script>";
    exit;
            } else {
                $message = '<div class="notice error">Không thể tạo tài khoản: '.htmlspecialchars($stmt->error).'</div>';
            }
        }
    }
}

?>