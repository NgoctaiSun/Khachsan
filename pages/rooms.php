<?php 
require_once __DIR__ . '/../connect.php'; 

// Xử lý bộ lọc tìm kiếm linh hoạt
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$sokhach = isset($_GET['sokhach']) ? (int)$_GET['sokhach'] : 0;
$mugiagia = isset($_GET['mugiagia']) ? $_GET['mugiagia'] : '';

$where = "WHERE 1=1";

if (!empty($keyword)) {
    $where .= " AND (tenphong LIKE '%" . mysqli_real_escape_string($conn, $keyword) . "%' OR mota LIKE '%" . mysqli_real_escape_string($conn, $keyword) . "%')";
}

if ($sokhach > 0) {
    $where .= " AND sokhach >= $sokhach";
}

if ($mugiagia === 'duoi2m') {
    $where .= " AND gia < 2000000";
} else if ($mugiagia === '2m-4m') {
    $where .= " AND gia BETWEEN 2000000 AND 4000000";
} else if ($mugiagia === 'tren4m') {
    $where .= " AND gia > 4000000";
}

$sql = "SELECT * FROM loaiphong $where ORDER BY gia ASC";
$result = mysqli_query($conn, $sql);
$queryError = $result === false ? mysqli_error($conn) : '';
?>

<!-- BANNER HERO SANG TRỌNG -->
<div class="luxury-rooms-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="luxury-badge">✦ HARMONY RESORT & SUITES ✦</div>
        <h1>TUYỆT TÁC KHÔNG GIAN NGHỈ DƯỠNG</h1>
        <p>Tận hưởng sự thư thái tuyệt đối với thiết kế tinh tế, tầm nhìn thiên nhiên và dịch vụ chuẩn 5 sao quốc tế</p>
        <div class="scroll-down-icon">↓</div>
    </div>
</div>

