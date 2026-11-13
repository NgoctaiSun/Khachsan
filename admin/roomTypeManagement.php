<?php
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin']);
$message = '';

// 1. Xử lý lưu form POST trước khi SELECT dữ liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'them_loai_phong') {
    $tenloaiphong = trim($_POST['tenloaiphong'] ?? '');
    $mota         = trim($_POST['mota'] ?? '');
    $sokhach      = trim($_POST['sokhach'] ?? '');
    $gia          = trim($_POST['gia'] ?? '');
    $dientich     = trim($_POST['dientich'] ?? '');

    if ($tenloaiphong === '' || $mota === '' || $sokhach === '' || $gia === '' || $dientich === '') {
        $message = '<script>alert("Vui lòng nhập đầy đủ thông tin loại phòng.");</script>';
    } elseif (!isset($_FILES['anhphong']) || $_FILES['anhphong']['error'] !== UPLOAD_ERR_OK) {
        $message = '<script>alert("Vui lòng chọn ảnh phòng hợp lệ.");</script>';
    } else {
        // Kiểm tra xem tên loại phòng đã tồn tại chưa
        $check = $conn->prepare("SELECT id FROM loaiphong WHERE tenphong = ? LIMIT 1");
        $check->bind_param("s", $tenloaiphong);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            $message = '<script>alert("Tên loại phòng đã tồn tại!");</script>';
        } else {
            // Kiểm tra định dạng ảnh
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower(pathinfo($_FILES['anhphong']['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $allowed)) {
                $message = '<script>alert("Chỉ chấp nhận ảnh JPG, JPEG, PNG, WEBP!");</script>';
            } elseif ($_FILES['anhphong']['size'] > 5 * 1024 * 1024) { // 5MB
                $message = '<script>alert("Ảnh không được vượt quá 5MB!");</script>';
            } else {
                // Tạo tên ảnh duy nhất
                $newName = uniqid('phong_', true) . '.' . $extension;

                // Thư mục lưu ảnh
                $uploadDir = __DIR__ . '/../images/';

                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $uploadPath = $uploadDir . $newName;

                if (move_uploaded_file($_FILES['anhphong']['tmp_name'], $uploadPath)) {
                    $anhphong = 'images/' . $newName;
                    $status = 'hoatdong';

                    $stmt = $conn->prepare("INSERT INTO loaiphong(tenphong, mota, sokhach, gia, dientich, trangthai, anhphong) VALUES(?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $tenloaiphong, $mota, $sokhach, $gia, $dientich, $status, $anhphong);

                    if ($stmt->execute()) {
                        // Tải lại trang để cập nhật danh sách ngay lập tức
                        echo "<script>alert('Thêm loại phòng mới thành công!'); window.location.href=window.location.href;</script>";
                        exit;
                    } else {
                        $message = '<script>alert("Lỗi SQL: ' . addslashes($stmt->error) . '");</script>';
                    }
                } else {
                    $message = '<script>alert("Không thể tải lên tệp tin hình ảnh.");</script>';
                }
            }
        }
    }
}

// 2. Truy vấn danh sách hiển thị ra bảng
$limit = 10;
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;

if ($page < 1) {
    $page = 1;
}

$start = ($page - 1) * $limit;
$sql_count = "SELECT COUNT(*) AS total FROM loaiphong";

$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total'];

$total_pages = ceil($total_records / $limit);
$sql = "SELECT * FROM loaiphong LIMIT $start, $limit";
$result = mysqli_query($conn, $sql);
// 3. Thống kê số lượng loại phòng
$countResult = $conn->query("
    SELECT
      COUNT(*) total,
      SUM(trangthai='hoatdong') active,
      SUM(trangthai='baotri') blocked
    FROM loaiphong
");
$stats = $countResult->fetch_assoc();
// 4. Xử lý lọc dữ liệu

// Lấy dữ liệu từ form gửi lên qua phương thức GET
$keyword   = isset($_GET['q']) ? trim($_GET['q']) : '';
$so_khach  = isset($_GET['sokhach']) ? trim($_GET['sokhach']) : '';
$gia_max   = isset($_GET['gia']) ? trim($_GET['gia']) : '';
$dien_tich = isset($_GET['dientich']) ? trim($_GET['dientich']) : '';

$where_clauses = [];
$params = [];
$types = "";

// 1. Tìm kiếm theo Tên loại phòng hoặc Mô tả
if ($keyword !== '') {
    // Lưu ý: Đổi `tenloaiphong`, `mota` thành tên cột thực tế trong bảng của bạn
    $where_clauses[] = "(tenloaiphong LIKE ? OR mota LIKE ?)";
    $search_term = "%$keyword%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// 2. Lọc theo Số khách (Tìm phòng chứa được số khách lớn hơn hoặc bằng số nhập vào)
if ($so_khach !== '' && is_numeric($so_khach)) {
    // Đổi `sokhach` thành tên cột số khách trong DB của bạn
    $where_clauses[] = "sokhach >= ?";
    $params[] = (int)$so_khach;
    $types .= "i";
}

// 3. Lọc theo Giá (Tìm phòng có giá nhỏ hơn hoặc bằng mức giá nhập vào)
if ($gia_max !== '' && is_numeric($gia_max)) {
    // Đổi `gia` thành tên cột giá trong DB của bạn
    $where_clauses[] = "gia <= ?";
    $params[] = (float)$gia_max;
    $types .= "d";
}

// 4. Lọc theo Diện tích (Tìm phòng có diện tích lớn hơn hoặc bằng số m2 nhập vào)
if ($dien_tich !== '' && is_numeric($dien_tich)) {
    // Đổi `dientich` thành tên cột diện tích trong DB của bạn
    $where_clauses[] = "dientich >= ?";
    $params[] = (float)$dien_tich;
    $types .= "d";
}

// Ghép các điều kiện lại với nhau
$sql_where = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Truy vấn dữ liệu (Đổi `loaiphong` thành tên bảng của bạn nếu khác)
$sql = "SELECT * FROM loaiphong" . $sql_where . " ORDER BY id DESC";

if (count($params) > 0) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($conn, $sql);
}

// Kiểm tra xem có đang bật bộ lọc nào không để hiển thị nút "Đặt lại"
$is_filtering = ($keyword !== '' || $so_khach !== '' || $gia_max !== '' || $dien_tich !== '');
?>


<?= $message; ?>

<div class="hh-page">
    <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL / ADMIN</span>
            <h1>Quản lý loại phòng</h1>
            <p>Theo dõi tình trạng và thông tin từng loại phòng trong khách sạn.</p>
        </div>
        <button class="hh-btn hh-btn-primary" id="btn" class="btn-add">+ Thêm loại phòng mới</button>
    </div>
       <div class="hh-stat-grid">
        <div class="hh-stat-card"><span>Tổng số loại phòng</span><strong><?= (int)$stats['total'] ?></strong><small>Toàn hệ thống</small></div>
        <div class="hh-stat-card"><span>Loại phòng đang hoạt động</span><strong><?= (int)$stats['active'] ?></strong><small>Đang hoạt động</small></div>
        <div class="hh-stat-card"><span>Loại phòng đang bảo trì</span><strong><?= (int)$stats['blocked'] ?></strong><small>Đang bảo trì</small></div>
    </div>
    <!-- Modal Popup Form (Đã thêm enctype để upload ảnh) -->
    <div id="popup">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Thêm loại phòng mới</h3>
                <button type="button" class="close-btn" id="btnCloseX">&times;</button>
            </div>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="them_loai_phong">

                <div class="form-group">
                    <label>Tên loại phòng *</label>
                    <input class="form-control" type="text" name="tenloaiphong" required placeholder="Nhập tên loại phòng">
                </div>

                <div class="form-group">
                    <label>Mô tả *</label>
                    <input class="form-control" type="text" name="mota" required placeholder="Nhập mô tả">
                </div>

                <div class="form-group">
                    <label>Số khách *</label>
                    <input class="form-control" type="number" name="sokhach" required placeholder="Nhập số khách">
                </div>

                <div class="form-group">
                    <label>Giá *</label>
                    <input class="form-control" type="text" name="gia" required placeholder="Nhập giá">
                </div>

                <div class="form-group">
                    <label>Diện tích *</label>
                    <input class="form-control" type="text" name="dientich" placeholder="Nhập diện tích">
                </div>

                <div class="form-group">
                    <label>Ảnh phòng *</label>
                    <input class="form-control" type="file" name="anhphong" required>
                </div>

                <div class="popup-actions">
                    <button type="button" class="btn-cancel" id="btnClose">Hủy</button>
                    <button type="submit" class="btn-submit">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách loại phòng -->
    <div class="hh-card-heading">
        <h2>Danh sách loại phòng</h2>
    </div>
    <form method="GET" action="admin.php" class="hh-filter-bar">
    <!-- Giữ lại tên page hiện tại trên URL -->
    <?php if (isset($_GET['page'])): ?>
        <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
    <?php endif; ?>

    <!-- Tìm theo Tên loại phòng hoặc Mô tả -->
    <input type="text" 
           name="q" 
           placeholder="Tên loại phòng, mô tả..." 
           value="<?= htmlspecialchars($keyword) ?>">

    <!-- Lọc theo Số khách -->
    <input type="number" 
           name="sokhach" 
           min="1"
           placeholder="Số khách tối thiểu..." 
           value="<?= htmlspecialchars($so_khach) ?>">

    <!-- Lọc theo Giá -->
    <input type="number" 
           name="gia" 
           min="0"
           step="10000"
           placeholder="Giá tối đa (VNĐ)..." 
           value="<?= htmlspecialchars($gia_max) ?>">

    <!-- Lọc theo Diện tích -->
    <input type="number" 
           name="dientich" 
           min="1"
           placeholder="Diện tích tối thiểu (m²)..." 
           value="<?= htmlspecialchars($dien_tich) ?>">

    <button type="submit" class="hh-btn hh-btn-primary">Lọc dữ liệu</button>

    <!-- Nút xóa bộ lọc, chỉ hiện khi đang có ít nhất 1 điều kiện lọc -->
    <?php if ($is_filtering): ?>
        <a href="admin.php<?= isset($_GET['page']) ? '?page=' . urlencode($_GET['page']) : '' ?>" class="hh-btn">Đặt lại</a>
    <?php endif; ?>
</form>
    <div class="hh-table-wrap">
        <table class="hh-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên loại phòng</th>
                    <th>Mô tả</th>
                    <th>Số khách</th>
                    <th>Giá</th>
                    <th>Diện tích</th>
                    <th>Ảnh phòng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['tenphong'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['mota'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['sokhach'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['gia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['dientich'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($row['anhphong'])): ?>
                                <img src="<?= htmlspecialchars($row['anhphong']) ?>" alt="Ảnh phòng" style="width: 100px; height: auto;">
                            <?php endif; ?>
                        </td>
                        <td><span class="hh-status hh-status-<?= $row['trangthai'] === 'hoatdong' ? 'green' : 'red'  ?>"><?= htmlspecialchars($row['trangthai'] ?? '') ?></span></td>
                        <td>
                            <?php if (($row['trangthai'] ?? '') === 'baotri'): ?>
                                <a href="process_blockRoomType.php?id=<?= $row['id'] ?>&trangthai=hoatdong"
                                    onclick="return confirm('Bạn có chắc chắn cho loại phòng này hoạt động?')"
                                    style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                                    Hoạt động
                                </a>
                            <?php else: ?>
                                <a href="process_blockRoomType.php?id=<?= $row['id'] ?>&trangthai=baotri"
                                    onclick="return confirm('Bạn có chắc chắn muốn bảo trì  loại phòng này?')"
                                    style="background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                                    Bảo trì
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
        <a href="?page=roomTypeManagement&page_num=<?= $page - 1 ?>">
            Trước
        </a>
    <?php } ?>

    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
        <a href="?page=roomTypeManagement&page_num=<?= $i ?>"
            class="<?= ($page == $i) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php } ?>

    <?php if ($page < $total_pages) { ?>
        <a href="?page=roomTypeManagement&page_num=<?= $page + 1 ?>">
            Sau
        </a>
    <?php } ?>
</div>