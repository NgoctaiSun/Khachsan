<?php 
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin']);

// 1. XỬ LÝ XÓA LIÊN HỆ (Nên đặt lên đầu trước khi truy vấn dữ liệu)
if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // Ép kiểu int để chống SQL Injection
    $stmt_del = $conn->prepare("DELETE FROM lienhe WHERE id = ?");
    $stmt_del->bind_param("i", $id);
    
    if ($stmt_del->execute()) {
        // Lưu ý: Đổi lại tên page thành contactManagement nếu trang này là Quản lý liên hệ
        header("Location: admin.php?page=contactManagement&msg=success");
        exit();
    } else {
        echo "Lỗi khi xóa: " . $conn->error;
    }
}

// 2. THỐNG KÊ TỔNG LIÊN HỆ TOÀN HỆ THỐNG
$countResult = $conn->query("SELECT COUNT(*) AS total FROM lienhe");
$stats = $countResult->fetch_assoc();

// 3. GỘP CHUNG LOGIC TÌM KIẾM VÀ HIỂN THỊ DANH SÁCH
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

$where_sql = " WHERE 1=1";
$params = [];
$types  = "";

// Nếu có nhập từ khóa thì nối thêm điều kiện tìm kiếm
if ($keyword !== '') {
    $where_sql .= " AND (hoten LIKE ? OR email LIKE ?)";
    $searchTerm = "%" . $keyword . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// 4. PHÂN TRANG (Đếm tổng số bản ghi theo đúng điều kiện đang lọc)
$limit = 10;
$page  = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$sql_count = "SELECT COUNT(*) AS total FROM lienhe" . $where_sql;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages   = ceil($total_records / $limit);

// 5. TRUY VẤN DUY NHẤT LẤY DỮ LIỆU ĐỔ RA BẢNG ($result)
$sql = "SELECT * FROM lienhe" . $where_sql . " ORDER BY id DESC LIMIT ?, ?";

$list_params = $params;
$list_params[] = $start;
$list_params[] = $limit;
$list_types = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($list_types, ...$list_params);
$stmt->execute();
$result = $stmt->get_result(); // Chỉ dùng duy nhất biến $result này cho vòng lặp while ở HTML
?>
<div class="hh-page">
   <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL / ADMIN</span>
            <h1>Quản lý liên hệ</h1>
            <p>Theo dõi tình trạng và thông tin liên hệ trong khách sạn.</p>
        </div>
    </div>

       <div class="hh-stat-grid">
        <div class="hh-stat-card"><span>Tổng số liên hệ</span><strong><?= (int)$stats['total'] ?></strong><small>Toàn hệ thống</small></div>
    </div>
      <div class="hh-card-heading">
        <h2>Danh sách liên hệ</h2>
    </div>
    <form method="GET" action="admin.php" class="hh-filter-bar">
    <!-- Giữ lại tên page hiện tại trên URL để không bị đá ra trang chủ khi submit -->
    <?php if (isset($_GET['page'])): ?>
        <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
    <?php endif; ?>

    <!-- Ô nhập từ khóa tìm kiếm theo tên khách hoặc email -->
    <input type="text"
        name="q"
        placeholder="Nhập tên khách hàng hoặc email..."
        value="<?= htmlspecialchars($keyword) ?>">

    <button type="submit" class="hh-btn hh-btn-primary">Lọc dữ liệu</button>

    <!-- Nút xóa bộ lọc, chỉ hiện khi đang có từ khóa tìm kiếm -->
    <?php if ($keyword !== ''): ?>
        <a href="admin.php<?= isset($_GET['page']) ? '?page=' . urlencode($_GET['page']) : '' ?>" class="hh-btn">Đặt lại</a>
    <?php endif; ?>
</form>
    <div class="hh-table-wrap">
        <table class="hh-table">
            <tr>
                <th>ID</th>
                <th>Họ và tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Nội dung</th>
                <th>Thời gian</th>
                <th>Thao tác</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id'] ?? ''; ?></td>
                <td><?php echo $row['hoten'] ?? ''; ?></td>
                <td><?php echo $row['email'] ?? ''; ?></td>
                <td><?php echo $row['sdt'] ?? ''; ?></td>
                <td><?php echo $row['noidung'] ?? ''; ?></td>
                <td><?php echo $row['thoigian'] ?? ''; ?></td>
                <td><a href="admin.php?page=contactManagement&id=<?= $row['id'] ?>"
                style="background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">Xóa</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div> 
<div class="pagination">

    <?php if ($page > 1) { ?>
        <a href="?page=contactManagement&page_num=<?= $page - 1 ?>">
            Trước
        </a>
    <?php } ?>

    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
        <a href="?page=contactManagement&page_num=<?= $i ?>"
           class="<?= ($page == $i) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php } ?>

    <?php if ($page < $total_pages) { ?>
        <a href="?page=contactManagement&page_num=<?= $page + 1 ?>">
            Sau
        </a>
    <?php } ?>

</div>