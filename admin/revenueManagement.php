<?php
require_once __DIR__ . '/../connect.php';
require_once 'check_permission.php';

// Bước 1: Kiểm tra quyền truy cập (cho phép Kế toán hoặc Admin)
checkRole(['ketoan', 'admin']);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Có thể bổ sung kiểm tra quyền theo cấu trúc session của dự án.
// Ví dụ: chỉ cho phép kế toán và admin truy cập.

// 1. Nhận điều kiện thống kê
$loai = $_GET['loai'] ?? 'thang';
$ngay = $_GET['ngay'] ?? date('Y-m-d');
$thang = (int)($_GET['thang'] ?? date('m'));
$nam = (int)($_GET['nam'] ?? date('Y'));
$tu_ngay = $_GET['tu_ngay'] ?? date('Y-m-01');
$den_ngay = $_GET['den_ngay'] ?? date('Y-m-d');

$loai_hop_le = ['ngay', 'thang', 'khoang'];
if (!in_array($loai, $loai_hop_le, true)) {
    $loai = 'thang';
}

// 2. Xác định ngày bắt đầu và kết thúc
$loi = '';

try {
    if ($loai === 'ngay') {
        $d = new DateTime($ngay);
        $tu = $d->format('Y-m-d');
        $den = $tu;
    } elseif ($loai === 'thang') {
        if ($thang < 1 || $thang > 12 || $nam < 2000 || $nam > 2100) {
            throw new Exception('Tháng hoặc năm không hợp lệ.');
        }

        $d = new DateTime(sprintf('%04d-%02d-01', $nam, $thang));
        $tu = $d->format('Y-m-01');
        $den = $d->format('Y-m-t');
    } else {
        $d1 = new DateTime($tu_ngay);
        $d2 = new DateTime($den_ngay);

        $tu = $d1->format('Y-m-d');
        $den = $d2->format('Y-m-d');

        if ($tu > $den) {
            throw new Exception('Ngày bắt đầu không được lớn hơn ngày kết thúc.');
        }
    }
} catch (Exception $e) {
    $loi = $e->getMessage();
    $tu = date('Y-m-01');
    $den = date('Y-m-d');
}

// 3. Hàm lấy dữ liệu thống kê
function layThongKe($conn, $tu, $den) {
   $sql = "
    SELECT
        COUNT(CASE WHEN loaiphieu = 'thu' THEN 1 END) AS so_phieu_thu,
        COUNT(CASE WHEN loaiphieu = 'chi' THEN 1 END) AS so_phieu_chi,

        COALESCE(SUM(CASE
            WHEN loaiphieu = 'thu' THEN sotien
            ELSE 0
        END), 0) AS tong_thu,

        COALESCE(SUM(CASE
            WHEN loaiphieu = 'chi' THEN sotien
            ELSE 0
        END), 0) AS tong_chi

    FROM thuchi
    WHERE thoigian >= ?
      AND thoigian < DATE_ADD(?, INTERVAL 1 DAY)
";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $tu, $den);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

// 4. Lấy dữ liệu tổng hợp
$thongke = layThongKe($conn, $tu, $den);

$tong_thu = (float)$thongke['tong_thu'];
$tong_chi = (float)$thongke['tong_chi'];
$chenh_lech = $tong_thu - $tong_chi;
$so_phieu_thu = (int)$thongke['so_phieu_thu'];
$so_phieu_chi = (int)$thongke['so_phieu_chi'];

// 5. Lấy doanh thu theo từng ngày
$sql_ngay = "
    SELECT
        DATE(thoigian) AS ngay,

        COUNT(CASE
            WHEN loaiphieu = 'thu' THEN 1
        END) AS so_phieu_thu,

        COUNT(CASE
            WHEN loaiphieu = 'chi' THEN 1
        END) AS so_phieu_chi,

        SUM(CASE
            WHEN loaiphieu = 'thu' THEN sotien
            ELSE 0
        END) AS doanhthu,

        SUM(CASE
            WHEN loaiphieu = 'chi' THEN sotien
            ELSE 0
        END) AS tongchi,

        SUM(CASE
            WHEN loaiphieu = 'thu' THEN sotien
            WHEN loaiphieu = 'chi' THEN -sotien
            ELSE 0
        END) AS loinhuan

    FROM thuchi
    WHERE thoigian >= ?
      AND thoigian < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY DATE(thoigian)
    ORDER BY ngay DESC
";

$stmt = $conn->prepare($sql_ngay);
$stmt->bind_param("ss", $tu, $den);
$stmt->execute();
$result_ngay = $stmt->get_result();

// 6. Xuất dữ liệu CSV để mở bằng Excel
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt_export = $conn->prepare($sql_ngay);
    $stmt_export->bind_param("ss", $tu, $den);
    $stmt_export->execute();
    $result_export = $stmt_export->get_result();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="bao_cao_doanh_thu.csv"');

    // BOM giúp Excel đọc tiếng Việt đúng hơn
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ngày', 'Số phiếu thu', 'Tổng doanh thu', 'Tổng chi']);

    while ($row = $result_export->fetch_assoc()) {
        fputcsv($output, [
            $row['ngay'],
            $row['so_phieu_thu'],
            $row['doanhthu'],
            $row['tongchi']
        ]);
    }

    fclose($output);
    exit;
}
?>

