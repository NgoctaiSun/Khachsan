<?php
require_once __DIR__ . '/../connect.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt=$conn->prepare("SELECT lp.*, COALESCE(gg.phantram,0) AS phantram FROM loaiphong lp LEFT JOIN giamgia gg ON gg.id_loaiphong=lp.id AND gg.trangthai=1 AND CURDATE() BETWEEN gg.ngaybatdau AND gg.ngayketthuc WHERE lp.id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$room=$stmt->get_result()->fetch_assoc();
if(!$room){
    include __DIR__.'/header.php';
    echo '<div class="page-wrap"><div class="notice error">Không tìm thấy loại phòng.</div></div>';
    include __DIR__.'/footer.php';
    exit;
}
$roomsStmt=$conn->prepare("SELECT * FROM phong WHERE id_loaiphong=? ORDER BY sophong");
$roomsStmt->bind_param("i",$id);
$roomsStmt->execute();
$physicalRooms=$roomsStmt->get_result();
$discount=(float)($room['phantram'] ?? 0);
$finalPrice=(float)$room['gia']*(1-$discount/100);
$img=trim($room['anhphong']??'');
$imgPath=$img!==''?'images/rooms/'.htmlspecialchars($img):'images/room-placeholder.svg';
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="page-wrap">
    <div class="detail">
        <div>
            <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($room['tenphong']) ?>">
        </div>
        <div>
            <h1><?= htmlspecialchars($room['tenphong']) ?></h1>
            <?php if($discount>0): ?><span class="badge-discount">Đang giảm <?= $discount ?>%</span><?php endif; ?>
            <p class="price">
                <?php if($discount>0): ?><span class="old-price"><?= number_format($room['gia'],0,',','.') ?> đ</span><?php endif; ?>
                <?= number_format($finalPrice,0,',','.') ?> đ/đêm
            </p>
            <ul class="info-list">
                <li><strong>Số khách:</strong> <?= htmlspecialchars($room['sokhach']) ?> người</li>
                <li><strong>Diện tích:</strong> <?= htmlspecialchars($room['dientich']) ?> m²</li>
                <li><strong>Mô tả:</strong> <?= nl2br(htmlspecialchars($room['mota'] ?? '')) ?></li>
            </ul>
            <h3>Các phòng thuộc loại này</h3>
            <?php if($physicalRooms->num_rows): ?>
                <ul>
                <?php while($p=$physicalRooms->fetch_assoc()): ?>
                    <li>Phòng <?= htmlspecialchars($p['sophong']) ?> – <?= htmlspecialchars($p['trangthai']) ?></li>
                <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>Chưa có phòng cụ thể được cập nhật.</p>
            <?php endif; ?>
            <?php if(isset($_SESSION['user'])): ?>
                <a class="btn" href="index.php?page=booking&id=<?= (int)$room['id'] ?>">Đặt phòng</a>
            <?php else: ?>
                <a class="btn" href="index.php?page=login">Đăng nhập để đặt phòng</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
