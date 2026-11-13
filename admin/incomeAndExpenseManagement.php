
<?php
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

// Bước 1: Kiểm tra quyền truy cập (cho phép Kế toán hoặc Admin)
checkRole(['ketoan', 'admin']);

$message = '';

// =========================================================================
// TỰ ĐỘNG ĐỒNG BỘ DỮ LIỆU TỪ BẢNG DATPHONG SANG BẢNG THUCHI
// =========================================================================
// Lưu ý: Hãy kiểm tra lại tên cột giá tiền (vd: tongtien) và giá trị chuỗi trong cột trangthai/vaitro cho khớp với DB của bạn
$sql_sync = "
    INSERT INTO thuchi (loaiphieu, sotien, noidung, thoigian, id_taikhoan, id_datphong)
    SELECT 
        'thu' AS loaiphieu,
        dp.tongtien AS sotien,
        CONCAT('Thu tiền đặt phòng #', dp.id) AS noidung,
        NOW() AS thoigian,
        dp.id_taikhoan,
        dp.id AS id_datphong
    FROM datphong dp
    INNER JOIN taikhoan tk ON dp.id_taikhoan = tk.id
    WHERE NOT EXISTS (
        SELECT 1 FROM thuchi tc WHERE tc.id_datphong = dp.id
    )
    AND (
        (tk.vaitro = 'khachhang' AND dp.trangthai = 'Chờ xác nhận')
        OR 
        (tk.vaitro IN ('letan', 'admin') AND dp.trangthai IN ('Đã xác nhận', 'Đã nhận phòng'))
    )
";
// Chạy lệnh đồng bộ âm thầm mỗi khi load trang
mysqli_query($conn, $sql_sync);
// =========================================================================


// 1. Xử lý thêm giao dịch thu/chi thủ công (Bước 5, 6, 7, 8)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'them_giaodich') {
    $loai_gd     = trim($_POST['loai_gd'] ?? ''); 
    $noidung     = trim($_POST['noidung'] ?? '');
    $sotien      = trim($_POST['sotien'] ?? '');
    $ngay_ps     = trim($_POST['ngay_phatsinh'] ?? '');
    $id_taikhoan = $_SESSION['id'] ?? 0;

    if ($loai_gd === '' || $noidung === '' || $sotien === '' || $ngay_ps === '') {
        $message = '<script>alert("Vui lòng nhập đầy đủ các trường bắt buộc (*).");</script>';
    } elseif (!is_numeric($sotien) || $sotien <= 0) {
        $message = '<script>alert("Số tiền phải là một số lớn hơn 0.");</script>';
    } else {
        $stmt = $conn->prepare("INSERT INTO thuchi (loaiphieu, sotien, noidung, thoigian, id_taikhoan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssi", $loai_gd, $sotien, $noidung, $ngay_ps, $id_taikhoan);

        if ($stmt->execute()) {
            echo "<script>alert('Thêm giao dịch thành công!'); window.location.href='admin.php?page=incomeAndExpenseManagement';</script>";
            exit;
        } else {
            $message = '<script>alert("Lỗi SQL: ' . addslashes($stmt->error) . '");</script>';
        }
    }
}

// 2. Xử lý tìm kiếm và lọc giao dịch
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$filter_loai = isset($_GET['filter_loai']) ? trim($_GET['filter_loai']) : '';

$where_clauses = [];
$params = [];
$types = "";

if ($keyword !== '') {
    $where_clauses[] = "(noidung LIKE ? OR id LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $types .= "ss";
}

if ($filter_loai === 'thu' || $filter_loai === 'chi') {
    $where_clauses[] = "loaiphieu = ?";
    $params[] = $filter_loai;
    $types .= "s";
}

$sql_where = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Phân trang (Đã sửa lại lỗi cú pháp ở biến $start)
$limit = 10;
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) AS total FROM thuchi" . $sql_where;
if (count($params) > 0) {
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $row_count = $stmt_count->get_result()->fetch_assoc();
} else {
    $result_count = mysqli_query($conn, $sql_count);
    $row_count = mysqli_fetch_assoc($result_count);
}
$total_records = $row_count['total'] ?? 0;
$total_pages = ceil($total_records / $limit);

// Truy vấn danh sách có phân trang
$sql = "SELECT * FROM thuchi" . $sql_where . " ORDER BY thoigian DESC, id DESC LIMIT $start, $limit";
if (count($params) > 0) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($conn, $sql);
}

