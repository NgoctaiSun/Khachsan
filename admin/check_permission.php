<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php?page=login");
    exit;
}

// Hàm kiểm tra quyền
function checkRole($roles) {
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        echo "<script>
            alert('Bạn không có quyền truy cập chức năng này!');
            window.location.href = '../index.php';
        </script>";
        exit;
    }
}
?>