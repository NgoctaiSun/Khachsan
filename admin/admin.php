<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/UI.css">
    <script src="/Khachsan/function.js?v=2"></script>
    <title>Admin Panel</title>
</head>
<body>
<div class="admin-card">
    <nav class="nav-admin">
        <h1>Quản trị</h1>
        <ul>
            <li><a href="admin.php?page=userManagement">Quản lý người dùng</a></li>
            <li><a href="admin.php?page=roomManagement">Quản lý phòng</a></li>
            <li><a href="admin.php?page=bookingManagement">Quản lý đặt phòng</a></li>
            <li><a href="admin.php?page=commentManagement">Quản lý bình luận</a></li>
            <li><a href="admin.php?page=contactManagement">Quản lý liên hệ</a></li>
            <li><a href="admin.php?page=promotionManagement">Khuyến mãi</a></li>
            <li><a href="../index.php">Trang chủ</a></li>
        </ul>
    </nav>

    <main class="admin-content">
    <?php 
    $page=isset($_GET['page']) ? $_GET['page'] : '';
    if($page == 'userManagement') {
        include 'userManagement.php';
    }else if($page == 'roomManagement') {
        include 'roomManagement.php';
    }else if($page == 'bookingManagement'){
        include 'bookingManagement';
    }else if($page == 'commentManagement') {
        include 'commentManagement.php';
    }else if($page == 'contactManagement') {
        include 'contactManagement.php';
    }else if($page == 'promotionManagement') {
        include 'promotionManagement.php';
    }
    else {
        echo "<h2>Chào mừng đến với trang quản trị!</h2>";
}
?>
    </main>
</div>
</body>
</html>
