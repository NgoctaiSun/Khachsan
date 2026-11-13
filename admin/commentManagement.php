<?php
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

checkRole(['admin']);

// 1. Lấy tham số tìm kiếm & lọc từ URL
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$star    = (isset($_GET['star']) && $_GET['star'] !== '') ? (int)$_GET['star'] : '';

// 2. Xây dựng điều kiện WHERE dùng chung cho cả Đếm tổng và Lấy danh sách
$base_join = "FROM binhluan bl
              LEFT JOIN taikhoan kh ON bl.id_taikhoan = kh.id
              LEFT JOIN phong p ON bl.id_phong = p.id
              LEFT JOIN loaiphong lp ON p.id_loaiphong = lp.id
              WHERE 1=1";

$where_sql = "";
$params = [];
$types  = "";

if ($keyword !== '') {
    $where_sql .= " AND (lp.tenphong LIKE ? OR p.sophong LIKE ? OR kh.hoten LIKE ? OR bl.noidung LIKE ?)";
    $searchTerm = "%" . $keyword . "%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $types .= "ssss";
}

if ($star !== '' && $star >= 1 && $star <= 5) {
    $where_sql .= " AND bl.sao = ?";
    $params[] = $star;
    $types .= "i";
}

// 3. Cấu hình phân trang (Tính tổng theo đúng điều kiện đang lọc)
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

// 4. Truy vấn danh sách bình luận có phân trang
// Giữ nguyên tên cột kh.hoten để khớp với bảng HTML bên dưới
$sql = "SELECT bl.*, kh.hoten, p.sophong, lp.tenphong "
     . $base_join . $where_sql 
     . " ORDER BY bl.id DESC LIMIT ?, ?";

$list_params = $params;
$list_params[] = $start;
$list_params[] = $limit;
$list_types = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($list_types, ...$list_params);
$stmt->execute();
$result = $stmt->get_result(); // Giữ nguyên biến $result cho vòng lặp while ở HTML

// 5. Thống kê tổng bình luận toàn hệ thống
$countResult = $conn->query("
    SELECT
      COUNT(*) total,
      SUM(trangthai='danghienthi') active,
      SUM(trangthai='dangan') blocked
    FROM binhluan
");
$stats = $countResult->fetch_assoc();
?>

<div class="hh-page">
    <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL / ADMIN</span>
            <h1>Quản lý bình luận</h1>
            <p>Theo dõi tình trạng và thông tin bình luận trong khách sạn.</p>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
        </div>
        </div>
    
       <div class="hh-stat-grid">
        <div class="hh-stat-card"><span>Tổng số bình luận</span><strong><?= (int)$stats['total'] ?></strong><small>Toàn hệ thống</small></div>
        <div class="hh-stat-card"><span>Bình luận đang hiển thị</span><strong><?= (int)$stats['active'] ?></strong><small>Đang hiển thị</small></div>
        <div class="hh-stat-card"><span>Bình luận bị ẩn</span><strong><?= (int)$stats['blocked'] ?></strong><small>Đang bị ẩn</small></div>
    </div>
        
    </div>
      <div class="hh-card-heading">
        <h2>Danh sách bình luận</h2>
    </div>
  <form method="GET" action="admin.php" class="hh-filter-bar">
    <!-- Giữ lại tên page hiện tại trên URL để không bị đá ra trang chủ khi submit -->
    <?php if (isset($_GET['page'])): ?>
        <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
    <?php endif; ?>

    <!-- Ô nhập từ khóa tìm kiếm -->
    <input type="text"
        name="q"
        placeholder="Tìm tên loại phòng, số phòng, khách hàng, nội dung..."
        value="<?= htmlspecialchars($keyword) ?>"
        style="min-width: 300px;">

    <!-- Dropdown lọc theo số sao -->
    <select name="star" class="hh-select">
        <option value="">-- Tất cả số sao --</option>
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>" <?= ($star === $i) ? 'selected' : '' ?>>
                <?= $i ?> sao
            </option>
        <?php endfor; ?>
    </select>

    <button type="submit" class="hh-btn hh-btn-primary">Lọc dữ liệu</button>

    <!-- Nút xóa bộ lọc: hiện khi có từ khóa HOẶC có chọn lọc số sao -->
    <?php if ($keyword !== '' || $star !== ''): ?>
        <a href="admin.php<?= isset($_GET['page']) ? '?page=' . urlencode($_GET['page']) : '' ?>" class="hh-btn">Đặt lại</a>
    <?php endif; ?>
</form>
    <div class="hh-table-wrap">
        <table class="hh-table">
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
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['tenphong'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['sophong'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['hoten'] ?? '') ?></td>
                        <td>
                            <p class="mb-1 text-muted small" style="max-width: 300px;"><?= htmlspecialchars($row['noidung'] ?? '') ?></p>

                            <?php if (!empty($row['phanhoi_admin'])): ?>
                                <div class="p-2 bg-light border-start border-3 border-success rounded text-dark small mt-1">
                                    <strong class="text-success"><i class="fa-solid fa-reply me-1"></i>Admin:</strong> <?= htmlspecialchars($row['phanhoi_admin']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-warning fw-bold">
                                <?= htmlspecialchars($row['sao'] ?? '') ?> <i class="fa-solid fa-star text-warning"></i>
                            </span>
                        </td>
                        <td><span class="hh-status hh-status-<?= $row['trangthai'] === 'danghienthi' ? 'green' : 'red' ?>"><?= htmlspecialchars($row['trangthai'] ?? '') ?></span></td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <!-- Nút Trả lời bình luận -->
                                <button type="button" class="btn-submit" style="width:15px;"
                                    onclick="openReplyModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['traloi_admin'] ?? '')) ?>')"
                                    title="Trả lời bình luận">
                                    ↩
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
                    placeholder="Nhập câu trả lời của shop..."></textarea>

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

    .btn-reply {
        color: #0d6efd;
        border-color: #0d6efd;
    }

    .btn-reply:hover {
        background: #0d6efd;
        color: #fff;
    }

    .btn-reply.is-replied {
        background: #198754;
        color: #fff;
        border-color: #198754;
    }

    .btn-toggle {
        color: #fd7e14;
        border-color: #fd7e14;
    }

    .btn-toggle:hover {
        background: #fd7e14;
        color: #fff;
    }

    .btn-delete {
        color: #dc3545;
        border-color: #dc3545;
    }

    .btn-delete:hover {
        background: #dc3545;
        color: #fff;
    }
</style>