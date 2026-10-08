<?php
require_once __DIR__ . '/../connect.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM loaiphong WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    echo '<div class="grand-container" style="padding: 60px 20px; text-align:center;"><h2>Rất tiếc! Không tìm thấy thông tin loại phòng này.</h2><br><a href="index.php?page=rooms" class="btn-solid-gold">QUAY LẠI DANH SÁCH PHÒNG</a></div>';
    exit;
}

$roomsStmt = $conn->prepare("SELECT * FROM phong WHERE id_loaiphong=? ORDER BY sophong");
$roomsStmt->bind_param("i", $id);
$roomsStmt->execute();
$physicalRooms = $roomsStmt->get_result();

$discount = 0;
$finalPrice = (float)$room['gia'];

/*
 * Chuẩn hóa đường dẫn ảnh chính.
 * Hỗ trợ cả dữ liệu cũ kiểu "A1.jpg" và dữ liệu kiểu
 * "images/A1.jpg", "/images/A1.jpg", "images/rooms/A1.jpg".
 */
$img = trim((string)($room['anhphong'] ?? ''));
$mainImgPath = 'images/A1.jpg';
$fallbackImages = ['A1.jpg', 'A2.jpg', 'A3.jpg', 'A4.jpg', 'A5.jpg', 'A6.jpg'];
$fallback = $fallbackImages[((int)$room['id'] - 1) % count($fallbackImages)];

if ($img !== '') {
    $cleanImg = preg_replace('/[?#].*$/', '', str_replace('\\', '/', $img));
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
            $mainImgPath = $candidate;
            break;
        }

        if (is_file(__DIR__ . '/../images/' . $candidate)) {
            $mainImgPath = 'images/' . $candidate;
            break;
        }

        if (is_file(__DIR__ . '/../images/rooms/' . $candidate)) {
            $mainImgPath = 'images/rooms/' . $candidate;
            break;
        }
    }
}

