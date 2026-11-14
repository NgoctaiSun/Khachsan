<?php
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin']);

// =========================================================================
// 1. XỬ LÝ THÊM KHUYẾN MÃI (POST) - ĐẶT LÊN ĐẦU ĐỂ CẬP NHẬT DỮ LIỆU NGAY
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'them_khuyen_mai') {
    $loaiphong       = trim($_POST['loaiphong'] ?? '');
    $dem             = (int)($_POST['dem'] ?? 0);
    $phantramgiamgia = (int)($_POST['phantramgiamgia'] ?? 0);

    $stmt_check = $conn->prepare("SELECT id FROM loaiphong WHERE tenphong = ?");
    $stmt_check->bind_param("s", $loaiphong);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows > 0) {
        $row = $res_check->fetch_assoc();
        $id_loaiphong = $row['id'];

        $stmt_save = $conn->prepare("INSERT INTO khuyenmai (id_loaiphong, dem, phantramgiamgia, trangthai) VALUES (?, ?, ?, 'dangapdung')");
        $stmt_save->bind_param("iii", $id_loaiphong, $dem, $phantramgiamgia);
        $stmt_save->execute();
        
        header("Location: admin.php?page=promotionManagement&msg=added");
        exit();
    }
}

// =========================================================================
// 2. XỬ LÝ TẠM NGƯNG HOẶC ÁP DỤNG KHUYẾN MÃI (GET)
// =========================================================================
if (isset($_GET['id']) && isset($_GET['trangthai'])) {
    $id = (int)$_GET['id'];
    $trangthai_moi = $_GET['trangthai'];

    if ($trangthai_moi === 'tamngung' || $trangthai_moi === 'dangapdung') {
        $stmt_update = $conn->prepare("UPDATE khuyenmai SET trangthai = ? WHERE id = ?");
        $stmt_update->bind_param("si", $trangthai_moi, $id);

        if ($stmt_update->execute()) {
            header("Location: admin.php?page=promotionManagement&msg=success");
            exit();
        } else {
            echo "Lỗi khi cập nhật: " . $conn->error;
        }
    }
}

