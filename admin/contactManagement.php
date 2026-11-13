<?php 
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin']);
// Số tài khoản hiển thị mỗi trang
$limit = 10;

// Lấy số trang hiện tại từ URL
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;

// Không cho phép trang nhỏ hơn 1
if ($page < 1) {
    $page = 1;
}

// Xác định vị trí bắt đầu lấy dữ liệu
$start = ($page - 1) * $limit;
$sql_count = "SELECT COUNT(*) AS total FROM lienhe";

$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);

$total_records = $row_count['total'];

// Tính tổng số trang
$total_pages = ceil($total_records / $limit);
$sql="SELECT * FROM lienhe LIMIT $start, $limit";
$result=mysqli_query($conn,$sql);
// Xử lý xóa liên hệ
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $delete_sql = "DELETE FROM lienhe WHERE id = $id";
    if(mysqli_query($conn, $delete_sql)) {
        header("Location: admin.php?page=commentManagement&msg=success");
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}
// Tổng liên hệ
$countResult = $conn->query("
    SELECT
      COUNT(*) total
    FROM lienhe
");
$stats = $countResult->fetch_assoc();
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