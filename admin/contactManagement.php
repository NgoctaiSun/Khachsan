<?php 
include '../connect.php'; 
$conn =mysqli_connect('localhost','root','','khachsan_db');
$sql="SELECT * FROM lienhe";
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
            text-align: center;
        }
    </style>
</head>
<body>
<div>
    <p>
        <h1>Quản lý liên hệ</h1>
</p>
    <div>
        <table class="admin__table">
            <tr>
                <th>ID</th>
                <th>Họ và tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Nội dung</th>
                <th>Thời gian</th>
                <th>Thao tác</th>
            </tr>
            <tr>
                <td><?php echo $row['id'] ?? ''; ?></td>
                <td><?php echo $row['hoten'] ?? ''; ?></td>
                <td><?php echo $row['email'] ?? ''; ?></td>
                <td><?php echo $row['sdt'] ?? ''; ?></td>
                <td><?php echo $row['noidung'] ?? ''; ?></td>
                <td><?php echo $row['thoigian'] ?? ''; ?></td>
                <td><button>Xóa</button></td>
            </tr>
        </table>
    </div>
</div> 
</body>
</html>