<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connect.php';

if (!isset($_SESSION['id'])) {
    header('Location: index.php?page=login');
    exit;
}
$userId = (int)$_SESSION['id'];
$typeId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM loaiphong WHERE id=? LIMIT 1");
$stmt->bind_param("i", $typeId);
$stmt->execute();
$type = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$type) {
    echo '<div class="hh-alert hh-error">Không tìm thấy loại phòng.</div>';
    return;
}

$message = '';
$old = [
    'id_phong' => '',
    'ngaynhan' => '',
    'ngaytra' => '',
    'sokhach' => 1,
    'ghichu' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $value) {
        $old[$key] = $_POST[$key] ?? $value;
    }

    $roomId = (int)$old['id_phong'];
    $checkIn = $old['ngaynhan'];
    $checkOut = $old['ngaytra'];
    $guests = (int)$old['sokhach'];
    $note = trim($old['ghichu']);

    $inDate = DateTime::createFromFormat('Y-m-d', $checkIn);
    $outDate = DateTime::createFromFormat('Y-m-d', $checkOut);
    $today = new DateTime('today');

    if (!$inDate || !$outDate || $inDate < $today || $outDate <= $inDate) {
        $message = '<div class="hh-alert hh-error">Ngày nhận hoặc ngày trả không hợp lệ.</div>';
    } elseif ($guests < 1 || $guests > (int)$type['sokhach']) {
        $message = '<div class="hh-alert hh-error">Số khách vượt quá sức chứa của loại phòng.</div>';
    } else {
        $check = $conn->prepare("
            SELECT p.id, p.sophong, p.tang, lp.gia, lp.sokhach
            FROM phong p
            JOIN loaiphong lp ON lp.id=p.id_loaiphong
            WHERE p.id=? AND p.id_loaiphong=?
              AND LOWER(TRIM(p.trangthai)) IN ('trong','trống')
              AND NOT EXISTS (
                SELECT 1 FROM datphong d
                WHERE d.id_phong=p.id
                  AND d.trangthai NOT IN ('Huy','Đã hủy','Hoan tat')
                  AND d.ngaynhan < ?
                  AND d.ngaytra > ?
              )
            LIMIT 1
        ");
        $check->bind_param("iiss", $roomId, $typeId, $checkOut, $checkIn);
        $check->execute();
        $selected = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$selected) {
            $message = '<div class="hh-alert hh-error">Phòng không còn trống trong thời gian bạn chọn. Vui lòng chọn phòng hoặc ngày khác.</div>';
        } else {
            $nights = (int)$inDate->diff($outDate)->days;
            $unitPrice = (float)$selected['gia'];
            $discountRate = 0;

            // Khuyến mãi trong database dùng dạng tỷ lệ:
            // 0.99 tương đương giảm 99%.
            $promo = $conn->prepare("
                SELECT phantramgiamgia
                FROM khuyenmai
                WHERE id_loaiphong=? AND trangthai='dangapdung'
                  AND dem<=?
                ORDER BY phantramgiamgia DESC
                LIMIT 1
            ");
            if ($promo) {
                $promo->bind_param("ii", $typeId, $nights);
                $promo->execute();
                $promoRow = $promo->get_result()->fetch_assoc();
                if ($promoRow) {
                    $discountRate = max(0, min(100, (float)$promoRow['phantramgiamgia'])) / 100;
                }

                $promo->close();
            }

            $total = (int)round($nights * $unitPrice * (1 - $discountRate));
            $status = $_POST['trangthai_dat'] ?? 'Cho xac nhan';
            $createdAt = date('Y-m-d H:i:s');

            // Kiểm tra lần cuối trước khi ghi đơn để tránh đặt trùng.
            $insert = $conn->prepare("
                INSERT INTO datphong
                (id_taikhoan,id_phong,ngaynhan,ngaytra,sokhach,tongtien,trangthai,thoigiandat,ghichu)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");
            $insert->bind_param(
                "iissiisss",
                $userId,
                $roomId,
                $checkIn,
                $checkOut,
                $guests,
                $total,
                $status,
                $createdAt,
                $note
            );

            if ($insert->execute()) {
                $bookingId = $insert->insert_id;
                $message = '<div class="hh-alert hh-success">Đặt phòng thành công! Mã đặt phòng của bạn là <strong>#' . $bookingId . '</strong>. Vui lòng chờ khách sạn xác nhận.</div>';
                $old = ['id_phong' => '', 'ngaynhan' => '', 'ngaytra' => '', 'sokhach' => 1, 'ghichu' => ''];
            } else {
                $message = '<div class="hh-alert hh-error">Không thể tạo đơn đặt phòng. Vui lòng thử lại.</div>';
            }
            $insert->close();
        }
    }
}

$roomStmt = $conn->prepare("
    SELECT p.id,p.sophong,p.tang
    FROM phong p
    WHERE p.id_loaiphong=?
      AND LOWER(TRIM(p.trangthai)) IN ('trong','trống')
    ORDER BY p.sophong
");
$roomStmt->bind_param("i", $typeId);
$roomStmt->execute();
$roomList = $roomStmt->get_result();
$roomOptions = [];
while ($r = $roomList->fetch_assoc()) $roomOptions[] = $r;
$roomStmt->close();


?>

<div class="hh-page">
    <div class="hh-page-heading">
        <div>
            <span class="hh-eyebrow">HARMONY HOTEL</span>
            <h1>Đặt phòng của bạn</h1>
            <p>Vui lòng điền thông tin để chúng tôi chuẩn bị kỳ nghỉ tốt nhất.</p>
        </div>
    </div>

    <?= $message ?>

    <div class="hh-card hh-booking-card">
        <div class="hh-booking-intro">
            <div>
                <span class="hh-eyebrow">ROOM SELECTION</span>
                <h2><?= htmlspecialchars($type['tenphong']) ?></h2>
                <p><?= htmlspecialchars($type['mota']) ?></p>
            </div>
            <div class="hh-booking-price">
                <span>Giá mỗi đêm</span>
                <strong><?= number_format((float)$type['gia'], 0, ',', '.') ?>đ</strong>
            </div>
        </div>

        <?php if (!$roomOptions): ?>
            <div class="hh-alert hh-error">Hiện không có phòng trống thuộc loại phòng này. Vui lòng quay lại sau.</div>
        <?php else: ?>
            <form method="post" id="bookingForm" class="hh-form">
                <div class="hh-form-grid">
                    <div class="hh-field">
                        <label>Chọn số phòng</label>
                        <select name="id_phong" id="bookingRoom" required>
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($roomOptions as $r): ?>
                                <option value="<?= (int)$r['id'] ?>"
                                    <?= (string)$old['id_phong'] === (string)$r['id'] ? 'selected' : '' ?>>
                                    Phòng <?= htmlspecialchars($r['sophong']) ?> - Tầng <?= (int)$r['tang'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hh-field">
                        <label>Số khách</label>
                        <input type="number" name="sokhach" id="bookingGuests"
                            min="1" max="<?= (int)$type['sokhach'] ?>"
                            value="<?= (int)$old['sokhach'] ?>" required>
                        <small>Sức chứa tối đa: <?= (int)$type['sokhach'] ?> khách</small>
                    </div>
                    <div class="hh-field">
                        <label>Ngày nhận phòng</label>
                        <input type="date" name="ngaynhan" id="bookingCheckin"
                            min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['ngaynhan']) ?>" required>
                    </div>
                    <div class="hh-field">
                        <label>Ngày trả phòng</label>
                        <input type="date" name="ngaytra" id="bookingCheckout"
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                            value="<?= htmlspecialchars($old['ngaytra']) ?>" required>
                    </div>
                    <div>
                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'letan'): ?>
                            <div class="hh-field">
                                <label>Trạng thái đặt phòng</label>
                                <select name="trangthai_dat" id="bookingStatus" required>
                                    <option value="">-- Chọn trạng thái --</option>
                                    <option value="Da xac nhan">Đã xác nhận</option>
                                    <option value="Da nhan phong">Đã nhận phòng</option>

                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hh-field hh-field-full">
                        <label>Yêu cầu đặc biệt</label>
                        <textarea name="ghichu" id="bookingNote" rows="4"
                            placeholder="Ví dụ: phòng không hút thuốc, tầng cao..."><?= htmlspecialchars($old['ghichu']) ?></textarea>
                    </div>
                </div>

                <div class="hh-booking-actions">
                    <a href="index.php?page=rooms" class="hh-btn hh-btn-light">Quay lại</a>
                    <button type="button" class="hh-btn hh-btn-primary" id="reviewBooking">
                        Kiểm tra thông tin đặt phòng
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="hh-modal" id="bookingModal" aria-hidden="true">
    <div class="hh-modal-box">
        <button type="button" class="hh-modal-close" data-close-modal>&times;</button>
        <span class="hh-eyebrow">CONFIRM YOUR BOOKING</span>
        <h2>Xác nhận thông tin đặt phòng</h2>
        <p class="hh-modal-subtitle">Vui lòng kiểm tra lại trước khi gửi yêu cầu.</p>
        <div class="hh-confirm-list">
            <div><span>Loại phòng</span><strong><?= htmlspecialchars($type['tenphong']) ?></strong></div>
            <div><span>Số phòng</span><strong id="confirmRoom">—</strong></div>
            <div><span>Ngày nhận</span><strong id="confirmIn">—</strong></div>
            <div><span>Ngày trả</span><strong id="confirmOut">—</strong></div>
            <div><span>Số đêm</span><strong id="confirmNights">—</strong></div>
            <div><span>Số khách</span><strong id="confirmGuests">—</strong></div>
            <div>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'letan'): ?>
                    <span>Trạng thái đặt phòng</span><strong id="confirmStatus">—</strong>
                <?php endif; ?>
            </div>
            <div id="rowOriginal" style="display: none;"><span>Tổng tiền</span><strong id="confirmOriginal">—</strong></div>
            <div id="rowDiscount" style="display: none;"><span>Giảm giá</span><strong id="confirmDiscount" style="color: #16a34a;">—</strong></div>
            <div class="hh-confirm-total"><span id="labelTotal">Tổng tiền</span><strong id="confirmTotal">—</strong></div>
        </div>
        <div class="hh-modal-actions">
            <button type="button" class="hh-btn hh-btn-light" data-close-modal>Quay lại chỉnh sửa</button>
            <button type="button" class="hh-btn hh-btn-primary" id="submitBooking">Xác nhận đặt phòng</button>
        </div>
    </div>
