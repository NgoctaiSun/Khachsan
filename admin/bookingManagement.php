
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/check_permission.php';
checkRole(['admin','letan']);

$message = '';
$allowedTransitions = [
    'Cho xac nhan' => ['Da xac nhan','Huy'],
    'Da xac nhan' => ['Da nhan phong','Huy'],
    'Da nhan phong' => ['Hoan tat'],
    'Hoan tat' => [],
    'Huy' => []
];

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $newStatus = trim($_POST['trangthai'] ?? '');

    $currentStmt = $conn->prepare("SELECT trangthai FROM datphong WHERE id=? LIMIT 1");
    $currentStmt->bind_param("i",$id);
    $currentStmt->execute();
    $current = $currentStmt->get_result()->fetch_assoc();
    $currentStmt->close();

    if (!$current || !in_array($newStatus,$allowedTransitions[$current['trangthai']] ?? [],true)) {
        $message = '<div class="hh-alert hh-error">Trạng thái không hợp lệ hoặc đơn đã kết thúc.</div>';
    } else {
        $update = $conn->prepare("UPDATE datphong SET trangthai=? WHERE id=?");
        $update->bind_param("si",$newStatus,$id);
        $message = $update->execute()
            ? '<div class="hh-alert hh-success">Đã cập nhật trạng thái đơn đặt phòng.</div>'
            : '<div class="hh-alert hh-error">Không thể cập nhật đơn đặt phòng.</div>';
        $update->close();

        // Đồng bộ trạng thái phòng khi nhận phòng hoặc hủy.
        if ($newStatus === 'Da nhan phong') {
            $sync = $conn->prepare("UPDATE phong p JOIN datphong d ON d.id_phong=p.id SET p.trangthai='Da dat' WHERE d.id=?");
            $sync->bind_param("i",$id); $sync->execute(); $sync->close();
        } elseif (in_array($newStatus,['Huy','Hoan tat'],true)) {
            $sync = $conn->prepare("
                UPDATE phong p
                JOIN datphong d ON d.id_phong=p.id
                SET p.trangthai='Trong'
                WHERE d.id=? AND NOT EXISTS (
                    SELECT 1 FROM datphong other
                    WHERE other.id_phong=p.id
                      AND other.id<>d.id
                      AND other.trangthai IN ('Cho xac nhan','Da xac nhan','Da nhan phong')
                      AND other.ngaynhan < d.ngaytra
                      AND other.ngaytra > d.ngaynhan
                )
            ");
            $sync->bind_param("i",$id); $sync->execute(); $sync->close();
        }
    }
}

$keyword = trim($_GET['q'] ?? '');
$filter = trim($_GET['status'] ?? '');

$sql = "SELECT d.*,t.hoten,t.sdt,t.email,p.sophong,lp.tenphong
        FROM datphong d
        JOIN taikhoan t ON t.id=d.id_taikhoan
        JOIN phong p ON p.id=d.id_phong
        JOIN loaiphong lp ON lp.id=p.id_loaiphong
        WHERE 1=1";
$params=[]; $bind='';
if ($keyword!=='') {
    $sql .= " AND (t.hoten LIKE ? OR p.sophong LIKE ? OR CAST(d.id AS CHAR) LIKE ?)";
    $like="%".$keyword."%";
    $params=[$like,$like,$like]; $bind='sss';
}
if (in_array($filter,['Cho xac nhan','Da xac nhan','Da nhan phong','Hoan tat','Huy'],true)) {
    $sql .= " AND d.trangthai=?";
    $params[]=$filter; $bind.='s';
}
$sql .= " ORDER BY d.id DESC";
$stmt=$conn->prepare($sql);
if ($params) $stmt->bind_param($bind,...$params);
$stmt->execute();
$result=$stmt->get_result();

