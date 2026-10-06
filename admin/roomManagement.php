<?php
session_start();
require_once __DIR__ . '/../connect.php';
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../index.php?page=login'); exit; }
$message=''; $editRoom=null;
if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $stmt=$conn->prepare("DELETE FROM phong WHERE id=?"); $stmt->bind_param("i",$id);
    $message=$stmt->execute()?'<div class="notice success">Đã xóa phòng.</div>':'<div class="notice error">Không thể xóa phòng. Có thể phòng đang được sử dụng trong đơn đặt.</div>';
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??''; $id=(int)($_POST['id']??0); $sophong=(int)($_POST['sophong']??0); $type=(int)($_POST['id_loaiphong']??0); $tang=(int)($_POST['tang']??0); $trangthai=trim($_POST['trangthai']??'Trong'); $ghichu=trim($_POST['ghichu']??'');
    if($action==='add' || $action==='edit'){
        if($sophong<=0 || $type<=0) $message='<div class="notice error">Vui lòng nhập số phòng và chọn loại phòng.</div>';
        elseif($action==='add'){
            $stmt=$conn->prepare("INSERT INTO phong(sophong,id_loaiphong,tang,trangthai,ghichu) VALUES(?,?,?,?,?)"); $stmt->bind_param("iiiss",$sophong,$type,$tang,$trangthai,$ghichu);
            $message=$stmt->execute()?'<div class="notice success">Thêm phòng thành công.</div>':'<div class="notice error">Thêm phòng thất bại: '.htmlspecialchars($stmt->error).'</div>';
        } else {
            $stmt=$conn->prepare("UPDATE phong SET sophong=?,id_loaiphong=?,tang=?,trangthai=?,ghichu=? WHERE id=?"); $stmt->bind_param("iiissi",$sophong,$type,$tang,$trangthai,$ghichu,$id);
            $message=$stmt->execute()?'<div class="notice success">Cập nhật phòng thành công.</div>':'<div class="notice error">Cập nhật phòng thất bại.</div>';
        }
    } elseif($action==='discount'){
        $discount=max(0,min(100,(float)($_POST['phantram']??0))); $start=$_POST['ngaybatdau']??date('Y-m-d'); $end=$_POST['ngayketthuc']??date('Y-m-d'); $active=isset($_POST['trangthai'])?1:0;
        if($end<$start) $message='<div class="notice error">Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.</div>';
        else {
            $stmt=$conn->prepare("INSERT INTO giamgia(id_loaiphong,phantram,ngaybatdau,ngayketthuc,trangthai) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE phantram=VALUES(phantram),ngaybatdau=VALUES(ngaybatdau),ngayketthuc=VALUES(ngayketthuc),trangthai=VALUES(trangthai)");
            $stmt->bind_param("idssi",$type,$discount,$start,$end,$active);
            $message=$stmt->execute()?'<div class="notice success">Đã lưu giảm giá.</div>':'<div class="notice error">Không thể lưu giảm giá: '.htmlspecialchars($stmt->error).'</div>';
        }
    }
}
if(isset($_GET['edit'])){ $id=(int)$_GET['edit']; $stmt=$conn->prepare("SELECT * FROM phong WHERE id=?"); $stmt->bind_param("i",$id); $stmt->execute(); $editRoom=$stmt->get_result()->fetch_assoc(); }
$types=$conn->query("SELECT * FROM loaiphong ORDER BY tenphong");
$rooms=$conn->query("SELECT p.*,lp.tenphong,lp.gia,COALESCE(gg.phantram,0) phantram FROM phong p JOIN loaiphong lp ON lp.id=p.id_loaiphong LEFT JOIN giamgia gg ON gg.id_loaiphong=lp.id ORDER BY p.id DESC");
?>
<link rel="stylesheet" href="../css/hotel.css">
<div class="page-wrap"><div class="section-title"><h1>Quản lý phòng</h1><p>Thêm, sửa, xóa phòng và quản lý giảm giá.</p></div><?= $message ?>
<div class="admin-form"><h3><?= $editRoom?'Cập nhật phòng':'Thêm phòng mới' ?></h3><form method="post"><input type="hidden" name="action" value="<?= $editRoom?'edit':'add' ?>"><?php if($editRoom): ?><input type="hidden" name="id" value="<?= $editRoom['id'] ?>"><?php endif; ?>
<label>Số phòng</label><input class="form-control" type="number" name="sophong" min="1" required value="<?= htmlspecialchars($editRoom['sophong']??'') ?>">
<label>Loại phòng</label><select class="form-control" name="id_loaiphong" required><option value="">-- Chọn loại phòng --</option><?php while($t=$types->fetch_assoc()): ?><option value="<?= $t['id'] ?>" <?= isset($editRoom['id_loaiphong'])&&$editRoom['id_loaiphong']==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['tenphong']) ?></option><?php endwhile; ?></select>
<label>Tầng</label><input class="form-control" type="number" name="tang" min="0" value="<?= htmlspecialchars($editRoom['tang']??0) ?>">
<label>Trạng thái</label><select class="form-control" name="trangthai"><option value="Trong">Trong</option><option value="Da dat">Đã đặt</option><option value="Bao tri">Bảo trì</option></select>
<label>Ghi chú</label><textarea class="form-control" name="ghichu" rows="3"><?= htmlspecialchars($editRoom['ghichu']??'') ?></textarea><br><button class="btn" type="submit"><?= $editRoom?'Cập nhật':'Thêm phòng' ?></button><?php if($editRoom): ?><a class="btn btn-secondary" href="admin.php?page=roomManagement">Hủy</a><?php endif; ?></form></div>
<div class="admin-form"><h3>Giảm giá theo loại phòng</h3><form method="post"><input type="hidden" name="action" value="discount"><select class="form-control" name="id_loaiphong" required><option value="">-- Chọn loại phòng --</option><?php $types2=$conn->query("SELECT id,tenphong FROM loaiphong ORDER BY tenphong"); while($t=$types2->fetch_assoc()): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['tenphong']) ?></option><?php endwhile; ?></select><br><input class="form-control" type="number" name="phantram" min="0" max="100" step="0.01" placeholder="Phần trăm giảm"><br><div class="search-grid"><div><label>Ngày bắt đầu</label><input class="form-control" type="date" name="ngaybatdau" value="<?= date('Y-m-d') ?>" required></div><div><label>Ngày kết thúc</label><input class="form-control" type="date" name="ngayketthuc" value="<?= date('Y-m-d',strtotime('+30 days')) ?>" required></div></div><label><input type="checkbox" name="trangthai" checked> Đang áp dụng</label><br><button class="btn" type="submit">Lưu giảm giá</button></form></div>
<table class="admin-table"><tr><th>ID</th><th>Số phòng</th><th>Loại</th><th>Tầng</th><th>Giá</th><th>Giảm</th><th>Trạng thái</th><th>Thao tác</th></tr><?php while($r=$rooms->fetch_assoc()): ?><tr><td><?= $r['id'] ?></td><td><?= $r['sophong'] ?></td><td><?= htmlspecialchars($r['tenphong']) ?></td><td><?= $r['tang'] ?></td><td><?= number_format($r['gia'],0,',','.') ?> đ</td><td><?= $r['phantram'] ?>%</td><td><?= htmlspecialchars($r['trangthai']) ?></td><td><a class="btn btn-secondary" href="admin.php?page=roomManagement&edit=<?= $r['id'] ?>">Sửa</a> <a class="btn btn-danger" href="admin.php?page=roomManagement&delete=<?= $r['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa phòng này?')">Xóa</a></td></tr><?php endwhile; ?></table></div>