<div class="revenue-container">
    <h2>QUẢN LÝ DOANH THU</h2>

    <!-- Form chọn thời gian -->
    <form method="GET" action="admin.php" class="filter-form">
        <input type="hidden" name="page" value="revenueManagement">

        <label>Hình thức thống kê:</label>
        <select name="loai" id="loaiThongKe" onchange="doiLoaiThongKe()">
            <option value="ngay" <?= $loai === 'ngay' ? 'selected' : '' ?>>Theo ngày</option>
            <option value="thang" <?= $loai === 'thang' ? 'selected' : '' ?>>Theo tháng</option>
            <option value="khoang" <?= $loai === 'khoang' ? 'selected' : '' ?>>Theo khoảng thời gian</option>
        </select>

        <div id="chonNgay" style="<?= $loai === 'ngay' ? '' : 'display:none;' ?>">
            <label>Chọn ngày:</label>
            <input type="date" name="ngay" value="<?= htmlspecialchars($ngay) ?>">
        </div>

        <div id="chonThang" style="<?= $loai === 'thang' ? '' : 'display:none;' ?>">
            <label>Tháng:</label>
            <select name="thang">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= $thang === $i ? 'selected' : '' ?>>
                        Tháng <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>

            <label>Năm:</label>
            <input type="number" name="nam" min="2000" max="2100"
                   value="<?= htmlspecialchars((string)$nam) ?>">
        </div>

        <div id="chonKhoang" style="<?= $loai === 'khoang' ? '' : 'display:none;' ?>">
            <label>Từ ngày:</label>
            <input type="date" name="tu_ngay" value="<?= htmlspecialchars($tu_ngay) ?>">

            <label>Đến ngày:</label>
            <input type="date" name="den_ngay" value="<?= htmlspecialchars($den_ngay) ?>">
        </div>

        <button type="submit">Thống kê</button>
    </form>

    <?php if ($loi !== ''): ?>
        <p class="error"><?= htmlspecialchars($loi) ?></p>
    <?php endif; ?>

    <p>
        <strong>Thời gian thống kê:</strong>
        <?= date('d/m/Y', strtotime($tu)) ?>
        đến
        <?= date('d/m/Y', strtotime($den)) ?>
    </p>

    <!-- Các ô thống kê -->
    <div class="revenue-cards">
        <div class="revenue-card">
            <h3>Số phiếu thu</h3>
            <p><?= number_format($so_phieu_thu) ?></p>
        </div>
            <div class="revenue-card">
    <h3>Số phiếu chi</h3>
    <p><?= number_format($so_phieu_chi) ?></p>
