
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connect.php';

if (!isset($_SESSION['id'])) {
    echo '<div class="hh-alert hh-error">Vui lòng đăng nhập để xem hồ sơ.</div>';
    return;
}

$id = (int)$_SESSION['id'];
$message = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten = trim($_POST['hoten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');

    if ($hoten === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="hh-alert hh-error">Vui lòng nhập họ tên và email hợp lệ.</div>';
    } else {
        $check = $conn->prepare("SELECT id FROM taikhoan WHERE email=? AND id<>? LIMIT 1");
        $check->bind_param("si", $email, $id);
        $check->execute();
        $duplicate = $check->get_result()->fetch_assoc();
        $check->close();

        if ($duplicate) {
            $message = '<div class="hh-alert hh-error">Email này đã được sử dụng bởi tài khoản khác.</div>';
        } else {
            $update = $conn->prepare(
                "UPDATE taikhoan SET hoten=?, email=?, sdt=?, diachi=? WHERE id=?"
            );
            $update->bind_param("ssssi", $hoten, $email, $sdt, $diachi, $id);

            if ($update->execute()) {
                $_SESSION['user'] = $hoten;
                $message = '<div class="hh-alert hh-success">Cập nhật hồ sơ thành công!</div>';
            } else {
                $message = '<div class="hh-alert hh-error">Không thể cập nhật hồ sơ. Vui lòng thử lại.</div>';
            }
            $update->close();
        }
    }
}

$stmt = $conn->prepare("SELECT id, hoten, email, sdt, diachi, ngaytao, vaitro, trangthai FROM taikhoan WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo '<div class="hh-alert hh-error">Không tìm thấy tài khoản.</div>';
    return;
}

$initial = mb_strtoupper(mb_substr($user['hoten'], 0, 1, 'UTF-8'), 'UTF-8');
?>

<div class="hh-page">
    <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL</span>
            <h1>Hồ sơ cá nhân</h1>
            <p>Quản lý thông tin và tài khoản của bạn.</p>
        </div>
    </div>

    <?= $message ?>

    <div class="hh-profile-layout">
        <section class="hh-card hh-profile-side">
            <div class="hh-avatar"><?= htmlspecialchars($initial) ?></div>
            <h2><?= htmlspecialchars($user['hoten']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <span class="hh-role"><?= htmlspecialchars($user['vaitro']) ?></span>
            <div class="hh-profile-meta">
                <div><span>Mã tài khoản</span><strong>#<?= (int)$user['id'] ?></strong></div>
                <div><span>Ngày tham gia</span><strong><?= htmlspecialchars($user['ngaytao']) ?></strong></div>
                <div><span>Trạng thái</span><strong><?= htmlspecialchars($user['trangthai']) ?></strong></div>
            </div>
        </section>

        <section class="hh-card">
            <div class="hh-card-heading">
                <div>
                    <span class="hh-eyebrow">PERSONAL INFORMATION</span>
                    <h2>Thông tin tài khoản</h2>
                </div>
                <span class="hh-heading-icon">✦</span>
            </div>

            <form method="post" class="hh-form">
                <div class="hh-form-grid">
                    <div class="hh-field">
                        <label>Họ và tên</label>
                        <input name="hoten" required maxlength="255"
                               value="<?= htmlspecialchars($user['hoten']) ?>">
                    </div>
                    <div class="hh-field">
                        <label>Email</label>
                        <input type="email" name="email" required
                               value="<?= htmlspecialchars($user['email']) ?>">
                    </div>
                    <div class="hh-field">
                        <label>Số điện thoại</label>
                        <input name="sdt" maxlength="20"
                               value="<?= htmlspecialchars($user['sdt']) ?>">
                    </div>
                    <div class="hh-field">
                        <label>Địa chỉ</label>
                        <input name="diachi" maxlength="255"
                               value="<?= htmlspecialchars($user['diachi']) ?>">
                    </div>
                </div>

                <div class="hh-form-actions">
                    <button class="hh-btn hh-btn-primary" type="submit">Lưu thay đổi</button>
                </div>
            </form>
        </section>
    </div>
</div>
