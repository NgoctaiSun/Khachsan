<?php 
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin']);

// Xử lý xóa bình luận an toàn hơn khi có action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $delete_sql = "DELETE FROM binhluan WHERE id = $id";
    if (mysqli_query($conn, $delete_sql)) {
        header("Location: admin.php?page=commentManagement&msg=success");
        exit();
    } else {
        $error_message = "Error deleting record: " . mysqli_error($conn);
    }
}

// Số bản ghi hiển thị mỗi trang
$limit = 10;

// Lấy số trang hiện tại từ URL
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;

// Không cho phép trang nhỏ hơn 1
if ($page < 1) {
    $page = 1;
}

// Xác định vị trí bắt đầu lấy dữ liệu
$start = ($page - 1) * $limit;

$sql_count = "SELECT COUNT(*) AS total FROM binhluan";
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total'];

// Tính tổng số trang
$total_pages = ceil($total_records / $limit);

$sql = "SELECT binhluan.*, taikhoan.hoten, phong.sophong, loaiphong.tenphong FROM binhluan  
JOIN taikhoan ON binhluan.id_taikhoan = taikhoan.id
JOIN phong ON binhluan.id_phong = phong.id
JOIN loaiphong ON phong.id_loaiphong = loaiphong.id
LIMIT $start, $limit";

$result = mysqli_query($conn, $sql);
?>

<div>
    <div>
        <h1>Quản lý bình luận</h1>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
    </div>
    <div>
        <table class="admin__table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên loại phòng</th>
                    <th>Số phòng</th>
                    <th>Tên khách hàng</th>
                    <th>Nội dung</th>
                    <th>Số sao</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['tenphong'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['sophong'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['hoten'] ?? '') ?></td>
                    <td>
                         <p class="mb-1 text-muted small" style="max-width: 300px;"><?= htmlspecialchars($row['noidung'] ?? '') ?></p>
                                    
                         <?php if (!empty($row['traloi_admin'])): ?>
                             <div class="p-2 bg-light border-start border-3 border-success rounded text-dark small mt-1">
                                 <strong class="text-success"><i class="fa-solid fa-reply me-1"></i>Admin:</strong> <?= htmlspecialchars($row['traloi_admin']) ?>
                             </div>
                         <?php endif; ?>
                    </td>
                    <td>
                        <span class="text-warning fw-bold">
                            <?= htmlspecialchars($row['sosao'] ?? '') ?> <i class="fa-solid fa-star text-warning"></i>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['trangthai'] ?? '') ?></td>
                    <td>
                         <div class="d-flex justify-content-center gap-2">
                             <!-- Nút Trả lời bình luận -->
                             <button type="button" class="btn btn-sm btn-outline-primary" 
                                     onclick="openReplyModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['traloi_admin'] ?? '')) ?>')"
                                     title="Trả lời bình luận">
                                 <i class="fa-solid fa-reply"></i>
                             </button>

                             <!-- Nút Ẩn / Hiện bình luận -->
                             <a href="kiemsoat_binhluan.php?action=toggle_status&id=<?= $row['id'] ?>&current=<?= $row['trangthai'] ?>" 
                                class="btn btn-sm <?= ($row['trangthai'] == '1' || $row['trangthai'] == 'Hiển thị') ? 'btn-outline-warning' : 'btn-outline-success' ?>" 
                                title="<?= ($row['trangthai'] == '1' || $row['trangthai'] == 'Hiển thị') ? 'Ẩn bình luận này' : 'Hiện bình luận này' ?>">
                                 <i class="fa-solid <?= ($row['trangthai'] == '1' || $row['trangthai'] == 'Hiển thị') ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                             </a>

                             <!-- Nút Xóa bình luận có xác nhận -->
                             <a href="admin.php?page=commentManagement&action=delete&id=<?= $row['id'] ?>" 
                                class="btn btn-sm btn-outline-danger" 
                                title="Xóa vĩnh viễn"
                                onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn bình luận này không?')">
                                 <i class="fa-solid fa-trash"></i>
                             </a>
                         </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div> 

<!-- MODAL POPUP ADMIN TRẢ LỜI BÌNH LUẬN -->
<div class="modal fade" id="modalTraLoiBinhLuan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-reply me-2"></i>Trả lời bình luận</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="xuly_traloibinhluan.php" method="POST">
          <div class="modal-body p-4">
            <input type="hidden" name="id_binhluan" id="reply_id_binhluan">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nội dung phản hồi từ Admin:</label>
                <textarea class="form-control" name="traloi_admin" id="reply_noidung" rows="4" placeholder="Nhập câu trả lời của shop..." required></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Gửi phản hồi</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- PHÂN TRANG -->
<div class="pagination">
    <?php if ($page > 1) { ?>
        <a href="?page=commentManagement&page_num=<?= $page - 1 ?>">
            Trước
        </a>
    <?php } ?>

    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
        <a href="?page=commentManagement&page_num=<?= $i ?>"
           class="<?= ($page == $i) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php } ?>

    <?php if ($page < $total_pages) { ?>
        <a href="?page=commentManagement&page_num=<?= $page + 1 ?>">
            Sau
        </a>
    <?php } ?>
</div>