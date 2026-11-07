<?php
session_start();
$conn = mysqli_connect('localhost', 'root', '', 'khachsan_db');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Kiểm tra xem có truyền id và cập nhật trạng thái
if (isset($_GET['id']) && ($action === 'dangan' || $action === 'danghienthi')) {
    $id = intval($_GET['id']);
    $trangthai_moi = $_GET['action'];

    // Chỉ cho phép 2 giá trị này để tránh lỗi bảo mật SQL Injection
    if ($trangthai_moi === 'dangan' || $trangthai_moi === 'danghienthi') {
        $sql = "UPDATE binhluan SET trangthai = '$trangthai_moi' WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            // Cập nhật thành công -> Quay lại trang quản lý bình luận
            header("Location: admin.php?page=commentManagement&msg=success");
            exit();
        } else {
            echo "Lỗi khi cập nhật: " . mysqli_error($conn);
        }
    }
} 
// Xử lý xóa bình luận
elseif ( $action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $delete_sql = "DELETE FROM binhluan WHERE id = $id";
    if (mysqli_query($conn, $delete_sql)) {
        header("Location: admin.php?page=commentManagement&msg=success");
        exit();
    } else {
        $error_message = "Error deleting record: " . mysqli_error($conn);
    }
}
// Xử lý phản hồi từ admin
elseif($action === 'reply' && isset($_POST['id_binhluan'])) {
    $id_binhluan = (int)$_POST['id_binhluan'];
    $phanhoi_admin = mysqli_real_escape_string($conn, $_POST['phanhoi_admin']);
    $update_sql = "UPDATE binhluan SET phanhoi_admin = '$phanhoi_admin', ngay_phanhoi = NOW() WHERE id = $id_binhluan";
    if (mysqli_query($conn, $update_sql)) {
        header("Location: admin.php?page=commentManagement&msg=success");
        exit();
    } else {
        $error_message = "Error updating record: " . mysqli_error($conn);
    }
}
// Xử lý bình luận từ người dùng
elseif($action === 'binhluan' && isset($_POST['id_loaiphong']) && isset($_POST['noidung'])) {
    $id_loaiphong = (int)$_POST['id_loaiphong'];
    $id_phong = (int)$_POST['id_phong'];
    $id_datphong = (int)$_POST['id_datphong'];
    $noidung = mysqli_real_escape_string($conn, $_POST['noidung']);
    $sao=(int)$_POST['sao'];
    $id_taikhoan = $_SESSION['id'] ?? null; // Giả sử bạn lưu ID người dùng trong session

    
        $insert_sql = "INSERT INTO binhluan ( id_taikhoan, id_datphong, id_phong, sao, noidung, ngaybinhluan, trangthai) 
        VALUES ( $id_taikhoan, $id_datphong, $id_phong, $sao, '$noidung', NOW(), 'danghienthi')";
        if (mysqli_query($conn, $insert_sql)) {
            header("Location: ../index.php?page=room_detail&id=$id_loaiphong&msg=success");
            exit();
        } else {
            $error_message = "Error inserting record: " . mysqli_error($conn);
        }
    
    
}
else {
    header("Location: commentManagement.php");
    exit();
}
?>