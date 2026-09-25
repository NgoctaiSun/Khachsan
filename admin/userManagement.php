<?php 
include '../connect.php'; 
$conn =mysqli_connect('localhost','root','','khachsan_db');
$sql="SELECT * FROM taikhoan where vaitro= 'khachhang' ";
$result=mysqli_query($conn,$sql);

$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin panel</title>
    <style>
        .admin__table {
            border-collapse: collapse;
            width: 100%;
            border: 1px solid #ddd;
            text-align:center;
        }
        #popup {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);

    justify-content: center;
    align-items: center;
}

.popup-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
}
    </style>
</head>
<body>
<div>
    <p>
        <h1>Quản lý người dùng</h1>
        <button id="btn">Bấm vào đây</button>

<div id="popup">
    <div class="popup-content">
        <h3>Thông báo</h3>
        <form method="post" action="">

    <div class="login-card__form">
        <label>Tên đăng nhập </label> 
        <input type="text" name="name" required>
    </div>

    <div class="login-card__form">
        <label>Mật khẩu</label>
        <div class="Matkhau">
            <input type="password" name="password" id="password" required>
            <button type="button" id="mat" onclick="hienMatKhau()">👁️</button>
        </div>
    </div>

    <div >
        <input type="submit" value="Đăng nhập" class="login-card__btn">
    </div>
</form>
        <button>Lưu</button>
        <button id="btnClose">Thoát</button>
    </div>
</div>
</p>

    <div>
        <table class="admin__table">
            <tr>
                <th>ID</th>
                <th>Tên khách hàng</th>
                <th>Sđt</th>
                <th>Địa chỉ</th>
                <th>Email</th>
                <th>Ngày đăng ký</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            <tr>
                <td><?php echo $row['id'] ??''?></td>
                <td><?php echo $row['hovaten'] ??''?></td>
                <td><?php echo $row['sdt'] ??''?></td>
                <td><?php echo $row['diachi'] ??''?></td>
                <td><?php echo $row['email'] ??''?></td>
                <td><?php echo $row['ngaytao'] ??''?></td>
                <td><?php echo $row['trangthai'] ??''?></td>
                <td><button id='btn_xoa'>Khóa</button></td>

            </tr>
        </table>
    </div>
</div> 
</body>
</html>
<script>
    let btn = document.getElementById("btn");
let popup = document.getElementById("popup");
let btnClose = document.getElementById("btnClose");

btn.onclick = function() {
    popup.style.display = "flex";
};

btnClose.onclick = function() {
    popup.style.display = "none";
};
</script>