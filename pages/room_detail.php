<?php
require_once __DIR__ . '/../connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Lấy thông tin loại phòng
$stmt = $conn->prepare("SELECT * FROM loaiphong WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    echo '<div class="grand-container" style="padding: 60px 20px; text-align:center;">
            <h2>Rất tiếc! Không tìm thấy thông tin loại phòng này.</h2><br>
            <a href="index.php?page=rooms" class="btn-solid-gold">QUAY LẠI DANH SÁCH PHÒNG</a>
          </div>';
    exit;
}

$finalPrice = (float)$room['gia'];

// 2. Lấy danh sách số phòng thực tế thuộc loại này
$roomsStmt = $conn->prepare("SELECT * FROM phong WHERE id_loaiphong = ? ORDER BY sophong");
$roomsStmt->bind_param("i", $id);
$roomsStmt->execute();
$physicalRooms = $roomsStmt->get_result();

// 3. Chuẩn hóa đường dẫn ảnh chính
$img = trim((string)($room['anhphong'] ?? ''));
$mainImgPath = 'images/A1.jpg';
if ($img !== '' && is_file(__DIR__ . '/../images/' . basename($img))) {
    $mainImgPath = 'images/' . basename($img);
}

// 4. Lấy thông tin khuyến mãi nếu có
$sqlDiscount = "SELECT * FROM khuyenmai WHERE id_loaiphong = $id LIMIT 1";
$resultDiscount = mysqli_query($conn, $sqlDiscount);

// 5. Lấy username từ Session để kiểm tra lịch sử đặt phòng
$username = $_SESSION['user'] ?? '';
$bookedRooms = [];
$hasBooked = false;

if (!empty($username)) {
    $sqlBooked = "SELECT DISTINCT phong.id AS id_phong, phong.sophong 
                  FROM datphong 
                  JOIN taikhoan ON datphong.id_taikhoan = taikhoan.id
                  JOIN phong ON datphong.id_phong = phong.id
                  WHERE phong.id_loaiphong = ? AND taikhoan.hoten = ?";

    $stmtBooked = $conn->prepare($sqlBooked);
    $stmtBooked->bind_param("is", $id, $username);
    $stmtBooked->execute();
    $bookedRooms = $stmtBooked->get_result();

    if ($bookedRooms && $bookedRooms->num_rows > 0) {
        $hasBooked = true;
    }
}

// 6. Lấy danh sách đánh giá/bình luận của loại phòng này
$commentsStmt = $conn->prepare("SELECT b.*, t.hoten 
                                FROM binhluan b 
                                JOIN taikhoan t ON b.id_taikhoan = t.id 
                                JOIN phong p ON b.id_phong = p.id 
                                WHERE p.id_loaiphong = ? 
                                ORDER BY b.id DESC");
$commentsStmt->bind_param("i", $id);
$commentsStmt->execute();
$comments = $commentsStmt->get_result();
?>

<style>
    .stars-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        margin-bottom: 10px;
    }
    .stars-input input { display: none; }
    .stars-input label {
        cursor: pointer;
        font-size: 25px;
        color: #ddd;
        transition: 0.2s;
    }
    .stars-input label:before { content: '★'; }
    .stars-input input:checked ~ label,
    .stars-input label:hover,
    .stars-input label:hover ~ label {
        color: #ffca08;
    }
</style>

