<?php
require_once __DIR__ . '/../connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php?page=login'); exit; }

$typeId=(int)($_GET['id']??0);
$s=$conn->prepare("SELECT lp.*, COALESCE(gg.phantram,0) AS phantram FROM loaiphong lp LEFT JOIN giamgia gg ON gg.id_loaiphong=lp.id AND gg.trangthai=1 AND CURDATE() BETWEEN gg.ngaybatdau AND gg.ngayketthuc WHERE lp.id=? LIMIT 1");
$s->bind_param("i",$typeId); $s->execute(); $room=$s->get_result()->fetch_assoc();
if(!$room){ header('Location:index.php?page=rooms'); exit; }

$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $roomId=(int)($_POST['id_phong']??0);
    $checkIn=$_POST['ngaynhan']??'';
    $checkOut=$_POST['ngaytra']??'';
    $guests=(int)($_POST['sokhach']??1);
    $note=trim($_POST['ghichu']??'');

    if($roomId<=0 || $checkIn==='' || $checkOut==='' || $checkOut <= $checkIn || $guests<1 || $guests>(int)$room['sokhach']){
        $message='<div class="notice error">Vui lòng chọn phòng, ngày và số khách hợp lệ.</div>';
    } else {
        $p=$conn->prepare("SELECT p.*, lp.gia, lp.sokhach, COALESCE(gg.phantram,0) AS phantram
            FROM phong p JOIN loaiphong lp ON lp.id=p.id_loaiphong
            LEFT JOIN giamgia gg ON gg.id_loaiphong=lp.id AND gg.trangthai=1 AND CURDATE() BETWEEN gg.ngaybatdau AND gg.ngayketthuc
            WHERE p.id=? AND p.id_loaiphong=? AND p.trangthai NOT IN ('Da dat','Bao tri')
            AND NOT EXISTS (SELECT 1 FROM datphong d WHERE d.id_phong=p.id AND d.trangthai NOT IN ('Huy','Đã hủy') AND d.ngaynhan < ? AND d.ngaytra > ?)
            LIMIT 1");
        $p->bind_param("iiss",$roomId,$typeId,$checkOut,$checkIn); $p->execute(); $selected=$p->get_result()->fetch_assoc();
        if(!$selected){
            $message='<div class="notice error">Phòng không tồn tại, đang bảo trì hoặc đã có người đặt trong khoảng thời gian này.</div>';
        } else {
            $days=(new DateTime($checkIn))->diff(new DateTime($checkOut))->days;
            $discount=(float)($selected['phantram'] ?? 0);
            $price=(float)$selected['gia']*(1-$discount/100);
            $total=(int)round($days*$price);
            $status='Cho xac nhan';
            $now=date('Y-m-d H:i:s');
            $insert=$conn->prepare("INSERT INTO datphong(id_taikhoan,id_phong,ngaynhan,ngaytra,sokhach,tongtien,trangthai,thoigiandat,ghichu) VALUES(?,?,?,?,?,?,?,?,?)");
            $insert->bind_param("iissiiiss",$_SESSION['user_id'],$roomId,$checkIn,$checkOut,$guests,$total,$status,$now,$note);
            if($insert->execute()) $message='<div class="notice success">Đặt phòng thành công. Mã đặt phòng: #'.$insert->insert_id.'</div>';
            else $message='<div class="notice error">Không thể tạo đơn đặt phòng: '.htmlspecialchars($insert->error).'</div>';
        }
    }
}

$rooms=$conn->prepare("SELECT * FROM phong WHERE id_loaiphong=? AND trangthai NOT IN ('Da dat','Bao tri') ORDER BY sophong");
$rooms->bind_param("i",$typeId); $rooms->execute(); $available=$rooms->get_result();
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="page-wrap">
    <div class="profile-card">
        <div class="section-title"><h2>Đặt phòng: <?= htmlspecialchars($room['tenphong']) ?></h2></div>
        <?= $message ?>
        <form method="post">
            <label>Phòng *</label>
            <select class="form-control" name="id_phong" required>
                <option value="">-- Chọn phòng --</option>
                <?php while($p=$available->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>">Phòng <?= htmlspecialchars($p['sophong']) ?> - Tầng <?= htmlspecialchars($p['tang']) ?></option>
                <?php endwhile; ?>
            </select>
            <label>Ngày nhận *</label>
            <input class="form-control" type="date" name="ngaynhan" required min="<?= date('Y-m-d') ?>">
            <label>Ngày trả *</label>
            <input class="form-control" type="date" name="ngaytra" required min="<?= date('Y-m-d') ?>">
            <label>Số khách *</label>
            <input class="form-control" type="number" name="sokhach" min="1" max="<?= (int)$room['sokhach'] ?>" value="1" required>
            <label>Ghi chú</label>
            <textarea class="form-control" name="ghichu" rows="4"></textarea>
            <button class="btn" type="submit">Xác nhận đặt phòng</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>