$summary=$conn->query("
 SELECT
 SUM(trangthai='Cho xac nhan') pending,
 SUM(trangthai='Da xac nhan') confirmed,
 SUM(trangthai='Da nhan phong') checkedin,
 SUM(trangthai='Hoan tat') completed,
 SUM(trangthai='Huy') cancelled
 FROM datphong
")->fetch_assoc();
?>

<div class="hh-page">
    <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL / ADMIN</span>
            <h1>Quản lý đặt phòng</h1>
            <p>Kiểm tra thông tin khách hàng và xử lý các yêu cầu đặt phòng.</p>
        </div>
    </div>

    <?= $message ?>

    <div class="hh-stat-grid hh-stat-grid-5">
        <div class="hh-stat-card"><span>Chờ xác nhận</span><strong><?= (int)$summary['pending'] ?></strong></div>
        <div class="hh-stat-card"><span>Đã xác nhận</span><strong><?= (int)$summary['confirmed'] ?></strong></div>
        <div class="hh-stat-card"><span>Đã nhận phòng</span><strong><?= (int)$summary['checkedin'] ?></strong></div>
        <div class="hh-stat-card"><span>Hoàn tất</span><strong><?= (int)$summary['completed'] ?></strong></div>
        <div class="hh-stat-card"><span>Đã hủy</span><strong><?= (int)$summary['cancelled'] ?></strong></div>
    </div>

    <section class="hh-card">
        <div class="hh-card-heading">
            <div><span class="hh-eyebrow">BOOKING MANAGEMENT</span><h2>Danh sách đơn đặt phòng</h2></div>
        </div>

        <form method="get" class="hh-filter-bar">
            <input type="hidden" name="page" value="bookingManagement">
            <input name="q" placeholder="Tìm mã đơn, khách hàng, số phòng..." value="<?= htmlspecialchars($keyword) ?>">
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <?php foreach (['Cho xac nhan'=>'Chờ xác nhận','Da xac nhan'=>'Đã xác nhận','Da nhan phong'=>'Đã nhận phòng','Hoan tat'=>'Hoàn tất','Huy'=>'Đã hủy'] as $v=>$label): ?>
                    <option value="<?= $v ?>" <?= $filter===$v?'selected':'' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button class="hh-btn hh-btn-primary">Lọc đơn</button>
            <a href="admin.php?page=bookingManagement" class="hh-btn hh-btn-light">Đặt lại</a>
        </form>

        <div class="hh-table-wrap">
            <table class="hh-table">
                <thead>
                    <tr><th>Mã đơn</th><th>Khách hàng</th><th>Phòng</th><th>Thời gian</th><th>Khách</th><th>Tổng tiền</th><th>Trạng thái</th><th>Cập nhật</th></tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows): ?>
                    <?php while($r=$result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= (int)$r['id'] ?></strong><br><small><?= htmlspecialchars($r['thoigiandat']) ?></small></td>
                        <td><?= htmlspecialchars($r['hoten']??$r['ten_khach_vang_lai']) ?><br><small><?= htmlspecialchars($r['sdt']??$r['sdt_khach_vang_lai']) ?></small><br><small><?= htmlspecialchars($r['email']??$r['email_khach_vang_lai']) ?></small></td>
                        <td><strong><?= htmlspecialchars($r['sophong']) ?></strong><br><small><?= htmlspecialchars($r['tenphong']) ?></small></td>
                        <td><?= date('d/m/Y H:i',strtotime($r['ngaynhan'])) ?><br>đến<br><?= date('d/m/Y H:i',strtotime($r['ngaytra'])) ?></td>
                        <td><?= (int)$r['sokhach'] ?></td>
                        <td><strong><?= number_format((float)$r['tongtien'],0,',','.') ?>đ</strong></td>
                        <td><span class="hh-status <?= $r['trangthai']==='Huy'?'hh-status-red':($r['trangthai']==='Hoan tat'?'hh-status-green':'hh-status-gold') ?>"><?= htmlspecialchars($r['trangthai']) ?></span></td>
                        <td>
                            <?php $next = $allowedTransitions[$r['trangthai']] ?? []; ?>
                            <?php if ($next): ?>
                                <form method="post" class="hh-status-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <select name="trangthai" required>
                                        <?php foreach($next as $v): ?>
                                            <option value="<?= $v ?>"><?= ['Da xac nhan'=>'Xác nhận','Da nhan phong'=>'Nhận phòng','Hoan tat'=>'Hoàn tất','Huy'=>'Hủy đơn'][$v] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="hh-btn hh-btn-small hh-btn-primary" type="submit">Cập nhật</button>
                                </form>
                            <?php else: ?>
                                <span class="hh-muted">Đã kết thúc</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="hh-empty">Chưa có đơn đặt phòng phù hợp.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
