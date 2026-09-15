<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/form.css">
    <title>Admin Panel</title>
    <style>
        .admin-card {
            display: flex;
        }
        .nav-admin {
            width: 30%;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .nav-admin ul {
            list-style-type: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .nav-admin li {
            display: inline;
        }
        .nav-admin a {
            text-decoration: none;
            color: #007bff;
        }
        .admin-content {
            width: 70%;
            padding: 10px;
        }
</style>
</head>
<body>
<div class="admin-card">
    <nav class="nav-admin">
        <h1>Quản trị</h1>
        <ul>
            <li><a href="admin.php?page=userManagement">Quản lý người dùng</a></li>
            <li><a href="admin.php?page=productManagement">Quản lý sản phẩm</a></li>
        </ul>
    </nav>

    <main class="admin-content">
    <?php 
    $page=isset($_GET['page']) ? $_GET['page'] : '';
    if($page == 'userManagement') {
        include 'userManagement.php';
    }else if($page == 'productManagement') {
        include 'productManagement.php';
    } else {
        include 'admin.php';
}
?>
    </main>
</div>
</body>
</html>