</div>
        <div class="revenue-card">
            <h3>Tổng doanh thu</h3>
            <p><?= number_format($tong_thu, 0, ',', '.') ?> VNĐ</p>
        </div>

        <div class="revenue-card">
            <h3>Tổng chi</h3>
            <p><?= number_format($tong_chi, 0, ',', '.') ?> VNĐ</p>
        </div>

        <div class="revenue-card">
            <h3>Chênh lệch thu chi</h3>
            <p><?= number_format($chenh_lech, 0, ',', '.') ?> VNĐ</p>
        </div>
    </div>

    <!-- Bảng thống kê -->
    <h3>CHI TIẾT DOANH THU THEO NGÀY</h3>

    <table class="revenue-table">
    <thead>
        <tr>
            <th>STT</th>
            <th>Ngày</th>
            <th>Số phiếu thu</th>
            <th>Số phiếu chi</th>
            <th>Doanh thu</th>
            <th>Tổng chi</th>
            <th>Lợi nhuận</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($result_ngay->num_rows > 0): ?>
            <?php $stt = 1; ?>

            <?php while ($row = $result_ngay->fetch_assoc()): ?>
                <tr>
                    <td><?= $stt++ ?></td>

                    <td>
                        <?= date('d/m/Y', strtotime($row['ngay'])) ?>
                    </td>

                    <td><?= number_format($row['so_phieu_thu']) ?></td>

                    <td><?= number_format($row['so_phieu_chi']) ?></td>

                    <td>
                        <?= number_format($row['doanhthu'], 0, ',', '.') ?> VNĐ
                    </td>

                    <td>
                        <?= number_format($row['tongchi'], 0, ',', '.') ?> VNĐ
                    </td>

                    <td>
                        <strong>
                            <?= number_format($row['loinhuan'], 0, ',', '.') ?> VNĐ
                        </strong>
                    </td>
                </tr>
            <?php endwhile; ?>

        <?php else: ?>
            <tr>
                <td colspan="7">
                    Không có dữ liệu trong khoảng thời gian này.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

    <!-- Xuất Excel -->
    <a class="export-btn"
       href="admin.php?page=revenueManagement&loai=<?= urlencode($loai) ?>
       &ngay=<?= urlencode($ngay) ?>
       &thang=<?= $thang ?>&nam=<?= $nam ?>
       &tu_ngay=<?= urlencode($tu_ngay) ?>
       &den_ngay=<?= urlencode($den_ngay) ?>&export=csv">
        Xuất Excel (CSV)
    </a>
</div>

<script>
function doiLoaiThongKe() {
    const loai = document.getElementById('loaiThongKe').value;

    document.getElementById('chonNgay').style.display =
        loai === 'ngay' ? 'block' : 'none';

    document.getElementById('chonThang').style.display =
        loai === 'thang' ? 'block' : 'none';

    document.getElementById('chonKhoang').style.display =
        loai === 'khoang' ? 'block' : 'none';
}
</script>

<style>
.revenue-container {
    padding: 20px;
}

.filter-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.filter-form input,
.filter-form select,
.filter-form button {
    padding: 9px;
}

.filter-form button,
.export-btn {
    background: #2F3E46;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
}

.revenue-cards {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.revenue-card {
    padding: 18px;
    border-radius: 8px;
    background: #F2F5F5;
    text-align: center;
}

.revenue-card h3 {
    font-size: 16px;
}

.revenue-card p {
    font-size: 20px;
    font-weight: bold;
    color: #2F3E46;
}

.revenue-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0 25px;
}

.revenue-table th,
.revenue-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}

.revenue-table th {
    background: #2F3E46;
    color: white;
}

.error {
    color: red;
}
</style>