<?php 
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin']);


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
                             <button type="button" class="btn-icon btn-reply" style="width:15px;"
                                     onclick="openReplyModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['traloi_admin'] ?? '')) ?>')"
                                     title="Trả lời bình luận">
                                 <i class="fa-solid fa-reply"></i>
                             </button>

                             <!-- Nút Ẩn / Hiện bình luận -->
                              <?php if (($row['trangthai'] ?? '') === 'dangan'): ?>
                            <a href="process_comment.php?id=<?= $row['id'] ?>&action=danghienthi" 
                               onclick="return confirm('Bạn có chắc chắn muốn HIỂN THỊ bình luận này?')"
                               style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                               Hiển thị
                            </a>
                        <?php else: ?>
                            <a href="process_comment.php?id=<?= $row['id'] ?>&action=dangan" 
                               onclick="return confirm('Bạn có chắc chắn muốn Ẩn bình luận này?')"
                               style="background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                               Ẩn
                            </a>
                        <?php endif; ?>

                             <!-- Nút Xóa bình luận có xác nhận -->
                             <a href="process_comment.php?action=delete&id=<?= $row['id'] ?>" 
                                class="btn-icon btn-delete" 
                                title="Xóa vĩnh viễn"
                                onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn bình luận này không vậy?')">
                                Xóa
                             </a>
                         </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div> 

<!-- MODAL TRẢ LỜI BÌNH LUẬN -->
<div id="modalTraLoiBinhLuan" class="reply-modal">
    <div class="reply-modal-content">

        <div class="reply-modal-header">
            <h3>
                <i class="fa-solid fa-reply"></i>
                Trả lời bình luận
            </h3>

            <button type="button" class="reply-close"
                    onclick="closeReplyModal()">
                &times;
            </button>
        </div>

        <form action="process_comment.php" method="POST">

            <div class="reply-modal-body">

                <input type="hidden"
                       name="id_binhluan"
                       id="reply_id_binhluan">

                <label for="reply_noidung">
                    Nội dung phản hồi từ Admin:
                </label>

                <textarea
                    name="phanhoi_admin"
                    id="reply_noidung"
                    rows="5"
                    placeholder="Nhập câu trả lời của shop..."
                    required></textarea>

            </div>

            <div class="reply-modal-footer">
                <button type="button"
                        class="btn-cancel"
                        onclick="closeReplyModal()">
                    Hủy
                </button>

                <button type="submit" class="btn-submit" name="action" value="reply">
                    <i class="fa-solid fa-paper-plane"></i>
                    Gửi phản hồi
                </button>
            </div>

        </form>
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
<script>
// Hàm mở Modal Trả lời
function openReplyModal(id, currentReply) {

    document.getElementById('reply_id_binhluan').value = id;

    document.getElementById('reply_noidung').value = currentReply;

    document.getElementById('modalTraLoiBinhLuan').style.display = 'flex';
}

// Đóng Modal
function closeReplyModal() {
    document.getElementById('modalTraLoiBinhLuan').style.display = 'none';
}

// Bấm ra ngoài Modal để đóng
document.getElementById('modalTraLoiBinhLuan').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReplyModal();
    }
});

// Nhấn ESC để đóng Modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReplyModal();
    }
});
</script>
<style>
    /* Lớp nền Modal */
.reply-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);

    justify-content: center;
    align-items: center;

    padding: 15px;
}

/* Nội dung Modal */
.reply-modal-content {
    background: #fff;
    width: 100%;
    max-width: 500px;
    border-radius: 12px;
    overflow: hidden;

    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);

    animation: modalShow 0.25s ease;
}

/* Hiệu ứng xuất hiện */
@keyframes modalShow {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Header */
.reply-modal-header {
    background: #198754;
    color: white;
    padding: 16px 20px;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.reply-modal-header h3 {
    margin: 0;
    font-size: 19px;
    font-weight: 600;
}

.reply-close {
    border: none;
    background: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
}

/* Body */
.reply-modal-body {
    padding: 25px 20px;
}

.reply-modal-body label {
    display: block;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.reply-modal-body textarea {
    width: 100%;
    min-height: 120px;
    padding: 12px;
    border: 1px solid #ced4da;
    border-radius: 8px;
    resize: vertical;
    font-family: inherit;
    font-size: 14px;
    box-sizing: border-box;
    outline: none;
}

.reply-modal-body textarea:focus {
    border-color: #198754;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.12);
}

/* Footer */
.reply-modal-footer {
    background: #f8f9fa;
    padding: 15px 20px;

    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.reply-modal-footer button {
    padding: 9px 18px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
}

.btn-cancel {
    background: #6c757d;
    color: white;
}

.btn-submit {
    background: #198754;
    color: white;
}

.btn-cancel:hover {
    background: #565e64;
}

.btn-submit:hover {
    background: #146c43;
}
/* Action Buttons */
.action-buttons {
    display: flex;
    justify-content: center;
    gap: 6px;
}
.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    background: #fff;
    cursor: pointer;
    text-decoration: none;
    color: #43494f;
    transition: all 0.2s;
}
.btn-reply { color: #0d6efd; border-color: #0d6efd; }
.btn-reply:hover { background: #0d6efd; color: #fff; }
.btn-reply.is-replied { background: #198754; color: #fff; border-color: #198754; }

.btn-toggle { color: #fd7e14; border-color: #fd7e14; }
.btn-toggle:hover { background: #fd7e14; color: #fff; }

.btn-delete { color: #dc3545; border-color: #dc3545; }
.btn-delete:hover { background: #dc3545; color: #fff; }
</style>