if (!is_file(__DIR__ . '/../' . ltrim($mainImgPath, '/'))) {
    $mainImgPath = 'images/' . $fallback;
}
?>
<div class="grand-container" style="margin-top: 30px; margin-bottom: 50px;">
    
    <!-- THANH ĐIỀU HƯỚNG / BREADCRUMB -->
    <div style="margin-bottom: 20px; font-size: 14px; color: #64748b;">
        <a href="index.php?page=home" style="color: #64748b; text-decoration: none;">Trang chủ</a> &nbsp; / &nbsp; 
        <a href="index.php?page=rooms" style="color: #64748b; text-decoration: none;">Danh sách phòng</a> &nbsp; / &nbsp; 
        <strong style="color: #0f172a;"><?= htmlspecialchars($room['tenphong']) ?></strong>
    </div>

    <!-- KHUNG CHI TIẾT PHÒNG -->
    <div class="grand-room-card" style="flex-direction: column; padding: 30px; border-radius: 16px;">
        
        <div style="display: flex; gap: 35px; flex-wrap: wrap;">
            
            <!-- CỘT BÊN TRÁI: KHUNG HÌNH ẢNH & GALLERY -->
            <div style="flex: 1.2; min-width: 320px;">
                <!-- Ảnh chính -->
                <div style="width: 100%; height: 380px; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.1); margin-bottom: 15px;">
                    <img id="detailMainImg" src="<?= htmlspecialchars($mainImgPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($room['tenphong']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: all 0.3s ease;" onerror="this.onerror=null; this.src='images/<?= htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8') ?>';">
                </div>
                
                <!-- Album ảnh nhỏ bổ sung (sử dụng danh sách ảnh A1-A6) -->
                <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px;">
                    <?php 
                    $galleryImages = ['A1.jpg', 'A2.jpg', 'A3.jpg', 'A4.jpg', 'A5.jpg', 'A6.jpg'];
                    foreach ($galleryImages as $gImg): 
                    ?>
                        <img src="images/<?= $gImg ?>" 
                             onclick="document.getElementById('detailMainImg').src='images/<?= $gImg ?>'" 
                             style="width: 75px; height: 55px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e2e8f0; transition: transform 0.2s;"
                             onmouseover="this.style.transform='scale(1.05)'" 
                             onmouseout="this.style.transform='scale(1)'">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CỘT BÊN PHẢI: THÔNG TIN CHI TIẾT -->
            <div style="flex: 1.5; min-width: 320px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="room-stars">★★★★★</div>
                        
                    </div>

                    <h1 style="font-size: 30px; color: #0f172a; margin: 10px 0; font-weight: 700;"><?= htmlspecialchars($room['tenphong']) ?></h1>
                    
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: inline-block;">
                        <span style="font-size: 13px; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Giá phòng niêm yết</span>
                        <strong style="font-size: 28px; color: #c5a059;"><?= number_format($finalPrice, 0, ',', '.') ?></strong>
                        <span style="font-size: 14px; color: #475569;">VND / đêm</span>
                    </div>

                    <div class="amenities-chips" style="margin-bottom: 20px;">
                        <span class="chip">👥 <strong>Sức chứa:</strong> <?= htmlspecialchars($room['sokhach']) ?> Khách lớn</span>
                        <span class="chip">📐 <strong>Diện tích:</strong> <?= htmlspecialchars($room['dientich']) ?> m²</span>
                        <span class="chip">📶 Wifi miễn phí</span>
                        <span class="chip">🍳 Ăn sáng Buffet</span>
                        <span class="chip">☕ Trà & Cà phê miễn phí</span>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h3 style="font-size: 16px; color: #0f172a; margin-bottom: 8px;">Mô tả chi tiết:</h3>
                        <p style="font-size: 15px; color: #475569; line-height: 1.8;">
                            <?= !empty($room['mota']) ? nl2br(htmlspecialchars($room['mota'])) : 'Phòng được thiết kế sang trọng với không gian hiện đại, đón ánh sáng tự nhiên. Trang bị đầy đủ tiện nghi cao cấp giúp quý khách tận hưởng trọn vẹn sự thoải mái và riêng tư trong suốt thời gian lưu trú.' ?>
                        </p>
                    </div>

                    <!-- Danh sách số phòng khả dụng -->
                    <div style="border-top: 1px dashed #cbd5e1; padding-top: 15px; margin-bottom: 25px;">
                        <h3 style="font-size: 15px; color: #0f172a; margin-bottom: 10px;">Vị trí phòng thực tế thuộc loại này:</h3>
                        <?php if($physicalRooms->num_rows): ?>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php while($p = $physicalRooms->fetch_assoc()): ?>
                                <span class="chip" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">
                                    Phòng <?= htmlspecialchars($p['sophong']) ?> 
                                    <small style="color: <?= in_array(mb_strtolower(trim($p['trangthai']), 'UTF-8'), ['trống', 'trong'], true) ? '#16a34a' : '#d97706' ?>;">(<?= htmlspecialchars($p['trangthai']) ?>)</small>
                                </span>
                            <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #94a3b8; font-size: 13px;">Chưa cập nhật danh sách phòng cụ thể.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nút Đặt Phòng -->
                <div>
                    <?php if(isset($_SESSION['user'])): ?>
                        <a class="btn-solid-gold" style="display: block; text-align: center; padding: 15px 30px; font-size: 16px; width: 100%; border-radius: 10px;" href="index.php?page=booking&id=<?= (int)$room['id'] ?>">ĐẶT PHÒNG NGAY NÀY</a>
                    <?php else: ?>
                        <a class="btn-solid-gold" style="display: block; text-align: center; padding: 15px 30px; font-size: 16px; width: 100%; border-radius: 10px;" href="index.php?page=login">ĐĂNG NHẬP ĐỂ ĐẶT PHÒNG</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

