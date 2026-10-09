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

// Lấy danh sách số phòng thực tế
$roomsStmt = $conn->prepare("SELECT * FROM phong WHERE id_loaiphong=? ORDER BY sophong");
$roomsStmt->bind_param("i", $id);
$roomsStmt->execute();
$physicalRooms = $roomsStmt->get_result();

// Lấy danh sách đánh giá/bình luận của loại phòng này
$commentsStmt = $conn->prepare("SELECT b.*, t.hoten FROM binhluan b JOIN taikhoan t ON b.id_taikhoan = t.id JOIN phong p ON b.id_phong = p.id WHERE p.id_loaiphong = ? ORDER BY b.id DESC LIMIT 5");
$commentsStmt->bind_param("i", $id);
$commentsStmt->execute();
$comments = $commentsStmt->get_result();

$finalPrice = (float)$room['gia'];

// Chuẩn hóa đường dẫn ảnh
$img = trim((string)($room['anhphong'] ?? ''));
$mainImgPath = 'images/A1.jpg';
if ($img !== '' && is_file(__DIR__ . '/../images/' . basename($img))) {
    $mainImgPath = 'images/' . basename($img);
}
?>

<div class="grand-container" style="margin-top: 30px; margin-bottom: 60px;">
    
    <!-- BREADCRUMB -->
    <div style="margin-bottom: 20px; font-size: 14px; color: #64748b;">
        <a href="index.php?page=home" style="color: #64748b; text-decoration: none;">Trang chủ</a> &nbsp;/&nbsp; 
        <a href="index.php?page=rooms" style="color: #64748b; text-decoration: none;">Danh sách phòng</a> &nbsp;/&nbsp; 
        <strong style="color: #0f172a;"><?= htmlspecialchars($room['tenphong']) ?></strong>
    </div>

    <!-- KHUNG CHI TIẾT TỔNG THỂ -->
    <div style="background: #ffffff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
        
        <div style="display: flex; gap: 40px; flex-wrap: wrap;">
            
            <!-- CỘT TRÁI: BỘ BỘ ẢNH GALLERY -->
            <div style="flex: 1.2; min-width: 320px;">
                <div style="width: 100%; height: 380px; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.1); margin-bottom: 15px;">
                    <img id="detailMainImg" src="<?= htmlspecialchars($mainImgPath) ?>" alt="<?= htmlspecialchars($room['tenphong']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                
                <!-- Album ảnh nhỏ xem thử -->
                <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px;">
                    <?php 
                    $galleryImages = ['A1.jpg', 'A2.jpg', 'A3.jpg', 'A4.jpg', 'A5.jpg', 'A6.jpg'];
                    foreach ($galleryImages as $gImg): 
                    ?>
                        <img src="images/<?= $gImg ?>" 
                             onclick="document.getElementById('detailMainImg').src='images/<?= $gImg ?>'" 
                             style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e2e8f0; transition: transform 0.2s;"
                             onmouseover="this.style.transform='scale(1.05)'" 
                             onmouseout="this.style.transform='scale(1)'">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CỘT PHẢI: THÔNG TIN CHI TIẾT & ĐẶT PHÒNG -->
            <div style="flex: 1.5; min-width: 320px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #f59e0b; font-size: 16px;">★★★★★</span>
                        <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">Hạng phòng Cao cấp</span>
                    </div>

                    <h1 style="font-size: 32px; color: #0f172a; margin: 10px 0; font-weight: 800;"><?= htmlspecialchars($room['tenphong']) ?></h1>
                    
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: inline-block;">
                        <span style="font-size: 12px; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Giá niêm yết ưu đãi</span>
                        <strong style="font-size: 30px; color: #c5a059;"><?= number_format($finalPrice, 0, ',', '.') ?></strong>
                        <span style="font-size: 14px; color: #475569;">VND / đêm</span>
                    </div>

                    <!-- THÔNG SỐ NỔI BẬT NÂNG CẤP -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 25px;">
                        <div style="background: #f1f5f9; padding: 10px 15px; border-radius: 10px; font-size: 13px;">👥 <strong>Sức chứa:</strong> <?= htmlspecialchars($room['sokhach']) ?> Khách người lớn</div>
                        <div style="background: #f1f5f9; padding: 10px 15px; border-radius: 10px; font-size: 13px;">📐 <strong>Diện tích:</strong> <?= htmlspecialchars($room['dientich']) ?> m²</div>
                        <div style="background: #f1f5f9; padding: 10px 15px; border-radius: 10px; font-size: 13px;">🛏️ <strong>Loại giường:</strong> 1 Giường King / 2 Giường Đơn</div>
                        <div style="background: #f1f5f9; padding: 10px 15px; border-radius: 10px; font-size: 13px;">🌄 <strong>Tầm nhìn:</strong> Hướng Sông / Hướng Vườn Vô Cực</div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h3 style="font-size: 16px; color: #0f172a; margin-bottom: 8px;">Mô tả không gian:</h3>
                        <p style="font-size: 14px; color: #475569; line-height: 1.8;">
                            <?= !empty($room['mota']) ? nl2br(htmlspecialchars($room['mota'])) : 'Phòng được thiết kế sang trọng theo phong cách hiện đại với ánh sáng tự nhiên. Trang bị đầy đủ nội thất cao cấp mang lại sự thoải mái tối đa cho quý khách.' ?>
                        </p>
                    </div>
                </div>

                <!-- NÚT ĐẶT PHÒNG KHÁCH HÀNG -->
                <div>
                    <?php if(isset($_SESSION['user'])): ?>
                        <a class="btn-solid-gold" style="display: block; text-align: center; padding: 16px 30px; font-size: 16px; width: 100%; border-radius: 12px; font-weight: bold;" href="index.php?page=booking&id=<?= (int)$room['id'] ?>">Đặt Phòng Ngay Này</a>
                    <?php else: ?>
                        <a class="btn-solid-gold" style="display: block; text-align: center; padding: 16px 30px; font-size: 16px; width: 100%; border-radius: 12px; font-weight: bold;" href="index.php?page=login">Đăng Nhập Để Đặt Phòng</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- BỔ SUNG KHU VỰC TIỆN ÍCH ĐẶC QUYỀN VÀ CHÍNH SÁCH -->
        <div style="margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 30px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <div>
                <h3 style="font-size: 16px; color: #0f172a; margin-bottom: 15px;">☕ Tiện Nghi Trong Phòng</h3>
                <ul style="list-style: none; padding: 0; font-size: 14px; color: #475569; line-height: 2;">
                    <li>✓ Wifi tốc độ cao miễn phí</li>
                    <li>✓ TV Smart 55 inch truyền hình vệ tinh</li>
                    <li>✓ Điều hòa trung tâm 2 chiều</li>
                    <li>✓ Két an toàn & Tủ lạnh Mini Bar</li>
                    <li>✓ Máy pha cà phê & Ấm siêu tốc</li>
                </ul>
            </div>
            <div>
                <h3 style="font-size: 16px; color: #0f172a; margin-bottom: 15px;">🚿 Phòng Tắm & Thư Giãn</h3>
                <ul style="list-style: none; padding: 0; font-size: 14px; color: #475569; line-height: 2;">
                    <li>✓ Bồn tắm nằm sang trọng / Vòi sen đứng</li>
                    <li>✓ Bộ đồ dùng vệ sinh cá nhân cao cấp</li>
                    <li>✓ Áo áo tắm & Khăn tắm cao cấp</li>
                    <li>✓ Máy sấy tóc chuyên nghiệp</li>
                    <li>✓ Gương trang điểm có đèn LED</li>
                </ul>
            </div>
            <div>
                <h3 style="font-size: 16px; color: #0f172a; margin-bottom: 15px;">📜 Chính Sách Lưu Trú</h3>
                <ul style="list-style: none; padding: 0; font-size: 14px; color: #475569; line-height: 2;">
                    <li>🕒 <strong>Nhận phòng:</strong> Từ 14:00</li>
                    <li>🕚 <strong>Trả phòng:</strong> Trước 12:00</li>
                    <li>🍳 <strong>Ăn sáng:</strong> Đã bao gồm Buffet sang trọng</li>
                    <li>❌ <strong>Hủy phòng:</strong> Miễn phí trước 48h</li>
                    <li>🚭 <strong>Hút thuốc:</strong> Phòng không hút thuốc</li>
                </ul>
            </div>
        </div>

        <!-- BỔ SUNG ĐÁNH GIÁ TỪ KHÁCH HÀNG -->
        <div style="margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 30px;">
            <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 20px;">💬 Đánh Giá Từ Khách Trải Nghiệm</h3>
            <?php if ($comments && $comments->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php while ($c = $comments->fetch_assoc()): ?>
                        <div style="background: #f8fafc; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <strong><?= htmlspecialchars($c['hoten']) ?></strong>
                                <span style="color: #f59e0b;"><?= str_repeat('⭐', (int)$c['sao']) ?></span>
                            </div>
                            <p style="font-size: 14px; color: #475569; margin: 0;"><?= htmlspecialchars($c['noidung']) ?></p>
                            <small style="color: #94a3b8; font-size: 12px;"><?= $c['ngaybinhluan'] ?></small>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: #64748b; font-size: 14px; font-style: italic">Chưa có bình luận nào cho loại phòng này. Hãy trải nghiệm và là người đầu tiên đánh giá!</p>
            <?php endif; ?>
        </div>

    </div>
</div>