</div>

<script>
    (function() {
        const form = document.getElementById('bookingForm');
        if (!form) return;

        const modal = document.getElementById('bookingModal');
        const room = document.getElementById('bookingRoom');
        const checkin = document.getElementById('bookingCheckin');
        const checkout = document.getElementById('bookingCheckout');
        const status = document.getElementById('bookingStatus');
        const guests = document.getElementById('bookingGuests');

        // Lấy thẳng dữ liệu khuyến mãi của loại phòng này từ DB
        const promos = <?= json_encode($conn->query("SELECT dem, phantramgiamgia FROM khuyenmai WHERE id_loaiphong = $typeId AND trangthai = 'dangapdung'")->fetch_all(MYSQLI_ASSOC)) ?>;
        const price = <?= json_encode((float)$type['gia']) ?>;

        document.getElementById('reviewBooking').addEventListener('click', function() {
            if (!form.reportValidity()) return;

            const start = new Date(checkin.value + 'T00:00:00');
            const end = new Date(checkout.value + 'T00:00:00');
            const nights = Math.round((end - start) / 86400000);

            if (nights <= 0) {
                alert('Ngày trả phòng phải sau ngày nhận phòng.');
                return;
            }

            // Tìm mức giảm giá cao nhất thỏa điều kiện số đêm
            let discountRate = 0;
            promos.forEach(p => {
                if (parseInt(p.dem) <= nights) {
                    const rate = Math.max(0, Math.min(100, parseFloat(p.phantramgiamgia))) / 100;
                    if (rate > discountRate) discountRate = rate;
                }
            });

            const option = room.options[room.selectedIndex];
            document.getElementById('confirmRoom').textContent = option.text;
            document.getElementById('confirmIn').textContent = checkin.value;
            document.getElementById('confirmOut').textContent = checkout.value;
            document.getElementById('confirmNights').textContent = nights + ' đêm';
            document.getElementById('confirmGuests').textContent = guests.value + ' khách';

            const originalTotal = nights * price;
            const discountAmount = Math.round(originalTotal * discountRate);
            const finalTotal = originalTotal - discountAmount;

            const rowOriginal = document.getElementById('rowOriginal');
            const rowDiscount = document.getElementById('rowDiscount');
            const labelTotal = document.getElementById('labelTotal');

            if (discountAmount > 0) {
                // Có giảm giá: hiện đủ Tổng, Giảm giá và Thành tiền
                rowOriginal.style.display = 'flex';
                rowDiscount.style.display = 'flex';
                document.getElementById('confirmOriginal').textContent = originalTotal.toLocaleString('vi-VN') + 'đ';
                document.getElementById('confirmDiscount').textContent = '-' + discountAmount.toLocaleString('vi-VN') + 'đ';
                labelTotal.textContent = 'Thành tiền';
                document.getElementById('confirmTotal').textContent = finalTotal.toLocaleString('vi-VN') + 'đ';
            } else {
                // Không giảm giá: ẩn 2 dòng trên, chỉ hiện Tổng
                rowOriginal.style.display = 'none';
                rowDiscount.style.display = 'none';
                labelTotal.textContent = 'Tổng tiền';
                document.getElementById('confirmTotal').textContent = originalTotal.toLocaleString('vi-VN') + 'đ';
            }

            if (status && document.getElementById('confirmStatus')) {
                document.getElementById('confirmStatus').textContent =
                    status.options[status.selectedIndex].text;
            }

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        });

        document.getElementById('submitBooking').addEventListener('click', function() {
            form.submit();
        });

        modal.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            });
        });

        checkout.addEventListener('change', function() {
            if (checkin.value) checkout.min = checkin.value;
        });
    })();
</script>