<?php
$conn = mysqli_connect('localhost', 'root', '', 'khachsan_db');

// Kiểm tra xem có truyền id và trangthai không
if (isset($_GET['id']) && isset($_GET['trangthai'])) {
    $id = intval($_GET['id']);
    $trangthai_moi = $_GET['trangthai'];

    // Chỉ cho phép 2 giá trị này để tránh lỗi bảo mật SQL Injection
    if ($trangthai_moi === 'khoa' || $trangthai_moi === 'hoatdong') {
        $sql = "UPDATE taikhoan SET trangthai = '$trangthai_moi' WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            // Cập nhật thành công -> Quay lại trang quản lý người dùng
            header("Location: admin.php?page=userManagement&msg=success");
            exit();
        } else {
            echo "Lỗi khi cập nhật: " . mysqli_error($conn);
        }
    }
} else {
    header("Location: userManagement.php");
    exit();
}
?>