<div class="grand-container">

    <!-- BỘ LỌC ĐA NĂNG -->
    <div class="grand-filter-card">
        <form action="index.php" method="get" class="grand-filter-grid">
            <input type="hidden" name="page" value="rooms">

            <div class="filter-group">
                <label>🔍 TÌM THEO TÊN PHÒNG</label>
                <input type="text" name="keyword" class="grand-input" placeholder="Nhập tên phòng..." value="<?= htmlspecialchars($keyword) ?>">
            </div>

            <div class="filter-group">
                <label>👥 SỐ LƯỢNG KHÁCH</label>
                <select name="sokhach" class="grand-select">
                    <option value="0">Tất cả số khách</option>
                    <option value="1" <?= $sokhach == 1 ? 'selected' : '' ?>>1 Khách (Phòng Đơn)</option>
                    <option value="2" <?= $sokhach == 2 ? 'selected' : '' ?>>2 Khách (Phòng Đôi)</option>
                    <option value="4" <?= $sokhach == 4 ? 'selected' : '' ?>>Gia đình (từ 4 Khách)</option>
                </select>
            </div>

            <div class="filter-group">
                <label>💎 MỨC GIÁ / ĐÊM</label>
                <select name="mugiagia" class="grand-select">
                    <option value="">Tất cả khoảng giá</option>
                    <option value="duoi2m" <?= $mugiagia === 'duoi2m' ? 'selected' : '' ?>>Dưới 2.000.000đ</option>
                    <option value="2m-4m" <?= $mugiagia === '2m-4m' ? 'selected' : '' ?>>2.000.000đ - 4.000.000đ</option>
                    <option value="tren4m" <?= $mugiagia === 'tren4m' ? 'selected' : '' ?>>Trên 4.000.000đ</option>
                </select>
            </div>

            <div class="filter-group button-container">
                <button type="submit" class="btn-grand-search">TÌM PHÒNG NGAY</button>
            </div>
        </form>
    </div>

    <!-- DANH SÁCH PHÒNG -->
    <div class="grand-room-list">
        <?php if ($queryError !== ''): ?>
            <div class="no-results-box" style="border:1px solid #fecaca; background:#fff7f7;">
                <div class="no-results-icon">⚠️</div>
                <h3>Không thể tải danh sách phòng</h3>
                <p style="color:#b91c1c;"><strong>Lỗi CSDL:</strong> <?= htmlspecialchars($queryError) ?></p>
                <p>Kiểm tra MySQL/XAMPP và database <strong>khachsan_db</strong>. Nếu database của bạn có tên khác, hãy sửa tên trong <code>connect.php</code>.</p>
            </div>
        <?php elseif ($result && mysqli_num_rows($result) > 0): 
            while ($row = mysqli_fetch_assoc($result)): 
                /*
                 * Chuẩn hóa ảnh phòng.
                 * DB có thể lưu: A1.jpg, images/A1.jpg, /images/A1.jpg,
                 * images/rooms/A1.jpg... nên không nối "images/" mù quáng.
                 */
                $imgName = trim((string)($row['anhphong'] ?? ''));
                $imgPath = 'images/A1.jpg';

                // Ảnh fallback có thật trong project.
                $fallbackImages = ['A1.jpg', 'A2.jpg', 'A3.jpg', 'A4.jpg', 'A5.jpg', 'A6.jpg'];
                $fallback = $fallbackImages[((int)$row['id'] - 1) % count($fallbackImages)];

                if ($imgName !== '') {
                    $cleanImg = preg_replace('/[?#].*$/', '', str_replace('\\', '/', $imgName));
                    $cleanImg = ltrim($cleanImg, '/');

                    $candidates = [
                        $cleanImg,
                        preg_replace('#^images/#i', '', $cleanImg),
                        preg_replace('#^images/rooms/#i', '', $cleanImg),
                    ];

                    foreach ($candidates as $candidate) {
                        $candidate = ltrim($candidate, '/');
                        if ($candidate === '') continue;

                        if (is_file(__DIR__ . '/../' . $candidate)) {
                            $imgPath = $candidate;
                            break;
                        }
                        if (is_file(__DIR__ . '/../images/' . $candidate)) {
                            $imgPath = 'images/' . $candidate;
                            break;
                        }
                        if (is_file(__DIR__ . '/../images/rooms/' . $candidate)) {
                            $imgPath = 'images/rooms/' . $candidate;
                            break;
                        }
                    }
                }

                if (!is_file(__DIR__ . '/../' . ltrim($imgPath, '/'))) {
                    $imgPath = 'images/' . $fallback;
                }

        ?>
            <div class="grand-room-card">
                <!-- Thẻ chứa hình ảnh phòng -->
                <div class="grand-room-image">
                    <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') ?>" 
                         alt="<?= htmlspecialchars($row['tenphong']) ?>"
                         onerror="this.onerror=null; this.src='images/<?= $fallback ?>';">
                    
                    <div class="image-tag-top">★ LUXURY SUITE</div>
                    <div class="image-price-badge">
                        <span>Giá chỉ từ</span>
                        <strong><?= number_format($row['gia'], 0, ',', '.') ?></strong>
                        <small>VND / đêm</small>
                    </div>
                </div>

                <!-- Chi tiết thông tin phòng -->
                <div class="grand-room-details">
                    <div>
                        <div class="room-stars">★★★★★</div>
                        <h2 class="room-name"><?= htmlspecialchars($row['tenphong']) ?></h2>

                        <!-- Chip Tiện nghi -->
                        <div class="amenities-chips">
                            <span class="chip">👥 <?= htmlspecialchars($row['sokhach']) ?> Khách tiêu chuẩn</span>
                            <span class="chip">📐 <?= htmlspecialchars($row['dientich']) ?> m²</span>
                            <span class="chip">📶 Wifi Tốc độ cao</span>
                            <span class="chip">☕ Bữa sáng thượng hạng</span>
                            <span class="chip">🏊 Sử dụng hồ bơi vô cực</span>
                        </div>

                        <p class="room-description">
                            <?= !empty($row['mota']) ? htmlspecialchars($row['mota']) : 'Phòng được thiết kế theo phong cách hiện đại tích hợp không gian mở thoáng đãng, trang bị nội thất cao cấp mang lại cảm giác thư thái và tiện nghi tối đa cho kỳ nghỉ của bạn.' ?>
                        </p>
                    </div>

                    <div class="grand-card-footer">
                        <div class="guarantee-text">✓ Đảm bảo giá tốt nhất | Hỗ trợ đặt phòng 24/7</div>
                        <div class="footer-buttons">
                            <!-- Nút Xem Nhanh Popup -->
                            <button type="button" class="btn-outline-gold" onclick='openRoomModal(<?= json_encode([
                                "id" => $row["id"],
                                "tenphong" => $row["tenphong"],
                                "gia" => number_format($row["gia"], 0, ",", "."),
                                "sokhach" => $row["sokhach"],
                                "dientich" => $row["dientich"],
                                "mota" => $row["mota"],
                                "img" => $imgPath
                            ]) ?>)'>XEM NHANH</button>
                            
                            <!-- Nút Xem Chi Tiết -->
                            <a href="index.php?page=room_detail&id=<?= $row['id'] ?>" class="btn-solid-gold">XEM CHI TIẾT</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
            endwhile; 
        else: 
        ?>
            <!-- Không tìm thấy phòng -->
            <div class="no-results-box">
                <div class="no-results-icon">🏨</div>
                <h3>Chưa có dữ liệu loại phòng</h3>
                <p>Hiện truy vấn không trả về loại phòng nào. Vì bạn đang không áp dụng bộ lọc, hãy kiểm tra bảng <strong>loaiphong</strong> trong database.</p>
                <p style="font-size:13px;color:#64748b;">Nếu trang chủ cũng không có phòng, nguyên nhân gần như chắc chắn là database chưa import dữ liệu hoặc tên database trong <code>connect.php</code> chưa đúng.</p>
                <a href="index.php?page=rooms" class="btn-reset-grand">XÓA BỘ LỌC & XEM TẤT CẢ PHÒNG</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL POPUP XEM NHANH PHÒNG -->
