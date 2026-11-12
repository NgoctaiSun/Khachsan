
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id'], $_SESSION['role'])) {
    header('Location: ../index.php?page=login');
    exit;
}

$role = $_SESSION['role'];
$allowedRoles = ['admin','letan','ketoan'];

if (!in_array($role,$allowedRoles,true)) {
    header('Location: ../index.php');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';

$menu = [
    'roomManagement' => ['label'=>'Quản lý phòng','roles'=>['admin','letan'],'icon'=>'▦'],
    'bookingManagement' => ['label'=>'Quản lý đặt phòng','roles'=>['admin','letan'],'icon'=>'▣'],
    'userManagement' => ['label'=>'Quản lý người dùng','roles'=>['admin','letan'],'icon'=>'♙'],
    'roomTypeManagement' => ['label'=>'Quản lý loại phòng','roles'=>['admin'],'icon'=>'▤'],
    'commentManagement' => ['label'=>'Quản lý bình luận','roles'=>['admin'],'icon'=>'✉'],
    'contactManagement' => ['label'=>'Quản lý liên hệ','roles'=>['admin'],'icon'=>'☎'],
    'promotionManagement' => ['label'=>'Quản lý khuyến mãi','roles'=>['admin'],'icon'=>'✦'],
    'revenueManagement' => ['label'=>'Quản lý doanh thu','roles'=>['admin','ketoan'],'icon'=>'◈'],
    'incomeAndExpenseManagement' => ['label'=>'Quản lý thu chi','roles'=>['admin','ketoan'],'icon'=>'₫']
];

$allowedPages = [
    'userManagement'=>'userManagement.php',
    'roomTypeManagement'=>'roomTypeManagement.php',
    'roomManagement'=>'roomManagement.php',
    'bookingManagement'=>'bookingManagement.php',
    'commentManagement'=>'commentManagement.php',
    'contactManagement'=>'contactManagement.php',
    'promotionManagement'=>'promotionManagement.php',
    'revenueManagement'=>'revenueManagement.php',
    'incomeAndExpenseManagement'=>'incomeAndExpenseManagement.php'
];

if (isset($allowedPages[$page])) {
    if (!isset($menu[$page]) || !in_array($role,$menu[$page]['roles'],true)) {
        http_response_code(403);
        $page = 'forbidden';
    }
} elseif ($page !== 'dashboard') {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Harmony Hotel | Quản trị</title>
    <link rel="stylesheet" href="../css/UI.css">
    <link rel="stylesheet" href="../css/page.css">
    <link rel="stylesheet" href="../css/UI_KT.css">
    <link rel="stylesheet" href="../css/harmony-admin.css?v=1">
    <script src="../function.js?v=5" defer></script>

    <style>
        body { background:#f5f4f0; }
        .hh-admin-shell { display:flex; min-height:100vh; }
        .hh-sidebar {
            width:255px; flex:0 0 255px;
            background:linear-gradient(180deg,#172b3a,#203c4e);
            color:#fff; padding:27px 17px;
            box-sizing:border-box;
        }
        .hh-brand { padding:0 12px 25px; border-bottom:1px solid rgba(255,255,255,.12); }
        .hh-brand strong { display:block; font-size:21px; letter-spacing:1px; }
        .hh-brand span { color:#d3b783; font-size:10px; letter-spacing:2px; }
        .hh-admin-user { padding:18px 12px; color:#dbe2e5; font-size:12px; line-height:1.8; }
        .hh-sidebar-label { color:#b5a27f; font-size:10px; letter-spacing:1.5px; padding:15px 12px 8px; }
        .hh-sidebar a {
            display:flex; align-items:center; gap:12px;
            padding:12px; margin:4px 0; border-radius:9px;
            color:#e6ecef; text-decoration:none; font-size:13px;
            transition:.2s;
        }
        .hh-sidebar a:hover,.hh-sidebar a.active {
            background:rgba(199,166,111,.2); color:#f2d5a4;
        }
        .hh-menu-icon { width:20px; text-align:center; color:#d0ad72; font-size:17px; }
        .hh-admin-main { flex:1; min-width:0; }
        .hh-topbar {
            height:68px; background:#fff; border-bottom:1px solid #e9e5dd;
            display:flex; justify-content:space-between; align-items:center;
            padding:0 30px; box-sizing:border-box;
        }
        .hh-topbar strong { color:#172b3a; }
        .hh-topbar a { color:#a98249; text-decoration:none; font-size:13px; }
        .hh-dashboard-welcome {
            background:linear-gradient(120deg,#1a3345,#355267);
            color:#fff; border-radius:17px; padding:32px; margin-bottom:24px;
        }
        .hh-dashboard-welcome h1 { margin:0 0 10px; font-size:26px; }
        .hh-dashboard-welcome p { margin:0; color:#d8e0e5; font-size:14px; }
        @media(max-width:850px) {
            .hh-admin-shell { display:block; }
            .hh-sidebar { width:100%; padding:14px; }
            .hh-brand { padding:5px 10px 12px; }
            .hh-admin-user { padding:8px 10px; }
            .hh-sidebar nav { display:flex; flex-wrap:wrap; gap:5px; }
            .hh-sidebar-label { width:100%; }
            .hh-sidebar a { padding:9px; font-size:12px; }
            .hh-topbar { padding:0 15px; }
        }
    </style>
</head>
<body>
<div class="hh-admin-shell">
    <aside class="hh-sidebar">
        <div class="hh-brand">
            <strong>HARMONY HOTEL</strong>
            <span>MANAGEMENT SYSTEM</span>
        </div>

        <div class="hh-admin-user">
            Xin chào, <strong><?= htmlspecialchars($_SESSION['user'] ?? 'Quản trị viên') ?></strong><br>
            Vai trò: <?= htmlspecialchars($role) ?>
        </div>

        <nav>
            <div class="hh-sidebar-label">TRUNG TÂM QUẢN TRỊ</div>
            <a class="<?= $page==='dashboard'?'active':'' ?>" href="admin.php">
                <span class="hh-menu-icon">⌂</span> Tổng quan
            </a>

            <?php foreach ($menu as $key=>$item): ?>
                <?php if (in_array($role,$item['roles'],true)): ?>
                    <a class="<?= $page===$key?'active':'' ?>" href="admin.php?page=<?= urlencode($key) ?>">
                        <span class="hh-menu-icon"><?= $item['icon'] ?></span>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="hh-sidebar-label">ĐIỀU HƯỚNG</div>
            <a href="../index.php"><span class="hh-menu-icon">⌂</span> Về trang khách sạn</a>
            <a href="../logout.php"><span class="hh-menu-icon">↪</span> Đăng xuất</a>
        </nav>
    </aside>

    <main class="hh-admin-main">
        <header class="hh-topbar">
            <strong>Hệ thống quản lý khách sạn</strong>
            <a href="../index.php">Xem website ↗</a>
        </header>

        <?php if ($page==='dashboard'): ?>
            <div class="hh-page">
                <div class="hh-dashboard-welcome">
                    <span class="hh-eyebrow">WELCOME TO HARMONY HOTEL</span>
                    <h1>Xin chào <?= htmlspecialchars($_SESSION['user'] ?? 'bạn') ?>!</h1>
                    <p>Chúc bạn một ngày làm việc hiệu quả. Chọn chức năng quản lý ở thanh điều hướng để bắt đầu.</p>
                </div>
                <div class="hh-stat-grid">
                    <div class="hh-stat-card"><span>Hệ thống</span><strong>Harmony</strong><small>Hotel Management</small></div>
                    <div class="hh-stat-card"><span>Tài khoản</span><strong><?= htmlspecialchars($role) ?></strong><small>Vai trò hiện tại</small></div>
                    <div class="hh-stat-card"><span>Ngày làm việc</span><strong><?= date('d/m/Y') ?></strong><small>Ngày hiện tại</small></div>
                    <div class="hh-stat-card"><span>Trạng thái</span><strong>Online</strong><small>Hệ thống quản trị</small></div>
                </div>
                <section class="hh-card">
                    <div class="hh-card-heading"><div><span class="hh-eyebrow">QUICK ACCESS</span><h2>Truy cập nhanh</h2></div></div>
                    <div class="hh-actions">
                        <?php foreach (['roomManagement','bookingManagement'] as $key): ?>
                            <?php if (in_array($role,$menu[$key]['roles'],true)): ?>
                                <a class="hh-btn hh-btn-primary" href="admin.php?page=<?= $key ?>"><?= $menu[$key]['label'] ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        <?php elseif ($page==='forbidden'): ?>
            <div class="hh-page"><div class="hh-alert hh-error">Bạn không có quyền truy cập chức năng này.</div></div>
        <?php elseif (isset($allowedPages[$page]) && is_file(__DIR__.'/'.$allowedPages[$page])): ?>
            <?php include __DIR__.'/'.$allowedPages[$page]; ?>
        <?php else: ?>
            <div class="hh-page"><div class="hh-alert hh-error">Không tìm thấy chức năng được yêu cầu.</div></div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
