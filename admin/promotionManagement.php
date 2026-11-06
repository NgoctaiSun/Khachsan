<?php 
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin']);
$limit = 10;
//1. Xác định vị trí bắt đầu lấy dữ liệu
// Lấy số trang hiện tại từ URL
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;

// Không cho phép trang nhỏ hơn 1
if ($page < 1) {
    $page = 1;
}

// Xác định vị trí bắt đầu lấy dữ liệu
$start = ($page - 1) * $limit;
$sql_count = "SELECT COUNT(*) AS total FROM khuyenmai";

$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);

$total_records = $row_count['total'];

// Tính tổng số trang
$total_pages = ceil($total_records / $limit);
// Truy vấn danh sách khuyến mãi và loại phòng
$sql = "SELECT 
            km.id AS id_khuyenmai,
            km.id_loaiphong,
            km.dem,
            km.phantramgiamgia,
            km.trangthai,
            lp.tenphong
        FROM khuyenmai km
        JOIN loaiphong lp ON km.id_loaiphong = lp.id";;
$result = mysqli_query($conn, $sql);

// 2. Xử lý lưu form POST trước khi SELECT dữ liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'them_khuyen_mai') {
    $loaiphong = trim($_POST['loaiphong'] ?? '');
    $dem= trim($_POST['dem'] ?? '');
    $phantramgiamgia = trim($_POST['phantramgiamgia'] ?? '');
    $kiemtra = "SELECT id FROM loaiphong WHERE tenphong = '$loaiphong'";

    $result_kiemtra = mysqli_query($conn, $kiemtra);
    if (mysqli_num_rows($result_kiemtra) > 0) {
        $row = mysqli_fetch_assoc($result_kiemtra);
        $id_loaiphong = $row['id'];
        $save="INSERT INTO khuyenmai (id_loaiphong, dem, phantramgiamgia, trangthai) VALUES ($id_loaiphong, $dem, $phantramgiamgia, 'dangapdung')";
        mysqli_query($conn, $save);
    }
}


// 3. Tạm ngưng hoặc áp dụng khuyến mãi
if (isset($_GET['id']) && isset($_GET['trangthai'])) {
    $id = intval($_GET['id']);
    $trangthai_moi = $_GET['trangthai'];

    // Chỉ cho phép 2 giá trị này để tránh lỗi bảo mật SQL Injection
    if ($trangthai_moi === 'tamngung' || $trangthai_moi === 'dangapdung') {
        $sql = "UPDATE khuyenmai SET trangthai = '$trangthai_moi' WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            header("Location: admin.php?page=promotionManagement&msg=success");
            exit();
        } else {
            echo "Lỗi khi cập nhật: " . mysqli_error($conn);
        }
    }
} 
?>



<div>
    <h1>Quản lý khuyến mãi</h1>
    <button id="btn" class="btn-add">+ Thêm khuyến mãi mới</button>

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
    <div>
        <table class="admin__table">
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
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr> 
                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['tenphong'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['dem'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['phantramgiamgia'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['trangthai'] ?? '') ?></td>
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