<div id="quickViewModal" class="room-modal">
    <div class="room-modal-content">
        <span class="close-modal" onclick="closeRoomModal()">&times;</span>
        <div class="modal-body-grid">
            <div class="modal-img-wrap">
                <img id="modalImg" src="" alt="Hình ảnh phòng">
            </div>
            <div class="modal-info-wrap">
                <div class="room-stars">★★★★★</div>
                <h2 id="modalTitle" style="color:#0f172a; margin: 5px 0;"></h2>
                <div class="modal-price"><span id="modalPrice"></span> <small style="font-size:14px; font-weight:normal; color:#64748b;">VND / đêm</small></div>
                
                <div class="amenities-chips" style="margin: 15px 0;">
                    <span class="chip" id="modalGuests"></span>
                    <span class="chip" id="modalArea"></span>
                    <span class="chip">📶 Wifi Tốc độ cao</span>
                </div>
                
                <p id="modalDesc" class="room-description" style="max-height: 120px; overflow-y: auto;"></p>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <a id="modalDetailBtn" href="#" class="btn-outline-gold" style="flex:1; text-align:center;">XEM TRANG CHI TIẾT</a>
                    <a id="modalBookBtn" href="#" class="btn-solid-gold" style="flex:1; text-align:center;">ĐẶT PHÒNG NGAY</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openRoomModal(room) {
    document.getElementById('modalTitle').innerText = room.tenphong;
    document.getElementById('modalPrice').innerText = room.gia;
    document.getElementById('modalGuests').innerText = '👥 ' + room.sokhach + ' Khách';
    document.getElementById('modalArea').innerText = '📐 ' + room.dientich + ' m²';
    document.getElementById('modalDesc').innerText = room.mota || 'Không gian nghỉ dưỡng đạt chuẩn 5 sao sang trọng, đầy đủ tiện nghi hiện đại.';
    document.getElementById('modalImg').src = room.img;
    
    document.getElementById('modalDetailBtn').href = 'index.php?page=room_detail&id=' + room.id;
    document.getElementById('modalBookBtn').href = 'index.php?page=booking&id=' + room.id;
    
    document.getElementById('quickViewModal').style.display = 'flex';
}

function closeRoomModal() {
    document.getElementById('quickViewModal').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('quickViewModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>