<div class="grand-container" style="margin-top: 30px; margin-bottom: 50px;">

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

                <!-- Album ảnh nhỏ -->
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

            <!-- CỘT PHẢI: THÔNG TIN CHI TIẾT & ĐẶT PHÒNG -->
            <div style="flex: 1.5; min-width: 320px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="color: #f59e0b; font-size: 16px;">★★★★★</span>
                        <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">Hạng phòng Cao cấp</span>
                    </div>

                    <h1 style="font-size: 32px; color: #0f172a; margin: 10px 0; font-weight: 800;"><?= htmlspecialchars($room['tenphong']) ?></h1>
                    
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: inline-block;">
                        <span style="font-size: 12px; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Giá niêm yết ưu đãi</span>
                        <strong style="font-size: 30px; color: #c5a059;"><?= number_format($finalPrice, 0, ',', '.') ?></strong>
                        <span style="font-size: 14px; color: #475569;">VND / đêm</span>
                    </div>

                    <?php if ($resultDiscount && mysqli_num_rows($resultDiscount) > 0):
                        $discountData = mysqli_fetch_assoc($resultDiscount); ?>
                        <div style="background: #fdd3d3; border: 2px solid #ff0000; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; width: fit-content;">
                            <span style="font-weight: bold; color: #dc3545; font-size: 15px;">Giảm giá <?= $discountData['phantramgiamgia'] ?>%</span>
                            <span style="font-style: italic; font-size: 13px; color: #333;"> (Đặt từ <?= $discountData['dem'] ?> đêm trở lên)</span>
                        </div>
                    <?php endif; ?>

                    <div class="amenities-chips" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <span class="chip" style="background:#f1f5f9; padding: 6px 12px; border-radius: 6px; font-size: 13px;">👥 <strong>Sức chứa:</strong> <?= htmlspecialchars($room['sokhach']) ?> Khách lớn</span>
                        <span class="chip" style="background:#f1f5f9; padding: 6px 12px; border-radius: 6px; font-size: 13px;">📐 <strong>Diện tích:</strong> <?= htmlspecialchars($room['dientich']) ?> m²</span>
                        <span class="chip" style="background:#f1f5f9; padding: 6px 12px; border-radius: 6px; font-size: 13px;">📶 Wifi miễn phí</span>
                        <span class="chip" style="background:#f1f5f9; padding: 6px 12px; border-radius: 6px; font-size: 13px;">🍳 Ăn sáng Buffet</span>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <h3 style="font-size: 16px; color: #0f172a; margin-bottom: 8px;">Mô tả không gian:</h3>
                        <p style="font-size: 14px; color: #475569; line-height: 1.8;">
                            <?= !empty($room['mota']) ? nl2br(htmlspecialchars($room['mota'])) : 'Phòng được thiết kế sang trọng theo phong cách hiện đại với ánh sáng tự nhiên. Trang bị đầy đủ nội thất cao cấp mang lại sự thoải mái tối đa cho quý khách.' ?>
                        </p>
                    </div>

                    <!-- Danh sách số phòng khả dụng -->
                    <div style="border-top: 1px dashed #cbd5e1; padding-top: 15px; margin-bottom: 25px;">
                        <h3 style="font-size: 15px; color: #0f172a; margin-bottom: 10px;">Vị trí phòng thực tế thuộc loại này:</h3>
                        <?php if ($physicalRooms && $physicalRooms->num_rows > 0): ?>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php while ($p = $physicalRooms->fetch_assoc()): ?>
                                    <span style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 13px;">
                                        Phòng <?= htmlspecialchars($p['sophong']) ?>
                                        <small style="color: <?= in_array(mb_strtolower(trim($p['trangthai']), 'UTF-8'), ['trống', 'trong'], true) ? '#16a34a' : '#d97706' ?>; font-weight: bold;">
                                            (<?= htmlspecialchars($p['trangthai']) ?>)
                                        </small>
                                    </span>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #94a3b8; font-size: 13px;">Chưa cập nhật danh sách phòng cụ thể.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- NÚT ĐẶT PHÒNG KHÁCH HÀNG -->
                <div>
                    <?php if(isset($_SESSION['user'])): ?>
                        <a class="btn-solid-gold" style="display: block; text-align: center; padding: 16px 30px; font-size: 16px; width: 100%; border-radius: 12px; font-weight: bold; background: #b38b4d; color: #fff; text-decoration: none;" href="index.php?page=booking&id=<?= (int)$room['id'] ?>">Đặt Phòng Ngay Này</a>
                    <?php else: ?>
                        <a class="btn-solid-gold" style="display: block; text-align: center; padding: 16px 30px; font-size: 16px; width: 100%; border-radius: 12px; font-weight: bold; background: #b38b4d; color: #fff; text-decoration: none;" href="index.php?page=login">Đăng Nhập Để Đặt Phòng</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- BỔ SUNG KHU VỰC TIỆN ÍCH ĐẶC QUYỀN VÀ CHÍNH SÁCH -->
        <div style="margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
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
                    <li>✓ Áo tắm & Khăn tắm cao cấp</li>
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

        <!-- BÌNH LUẬN & ĐÁNH GIÁ -->
        <div style="margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 30px; display: flex; gap: 30px; flex-wrap: wrap;">
            <!-- CỘT GỬI ĐÁNH GIÁ -->
            <div style="flex: 1; min-width: 300px;">
                <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 15px;">Đánh giá của bạn</h3>
                <?php if (isset($_SESSION['user'])): ?>
                    <?php if ($hasBooked): ?>
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px;">
                            <p style="font-size: 13px; color: #166534; margin: 0 0 8px 0; font-weight: 600;">
                                ✓ Bạn đã từng đặt phòng thuộc loại này. Các phòng bạn đã ở:
                            </p>
                            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #15803d;">
                                <?php
                                $bookedRooms->data_seek(0);
                                while ($bInfo = $bookedRooms->fetch_assoc()):
                                ?>
                                    <li>Phòng số: <strong><?= htmlspecialchars($bInfo['sophong']) ?></strong> (ID phòng: <?= $bInfo['id_phong'] ?>)</li>
                                <?php endwhile; ?>
                            </ul>
                        </div>

                        <form action="process_comment.php" method="POST">
                            <input type="hidden" name="id_loaiphong" value="<?= $room['id'] ?>">

                            <div style="margin-bottom: 10px;">
                                <label style="font-size: 13px; font-weight: bold; color: #333; display: block; margin-bottom: 5px;">Chọn phòng cụ thể bạn muốn đánh giá:</label>
                                <select name="id_phong" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;" required>
                                    <option value="">-- Chọn phòng --</option>
                                    <?php
                                    $bookedRooms->data_seek(0);
                                    while ($bRoom = $bookedRooms->fetch_assoc()):
                                    ?>
                                        <option value="<?= $bRoom['id_phong'] ?>">Phòng <?= htmlspecialchars($bRoom['sophong']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="stars-input">
                                <input type="radio" name="sao" value="5" id="s5"><label for="s5"></label>
                                <input type="radio" name="sao" value="4" id="s4"><label for="s4"></label>
                                <input type="radio" name="sao" value="3" id="s3"><label for="s3"></label>
                                <input type="radio" name="sao" value="2" id="s2"><label for="s2"></label>
                                <input type="radio" name="sao" value="1" id="s1" required><label for="s1"></label>
                            </div>

                            <textarea name="noidung" placeholder="Cảm nhận của bạn về chuyến đi này..." style="width:100%; height:80px; padding:10px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box; font-family: inherit;" required></textarea>
                            
                            <button type="submit" style="width:100%; margin-top: 10px; padding: 10px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Gửi nhận xét</button>
                        </form>
                    <?php else: ?>
                        <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 15px; border-radius: 8px; text-align: center;">
                            <p style="font-size: 13px; color: #b45309; margin: 0;">
                                Bạn chưa từng đặt phòng nào thuộc loại phòng này nên chưa thể viết đánh giá.
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="font-size: 13px; color: #e74c3c; text-align: center;">Bạn cần <a href="index.php?page=login">Đăng nhập</a> để đánh giá phòng này.</p>
                <?php endif; ?>
            </div>

            <!-- CỘT DANH SÁCH BÌNH LUẬN -->
            <div style="flex: 1.5; min-width: 300px;">
                <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 15px;">💬 Đánh Giá Từ Khách Hàng</h3>
                <?php if ($comments && $comments->num_rows > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php while ($c = $comments->fetch_assoc()): ?>
                            <div style="background: #f8fafc; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                    <strong><?= htmlspecialchars($c['hoten']) ?></strong>
                                    <span style="color: #f59e0b;"><?= str_repeat('★', (int)$c['sao']) ?></span>
                                </div>
                                <p style="font-size: 14px; color: #475569; margin: 5px 0;"><?= htmlspecialchars($c['noidung']) ?></p>
                                <small style="color: #94a3b8; font-size: 12px;"><?= date("d/m/Y H:i", strtotime($c['ngaybinhluan'])) ?></small>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #64748b; font-size: 14px; font-style: italic;">Chưa có bình luận nào cho loại phòng này. Hãy trải nghiệm và là người đầu tiên đánh giá!</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>