
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/check_permission.php';

checkRole(['admin','letan']);

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$message = '';
$editRoom = null;

function roomNotice($type, $text) {
    return '<div class="hh-alert hh-' . $type . '">' . htmlspecialchars($text) . '</div>';
}

// Thao tác xóa chỉ bằng POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!$isAdmin) {
        $message = roomNotice('error','Chỉ quản trị viên mới có quyền xóa phòng.');
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $check = $conn->prepare("SELECT COUNT(*) AS total FROM datphong WHERE id_phong=?");
        $check->bind_param("i",$id);
        $check->execute();
        $used = (int)$check->get_result()->fetch_assoc()['total'];
        $check->close();

        if ($used > 0) {
            $message = roomNotice('error','Không thể xóa phòng đã có lịch sử đặt. Bạn có thể chuyển phòng sang trạng thái bảo trì.');
        } else {
            $del = $conn->prepare("DELETE FROM phong WHERE id=?");
            $del->bind_param("i",$id);
            $message = $del->execute()
                ? roomNotice('success','Đã xóa phòng thành công.')
                : roomNotice('error','Không thể xóa phòng.');
            $del->close();
        }
    }
}

// Thêm hoặc cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add','edit'], true)) {
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $sophong = trim($_POST['sophong'] ?? '');
    $type = (int)($_POST['id_loaiphong'] ?? 0);
    $tang = (int)($_POST['tang'] ?? 0);
    $status = trim($_POST['trangthai'] ?? 'Trong');
    $note = trim($_POST['ghichu'] ?? '');

    $allowedStatus = ['Trong','Da dat','Bao tri'];

    if ($sophong === '' || $type <= 0 || !in_array($status,$allowedStatus,true)) {
        $message = roomNotice('error','Vui lòng nhập đầy đủ thông tin hợp lệ.');
    } else {
        $duplicate = $conn->prepare("SELECT id FROM phong WHERE sophong=? AND id<>? LIMIT 1");
        $duplicate->bind_param("si",$sophong,$id);
        $duplicate->execute();
        $exists = $duplicate->get_result()->fetch_assoc();
        $duplicate->close();

        if ($exists) {
            $message = roomNotice('error','Số phòng này đã tồn tại.');
        } elseif ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO phong(sophong,id_loaiphong,tang,trangthai,ghichu) VALUES(?,?,?,?,?)");
            $stmt->bind_param("siiss",$sophong,$type,$tang,$status,$note);
            $message = $stmt->execute()
                ? roomNotice('success','Thêm phòng thành công.')
                : roomNotice('error','Thêm phòng thất bại: '.$stmt->error);
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE phong SET sophong=?,id_loaiphong=?,tang=?,trangthai=?,ghichu=? WHERE id=?");
            $stmt->bind_param("siissi",$sophong,$type,$tang,$status,$note,$id);
            $message = $stmt->execute()
                ? roomNotice('success','Cập nhật phòng thành công.')
                : roomNotice('error','Cập nhật phòng thất bại: '.$stmt->error);
            $stmt->close();
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM phong WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $editRoom = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$types = [];
$typeResult = $conn->query("SELECT id,tenphong FROM loaiphong ORDER BY tenphong");
while ($t = $typeResult->fetch_assoc()) $types[] = $t;

$keyword = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT p.*,lp.tenphong,lp.gia
        FROM phong p JOIN loaiphong lp ON lp.id=p.id_loaiphong
        WHERE 1=1";
$params = [];
$typesBind = '';

if ($keyword !== '') {
    $sql .= " AND (p.sophong LIKE ? OR lp.tenphong LIKE ?)";
    $like = "%".$keyword."%";
    $params[] = $like; $params[] = $like;
    $typesBind .= 'ss';
}
if (in_array($statusFilter,['Trong','Da dat','Bao tri'],true)) {
    $sql .= " AND p.trangthai=?";
    $params[] = $statusFilter;
    $typesBind .= 's';
}
$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($typesBind,...$params);
$stmt->execute();
$rooms = $stmt->get_result();

$countResult = $conn->query("
    SELECT
      COUNT(*) total,
      SUM(trangthai='Trong') available,
      SUM(trangthai='Da dat') booked,
      SUM(trangthai='Bao tri') maintenance
    FROM phong
");
$stats = $countResult->fetch_assoc();
?>

<div class="hh-page">
    <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL / ADMIN</span>
            <h1>Quản lý phòng</h1>
            <p>Theo dõi tình trạng và thông tin từng phòng trong khách sạn.</p>
        </div>
        <a class="hh-btn hh-btn-primary" href="admin.php?page=roomManagement#roomForm">＋ Thêm phòng mới</a>
    </div>

    <?= $message ?>

    <div class="hh-stat-grid">
        <div class="hh-stat-card"><span>Tổng số phòng</span><strong><?= (int)$stats['total'] ?></strong><small>Toàn hệ thống</small></div>
        <div class="hh-stat-card"><span>Phòng trống</span><strong><?= (int)$stats['available'] ?></strong><small>Đang sẵn sàng</small></div>
        <div class="hh-stat-card"><span>Đã đặt</span><strong><?= (int)$stats['booked'] ?></strong><small>Đang có trạng thái đã đặt</small></div>
        <div class="hh-stat-card"><span>Bảo trì</span><strong><?= (int)$stats['maintenance'] ?></strong><small>Tạm ngưng phục vụ</small></div>
    </div>

    <section class="hh-card" id="roomForm">
        <div class="hh-card-heading">
            <div>
                <span class="hh-eyebrow">ROOM INFORMATION</span>
                <h2><?= $editRoom ? 'Cập nhật thông tin phòng' : 'Thêm phòng mới' ?></h2>
            </div>
        </div>

        <form method="post" class="hh-form">
            <input type="hidden" name="action" value="<?= $editRoom?'edit':'add' ?>">
            <?php if ($editRoom): ?>
                <input type="hidden" name="id" value="<?= (int)$editRoom['id'] ?>">
            <?php endif; ?>

            <div class="hh-form-grid">
                <div class="hh-field">
                    <label>Số phòng</label>
                    <input name="sophong" required value="<?= htmlspecialchars($editRoom['sophong'] ?? '') ?>">
                </div>
                <div class="hh-field">
                    <label>Loại phòng</label>
                    <select name="id_loaiphong" required>
                        <option value="">-- Chọn loại phòng --</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"
                                <?= isset($editRoom['id_loaiphong']) && (int)$editRoom['id_loaiphong']===(int)$t['id']?'selected':'' ?>>
                                <?= htmlspecialchars($t['tenphong']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="hh-field">
                    <label>Tầng</label>
                    <input type="number" name="tang" min="0" value="<?= (int)($editRoom['tang'] ?? 1) ?>">
                </div>
                <div class="hh-field">
                    <label>Trạng thái</label>
                    <select name="trangthai">
                        <?php foreach (['Trong'=>'Trống','Da dat'=>'Đã đặt','Bao tri'=>'Bảo trì'] as $v=>$label): ?>
                            <option value="<?= $v ?>" <?= ($editRoom['trangthai'] ?? 'Trong')===$v?'selected':'' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="hh-field hh-field-full">
                    <label>Ghi chú</label>
                    <textarea name="ghichu" rows="3"><?= htmlspecialchars($editRoom['ghichu'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="hh-form-actions">
                <button class="hh-btn hh-btn-primary" type="submit"><?= $editRoom?'Lưu cập nhật':'Thêm phòng' ?></button>
                <?php if ($editRoom): ?>
                    <a class="hh-btn hh-btn-light" href="admin.php?page=roomManagement">Hủy chỉnh sửa</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="hh-card">
        <div class="hh-card-heading">
            <div>
                <span class="hh-eyebrow">ROOM DIRECTORY</span>
                <h2>Danh sách phòng</h2>
            </div>
        </div>

        <form method="get" class="hh-filter-bar">
            <input type="hidden" name="page" value="roomManagement">
            <input name="q" placeholder="Tìm số phòng hoặc loại phòng..." value="<?= htmlspecialchars($keyword) ?>">
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="Trong" <?= $statusFilter==='Trong'?'selected':'' ?>>Trống</option>
                <option value="Da dat" <?= $statusFilter==='Da dat'?'selected':'' ?>>Đã đặt</option>
                <option value="Bao tri" <?= $statusFilter==='Bao tri'?'selected':'' ?>>Bảo trì</option>
            </select>
            <button class="hh-btn hh-btn-primary">Lọc dữ liệu</button>
            <a class="hh-btn hh-btn-light" href="admin.php?page=roomManagement">Đặt lại</a>
        </form>

        <div class="hh-table-wrap">
            <table class="hh-table">
                <thead><tr><th>Số phòng</th><th>Loại phòng</th><th>Tầng</th><th>Giá/đêm</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                <?php if ($rooms->num_rows): ?>
                    <?php while ($r=$rooms->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= htmlspecialchars($r['sophong']) ?></strong></td>
                        <td><?= htmlspecialchars($r['tenphong']) ?></td>
                        <td><?= (int)$r['tang'] ?></td>
                        <td><?= number_format((float)$r['gia'],0,',','.') ?>đ</td>
                        <td><span class="hh-status hh-status-<?= $r['trangthai']==='Trong'?'green':($r['trangthai']==='Bao tri'?'red':'gold') ?>"><?= htmlspecialchars($r['trangthai']) ?></span></td>
                        <td>
                            <div class="hh-actions">
                                <a class="hh-btn hh-btn-small hh-btn-light" href="admin.php?page=roomManagement&edit=<?= (int)$r['id'] ?>#roomForm">Sửa</a>
                                <?php if ($isAdmin): ?>
                                <form method="post" class="hh-inline-form" data-confirm="Bạn chắc chắn muốn xóa phòng <?= htmlspecialchars($r['sophong']) ?>?">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="hh-btn hh-btn-small hh-btn-danger" type="submit">Xóa</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="hh-empty">Không tìm thấy phòng phù hợp.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
