<?php 
include 'connect.php'; 

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
?>

<!-- BANNER HOÀNH TRÁNG FULL SCREEN MẪU RESORT 5 SAO -->
<div class="luxury-rooms-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="luxury-badge">✦ HARMONY RESORT & SUITES ✦</div>
        <h1>TUYỆT TÁC KHÔNG GIAN NGHỈ DƯỠNG</h1>
        <p>Thưởng ngoạn kiến trúc thượng lưu, dịch vụ riêng tư đỉnh cao và tầm nhìn ôm trọn vẻ đẹp thiên nhiên</p>
        <div class="scroll-down-icon">↓</div>
    </div>
</div>

<div class="grand-container">

    <!-- BỘ LỌC ĐA NĂNG SANG TRỌNG -->
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

    <!-- DANH SÁCH PHÒNG HOÀNH TRÁNG -->
    <div class="grand-room-list">
        <?php 
        if ($result && mysqli_num_rows($result) > 0): 
            $i = 0;
            while ($row = mysqli_fetch_assoc($result)): 
                $i++;
        ?>
            <div class="grand-room-card">
                <!-- Hình ảnh lớn kèm Badge nổi -->
                <div class="grand-room-image">
                    <img src="images/<?= htmlspecialchars($row['anhphong']) ?>" 
                         alt="<?= htmlspecialchars($row['tenphong']) ?>"
                         onerror="this.onerror=null; this.src='images/banner.jpg';">
                    
                    <div class="image-tag-top">★ LUXURY ROOM</div>
                    <div class="image-price-badge">
                        <span>Giá từ</span>
                        <strong><?= number_format($row['gia'], 0, ',', '.') ?></strong>
                        <small>VND / đêm</small>
                    </div>
                </div>

                <!-- Thông tin phòng cực chi tiết -->
                <div class="grand-room-details">
                    <div class="room-stars">★★★★★</div>
                    <h2 class="room-name"><?= htmlspecialchars($row['tenphong']) ?></h2>

                    <!-- Tiện nghi dạng Icon / Chip -->
                    <div class="amenities-chips">
                        <span class="chip">👥 <?= htmlspecialchars($row['sokhach']) ?> Khách lớn</span>
                        <span class="chip">📐 <?= htmlspecialchars($row['dientich']) ?></span>
                        <span class="chip">📶 Wifi Tốc độ cao</span>
                        <span class="chip">☕ Ăn sáng miễn phí</span>
                        <span class="chip">🏊 Hồ bơi vô cực</span>
                    </div>

                    <p class="room-description">
                        <?= !empty($row['mota']) ? htmlspecialchars($row['mota']) : 'Không gian nghỉ dưỡng đạt chuẩn 5 sao quốc tế, tích hợp ban công hướng view thiên nhiên đắt giá cùng nội thất gỗ tự nhiên sang trọng, đáp ứng trọn vẹn sự riêng tư của du khách.' ?>
                    </p>

                    <div class="grand-card-footer">
                        <div class="guarantee-text">✓ Đảm bảo giá tốt nhất | Không chi phí ẩn</div>
                        <div class="footer-buttons">
                            <a href="index.php?page=room_detail&id=<?= $row['id'] ?>" class="btn-outline-gold">XEM CHI TIẾT</a>
                            <a href="index.php?page=booking&id=<?= $row['id'] ?>" class="btn-solid-gold">ĐẶT PHÒNG NGAY</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
            endwhile; 
        else: 
        ?>
            <!-- Thông báo khi không tìm thấy phòng -->
            <div class="no-results-box">
                <div class="no-results-icon">🏨</div>
                <h3>Không tìm thấy phòng phù hợp với yêu cầu của bạn</h3>
                <p>Bạn hãy thử thay đổi mức giá, số lượng khách hoặc bấm nút bên dưới để xem tất cả các phòng.</p>
                <a href="index.php?page=rooms" class="btn-reset-grand">XÓA BỘ LỌC & XEM TẤT CẢ</a>
            </div>
        <?php endif; ?>
    </div>

</div>
