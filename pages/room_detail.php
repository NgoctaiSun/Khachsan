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
// Giảm giá nếu có khuyến mãi
$sqlDiscount = "SELECT * FROM khuyenmai WHERE id_loaiphong=$id";
$resultDiscount = mysqli_query($conn, $sqlDiscount);
// Lấy đánh giá của khách hàng cho loại phòng này
$sql_dg = "SELECT dg.*, u.hoten FROM binhluan dg 
JOIN taikhoan u ON dg.id_taikhoan = u.id 
JOIN phong p ON p.id = dg.id_phong 
JOIN loaiphong lp ON lp.id = p.id_loaiphong
WHERE lp.id=$id ORDER BY dg.ngaybinhluan DESC";
$result_dg = mysqli_query($conn, $sql_dg);
// Truy vấn kiểm tra và lấy danh sách các phòng cụ thể mà user này ĐÃ TỪNG ĐẶT thuộc loại phòng này ($id)
$bookedRooms = [];
$hasBooked = false;

if (!empty($username)) {
    $sqlBooked = "SELECT DISTINCT phong.id AS id_phong, phong.sophong 
                  FROM datphong 
                  JOIN taikhoan ON datphong.id_taikhoan = taikhoan.id
                  JOIN phong ON datphong.id_phong = phong.id
                  WHERE phong.id_loaiphong = ? AND taikhoan.username = ?";

    $stmtBooked = $conn->prepare($sqlBooked);
    $stmtBooked->bind_param("is", $id, $username);
    $stmtBooked->execute();
    $bookedRooms = $stmtBooked->get_result();

    if ($bookedRooms->num_rows > 0) {
        $hasBooked = true;
    }
}
?>
<style>
    .stars-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        margin-bottom: 10px;
    }

    .stars-input input {
        display: none;
    }

    .stars-input label {
        cursor: pointer;
        font-size: 25px;
        color: #ddd;
        transition: 0.2s;
    }

    .stars-input label:before {
        content: '★';
    }

    .stars-input input:checked~label,
    .stars-input label:hover,
    .stars-input label:hover~label {
        color: #ffca08;
    }
</style>
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


                    <?php if ($resultDiscount && mysqli_num_rows($resultDiscount) > 0):
                        $discountData = mysqli_fetch_assoc($resultDiscount); ?>
                        <div style="background: #fdd3d3; border: 2px solid #ff0000; padding: 15px; border-radius: 8px; margin-bottom: 20px; width: fit-content;">
                            <span style="font-weight: bold; color: #dc3545;">Giảm giá <?= $discountData['phantramgiamgia'] ?>%</span>
                            <span style="font-style: italic; font-size: 13px;">Đặt từ <?= $discountData['dem'] ?> đêm trở lên</span>
                        </div>
                    <?php endif; ?>

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
                        <?php if ($physicalRooms->num_rows): ?>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php while ($p = $physicalRooms->fetch_assoc()): ?>
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
                    <?php if (isset($_SESSION['user'])): ?>
                        <a class="btn-solid-gold" style="display: block; text-align: center; padding: 15px 30px; font-size: 16px; width: 100%; border-radius: 10px;" href="index.php?page=booking&id=<?= (int)$room['id'] ?>">ĐẶT PHÒNG NGAY NÀY</a>
                    <?php else: ?>
                        <a class="btn-solid-gold" style="display: block; text-align: center; padding: 15px 30px; font-size: 16px; width: 100%; border-radius: 10px;" href="index.php?page=login">ĐĂNG NHẬP ĐỂ ĐẶT PHÒNG</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <!-- Bình luận -->
        <div style="display:flex; gap:10px">
            <div style="flex: 1.2; min-width: 320px;">
                <h4>Đánh giá của bạn</h4>
                <?php if (isset($_SESSION['user'])): ?>
                    <?php if ($hasBooked): ?>
                        !-- Đã đặt phòng -> Hiển thị thông báo và form đánh giá -->
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px;">
                            <p style="font-size: 13px; color: #166534; margin: 0 0 8px 0; font-weight: 600;">
                                ✓ Bạn đã từng đặt phòng thuộc loại này. Các phòng bạn đã ở:
                            </p>
                            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #15803d;">
                                <?php
                                // Duyệt lại con trỏ kết quả để liệt kê ID và Số phòng cho khách thấy
                                $bookedRooms->data_seek(0);
                                while ($bInfo = $bookedRooms->fetch_assoc()):
                                ?>
                                    <li>Phòng số: <strong><?= htmlspecialchars($bInfo['sophong']) ?></strong> (ID phòng: <?= $bInfo['id_phong'] ?>)</li>
                                <?php endwhile; ?>
                            </ul>
                        </div>

                        <form action="process_comment.php" method="POST">
                            <input type="hidden" name="id_loaiphong" value="<?= $room['id'] ?>">

                            <!-- Cho phép chọn phòng cụ thể để đánh giá -->
                            <div style="margin-bottom: 10px;">
                                <label style="font-size: 13px; font-weight: bold; color: #333; display: block; margin-bottom: 5px;">Chọn phòng cụ thể bạn muốn đánh giá:</label>
                                <select name="id_phong" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;" required>
                                    <option value="">-- Chọn phòng --</option>
                                    <?php
                                    $bookedRooms->data_seek(0);
                                    while ($bRoom = $bookedRooms->fetch_assoc()):
                                    ?>
                                        <option value="<?= $bRoom['id_phong'] ?>">Phòng <?= htmlspecialchars($bRoom['sophong']) ?> (ID: <?= $bRoom['id_phong'] ?>)</option>
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
                        <!-- Chưa đặt phòng nào -->
                        <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 15px; border-radius: 8px; text-align: center;">
                            <p style="font-size: 13px; color: #b45309; margin: 0;">
                                Bạn chưa từng đặt phòng nào thuộc loại phòng này nên không thể viết đánh giá.
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="font-size: 13px; color: #e74c3c; text-align: center;">Bạn cần <a href="index.php?page=login">Đăng nhập</a> để đánh giá phòng này.</p>
                <?php endif; ?>
            </div>
            <div style="flex: 1.5; min-width: 320px; display: flex; flex-direction: column; justify-content: space-between;">
                <h4>Nhận xét khách hàng</h4>
                <div class="review-list">
                    <?php if (mysqli_num_rows($result_dg) > 0): ?>
                        <?php while ($row_dg = mysqli_fetch_assoc($result_dg)): ?>
                            <div class="dg-item">
                                <span class="dg-user"><?= htmlspecialchars($row_dg['hoten']) ?></span>
                                <span class="dg-star"><?= str_repeat("★", $row_dg['sao']) ?></span>
                                <div class="dg-text">"<?= htmlspecialchars($row_dg['noidung']) ?>"</div>
                                <span class="dg-date"><?= date("d/m/Y H:i", strtotime($row_dg['ngay_dg'])) ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color:#999; font-size: 13px; text-align: center;">Chưa có nhận xét nào cho tour này.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>