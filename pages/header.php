<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="index.php">
            <span class="brand-logo">HS</span>
            <span class="brand-text">
                <strong>HARMONY</strong>
                <small>HOTEL & RESORT</small>
            </span>
        </a>

        <button class="menu-toggle" type="button" onclick="toggleMenu()">☰</button>

        <nav id="mainNav" class="main-nav">
            <a href="index.php?page=home">Trang chủ</a>
            <a href="index.php?page=rooms">Phòng</a>
            <a href="index.php?page=about">Giới thiệu</a>
            <a href="index.php?page=contact">Liên hệ</a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="index.php?page=profile">Profile</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/admin.php">Quản trị</a>
                <?php endif; ?>
                <a class="nav-button" href="pages/logout.php">Đăng xuất</a>
            <?php else: ?>
                <a class="nav-button" href="index.php?page=login">Đăng nhập</a>
                <a href="index.php?page=register">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
function toggleMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('show');
}
</script>