// =========================================================================
// 3. THỐNG KÊ TỔNG KHUYẾN MÃI
// =========================================================================
$countResult = $conn->query("
    SELECT
      COUNT(*) total,
      SUM(trangthai='dangapdung') active,
      SUM(trangthai='tamngung') blocked
    FROM khuyenmai
");
$stats = $countResult->fetch_assoc();

// =========================================================================
// 4. GỘP CHUNG TÌM KIẾM VÀ PHÂN TRANG
// =========================================================================
$keyword  = isset($_GET['q']) ? trim($_GET['q']) : '';
$nights   = (isset($_GET['nights']) && $_GET['nights'] !== '') ? (int)$_GET['nights'] : '';
$discount = (isset($_GET['discount']) && $_GET['discount'] !== '') ? (int)$_GET['discount'] : '';

$base_join = "FROM khuyenmai km
              LEFT JOIN loaiphong lp ON km.id_loaiphong = lp.id
              WHERE 1=1";

$where_sql = "";
$params = [];
$types  = "";

// Lọc theo Tên loại phòng
if ($keyword !== '') {
    $where_sql .= " AND lp.tenphong LIKE ?";
    $params[] = "%" . $keyword . "%";
    $types .= "s";
}

// Lọc theo Số đêm
if ($nights !== '' && $nights > 0) {
    $where_sql .= " AND km.dem = ?";
    $params[] = $nights;
    $types .= "i";
}

// Lọc theo Phần trăm giảm giá
if ($discount !== '' && $discount >= 0 && $discount <= 100) {
    $where_sql .= " AND km.phantramgiamgia = ?";
    $params[] = $discount;
    $types .= "i";
}

// Tính tổng số bản ghi theo điều kiện lọc để phân trang
$limit = 10;
$page  = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$sql_count = "SELECT COUNT(*) AS total " . $base_join . $where_sql;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages   = ceil($total_records / $limit);

// Truy vấn lấy danh sách hiển thị ra bảng (Giữ nguyên alias id_khuyenmai cho HTML bên dưới)
$sql = "SELECT 
            km.id AS id_khuyenmai,
            km.id_loaiphong,
            km.dem,
            km.phantramgiamgia,
            km.trangthai,
            lp.tenphong 
        " . $base_join . $where_sql . " 
        ORDER BY km.id DESC 
        LIMIT ?, ?";

$list_params = $params;
$list_params[] = $start;
$list_params[] = $limit;
$list_types = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($list_types, ...$list_params);
$stmt->execute();
$result = $stmt->get_result();
?>


<div class="hh-page">
    <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL / ADMIN</span>
            <h1>Quản lý khuyến mãi</h1>
            <p>Theo dõi tình trạng và thông tin từng khuyến mãi trong khách sạn.</p>
        </div>
        <button class="hh-btn hh-btn-primary" id="btn" class="btn-add">+ Thêm khuyến mãi mới</button>
    </div>
    
       <div class="hh-stat-grid">
        <div class="hh-stat-card"><span>Tổng số khuyến mãi</span><strong><?= (int)$stats['total'] ?></strong><small>Toàn hệ thống</small></div>
        <div class="hh-stat-card"><span>Khuyến mãi đang hoạt động</span><strong><?= (int)$stats['active'] ?></strong><small>Đang hoạt động</small></div>
        <div class="hh-stat-card"><span>Khuyến mãi đã tạm ngưng</span><strong><?= (int)$stats['blocked'] ?></strong><small>Đã tạm ngưng</small></div>
    </div>
    <!-- Modal Popup Form -->
    <div id="popup">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Thêm khuyến mãi mới</h3>
                <button type="button" class="close-btn" id="btnCloseX">&times;</button>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="them_khuyen_mai">

                <div class="form-group">
                    <label>Loại phòng *</label>
                    <input class="form-control" type="text" name="loaiphong" required placeholder="Nhập loại phòng">
                </div>

                <div class="form-group">
                    <label>Đêm *</label>
                    <input class="form-control" type="number" name="dem" required placeholder="Nhập số đêm">
                </div>

                <div class="form-group">
                    <label>Giảm giá (%) *</label>
                    <input class="form-control" type="number" name="phantramgiamgia" required placeholder="Nhập phần trăm giảm giá">
                </div>

                <div class="popup-actions">
                    <button type="button" class="btn-cancel" id="btnClose">Hủy</button>
                    <button type="submit" class="btn-submit">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách người dùng -->
       <div class="hh-card-heading">
        <h2>Danh sách khuyến mãi</h2>
    </div>
    <form method="GET" action="admin.php" class="hh-filter-bar">
    <!-- Giữ lại tên page hiện tại trên URL để không bị đá ra trang chủ khi submit -->
    <?php if (isset($_GET['page'])): ?>
        <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
    <?php endif; ?>

    <!-- Ô nhập tên loại phòng -->
    <input type="text"
        name="q"
        placeholder="Nhập tên loại phòng..."
        value="<?= htmlspecialchars($keyword) ?>">

    <!-- Ô nhập số đêm -->
    <input type="number"
        name="nights"
        min="1"
        placeholder="Số đêm (VD: 2)"
        value="<?= htmlspecialchars((string)$nights) ?>"
        style="width: 140px;">

    <!-- Ô nhập phần trăm giảm giá -->
    <input type="number"
        name="discount"
        min="1"
        max="100"
        placeholder="% giảm (VD: 15)"
        value="<?= htmlspecialchars((string)$discount) ?>"
        style="width: 150px;">

    <button type="submit" class="hh-btn hh-btn-primary">Lọc dữ liệu</button>

    <!-- Nút xóa bộ lọc: hiện khi có ít nhất 1 tiêu chí đang được lọc -->
    <?php if ($keyword !== '' || $nights !== '' || $discount !== ''): ?>
        <a href="admin.php<?= isset($_GET['page']) ? '?page=' . urlencode($_GET['page']) : '' ?>" class="hh-btn">Đặt lại</a>
    <?php endif; ?>
</form>
    <div class="hh-table-wrap">
        <table class="hh-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Loại phòng</th>
                    <th>Đêm</th>
                    <th>Giảm giá %</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['tenphong'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['dem'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['phantramgiamgia'] ?? '') ?></td>
                        <td><span class="hh-status hh-status-<?= $row['trangthai'] === 'dangapdung' ? 'green' : 'red' ?>"><?= htmlspecialchars($row['trangthai'] ?? '') ?></span></td>
                        <td>
                            <?php if (($row['trangthai'] ?? '') === 'tamngung'): ?>
                                <a href="admin.php?page=promotionManagement&id=<?= $row['id_khuyenmai'] ?>&trangthai=dangapdung"
                                    onclick="return confirm('Bạn có chắc chắn muốn Áp dụng khuyến mãi này?')"
                                    style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                                    Áp dụng
                                </a>
                            <?php else: ?>
                                <a href="admin.php?page=promotionManagement&id=<?= $row['id_khuyenmai'] ?>&trangthai=tamngung"
                                    onclick="return confirm('Bạn có chắc chắn muốn Tạm ngưng khuyến mãi này?')"
                                    style="background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                                    Tạm ngưng
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">

    <?php if ($page > 1) { ?>
        <a href="?page=promotionManagement&page_num=<?= $page - 1 ?>">
            Trước
        </a>
    <?php } ?>

    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
        <a href="?page=promotionManagement&page_num=<?= $i ?>"
            class="<?= ($page == $i) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php } ?>

    <?php if ($page < $total_pages) { ?>
        <a href="?page=promotionManagement&page_num=<?= $page + 1 ?>">
            Sau
        </a>
    <?php } ?>

</div>