// Xử lý xem chi tiết giao dịch 
$row_seen = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt_seen = $conn->prepare("SELECT * FROM thuchi WHERE id = ?");
    $stmt_seen->bind_param("i", $id);
    $stmt_seen->execute();
    $result_seen = $stmt_seen->get_result();
    $row_seen = mysqli_fetch_assoc($result_seen);
}
?>
<?= $message; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Quản lý thu chi</h1>
        <button id="btn" class="btn-add">+ Thêm giao dịch</button>
    </div>

    <!-- Thanh tìm kiếm và bộ lọc (Luồng phụ A2, A3) -->
    <form method="GET" action="admin.php" class="row g-3 mb-4">
        <input type="hidden" name="page" value="incomeAndExpenseManagement">
        <div class="col-md-4">
            <input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Tìm theo mã giao dịch hoặc nội dung...">
        </div>
        <div class="col-md-3">
            <select name="filter_loai" class="form-control">
                <option value="">-- Tất cả loại giao dịch --</option>
                <option value="thu" <?= $filter_loai === 'thu' ? 'selected' : '' ?>>Khoản thu</option>
                <option value="chi" <?= $filter_loai === 'chi' ? 'selected' : '' ?>>Khoản chi</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary">Tìm kiếm / Lọc</button>
            <a href="admin.php?page=incomeAndExpenseManagement" class="btn btn-outline-secondary">Làm mới</a>
        </div>
    </form>

    <!-- Modal Popup Thêm Giao Dịch (Bước 3, 4, 5) -->
    <div id="popup">
        <div class="popup-content">
            
                <div class="popup-header">
                    <h3>Thêm giao dịch thu / chi</h3>
                    <button type="button" class="close-btn" id="btnCloseX">&times;</button>
                </div>

                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="them_giaodich">

                        <div class="mb-3">
                            <label class="form-group">Loại giao dịch *</label>
                            <select name="loai_gd" class="form-control" required>
                                <option value="thu">Khoản thu</option>
                                <option value="chi">Khoản chi</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-group">Nội dung *</label>
                            <input type="text" class="form-control" name="noidung" required placeholder="Nhập nội dung thu/chi">
                        </div>

                        <div class="mb-3">
                            <label class="form-group">Số tiền (VNĐ) *</label>
                            <input type="number" step="0.01" class="form-control" name="sotien" required placeholder="Nhập số tiền">
                        </div>

                        <div class="mb-3">
                            <label class="form-group">Ngày phát sinh *</label>
                            <input type="datetime-local" class="form-control" name="ngay_phatsinh" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                    </div>
                    <div class="popup-actions">
                        <button type="button" class="btn-cancel" id="btnClose">Hủy</button>
                        <button type="submit" class="btn-submit">Lưu lại</button>
                    </div>
                </form>
        </div>
    </div>

    <!-- Bảng hiển thị danh sách thu chi (Bước 2) -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="admin__table table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Loại</th>
                        <th>Nội dung</th>
                        <th>Số tiền</th>
                        <th>Ngày phát sinh</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td>
                                    <?php if ($row['loaiphieu'] === 'thu'): ?>
                                        <span class="badge bg-success">Khoản thu</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Khoản chi</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['noidung']) ?></td>
                                <td class="fw-bold <?= $row['loaiphieu'] === 'thu' ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($row['sotien'], 0, ',', '.') ?> VNĐ
                                </td>
                                <td><?= htmlspecialchars($row['thoigian']) ?></td>
                                <td>
                                    <!-- Nút xem chi tiết (Luồng phụ A1) -->
                                    <a href="admin.php?page=incomeAndExpenseManagement&id=<?= $row['id'] ?>">Xem chi tiết</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr> 
                            <td colspan="7" class="text-center text-muted">Không có dữ liệu giao dịch nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
<?php if ($row_seen): ?>
<div id="popupDetail" class="custom-popup-overlay"
     style="display: flex;">

    <div class="custom-popup-content">
        <div class="custom-popup-header">
            <h3>Chi tiết giao dịch</h3>
            <button type="button" class="close-btn"
                    onclick="window.location.href='admin.php?page=incomeAndExpenseManagement'">
                &times;
            </button>
        </div>

        <div class="custom-popup-body">
            <p>
                <strong>Mã giao dịch:</strong>
                <?= htmlspecialchars($row_seen['id']) ?>
            </p>

            <p>
                <strong>Loại giao dịch:</strong>
                <?= $row_seen['loaiphieu'] === 'thu' ? 'Khoản thu' : 'Khoản chi' ?>
            </p>

            <p>
                <strong>Nội dung:</strong>
                <?= htmlspecialchars($row_seen['noidung']) ?>
            </p>

            <p>
                <strong>Số tiền:</strong>
                <?= number_format($row_seen['sotien'], 0, ',', '.') ?> VNĐ
            </p>

            <p>
                <strong>Ngày phát sinh:</strong>
                <?= htmlspecialchars($row_seen['thoigian']) ?>
            </p>

            <p>
                <strong>Mã tài khoản:</strong>
                <?= htmlspecialchars($row_seen['id_taikhoan']) ?>
            </p>
            <?php if ($row_seen['loaiphieu'] === 'thu' ): ?>
              <p>
                <strong>Mã đặt phòng:</strong>
                <?= htmlspecialchars($row_seen['id_datphong']) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination mt-3 d-flex justify-content-center">
            <?php if ($page > 1): ?>
                <a href="?page=financeManagement&page_num=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>&filter_loai=<?= urlencode($filter_loai) ?>" class="btn btn-outline-primary btn-sm me-1">Trước</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=financeManagement&page_num=<?= $i ?>&keyword=<?= urlencode($keyword) ?>&filter_loai=<?= urlencode($filter_loai) ?>" class="btn btn-sm <?= ($page == $i) ? 'btn-primary' : 'btn-outline-primary' ?> me-1"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=financeManagement&page_num=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>&filter_loai=<?= urlencode($filter_loai) ?>" class="btn btn-outline-primary btn-sm">Sau</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div> 

<div class="pagination">

    <?php if ($page > 1) { ?>
        <a href="?page=incomeAndExpenseManagement&page_num=<?= $page - 1 ?>">
            Trước
        </a>
    <?php } ?>

    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
        <a href="?page=incomeAndExpenseManagement&page_num=<?= $i ?>"
           class="<?= ($page == $i) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php } ?>

    <?php if ($page < $total_pages) { ?>
        <a href="?page=incomeAndExpenseManagement&page_num=<?= $page + 1 ?>">
            Sau
        </a>
    <?php } ?>

</div>
