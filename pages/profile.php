<?php
require_once __DIR__ . '/../connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}
$userName = $_SESSION['user'];
$message = '';

$stmt = $conn->prepare("SELECT * FROM taikhoan WHERE hoten = ? LIMIT 1");
$stmt->bind_param("s", $userName);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $message = '<div class="notice error">Không tìm thấy thông tin tài khoản.</div>';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten = trim($_POST['hoten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');
    $id = (int)$user['id'];

    $update = $conn->prepare("UPDATE taikhoan SET hoten=?, email=?, sdt=?, diachi=? WHERE id=?");
    $update->bind_param("ssssi", $hoten, $email, $sdt, $diachi, $id);
    if ($update->execute()) {
        $_SESSION['user'] = $hoten;
        $message = '<div class="notice success">Cập nhật thông tin thành công.</div>';
        $user['hoten']=$hoten; $user['email']=$email; $user['sdt']=$sdt; $user['diachi']=$diachi;
    } else {
        $message = '<div class="notice error">Cập nhật thất bại.</div>';
    }
}
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="page-wrap">
    <div class="profile-card">
        <div class="section-title"><h2>Thông tin cá nhân</h2></div>
        <?= $message ?>
        <form method="post">
            <label>Họ và tên</label>
            <input class="form-control" type="text" name="hoten" required value="<?= htmlspecialchars($user['hoten'] ?? '') ?>">
            <label>Email</label>
            <input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            <label>Số điện thoại</label>
            <input class="form-control" type="text" name="sdt" value="<?= htmlspecialchars($user['sdt'] ?? '') ?>">
            <label>Địa chỉ</label>
            <input class="form-control" type="text" name="diachi" value="<?= htmlspecialchars($user['diachi'] ?? '') ?>">
            <label>Vai trò</label>
            <input class="form-control" type="text" readonly value="<?= htmlspecialchars($user['vaitro'] ?? '') ?>">
            <button class="btn" type="submit">Lưu thay đổi</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
