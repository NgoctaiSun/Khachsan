
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="../css/form.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center; 
            align-items: center;
            flex-direction: column;
        }
    </style>
</head>
<body>
<div class="login-card">
        <h2>Đăng nhập</h2>
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

<div class="login-card__register">
    <span>Chưa có tài khoản?</span>
    <a href="register.php" style="text-decoration: none;">Đăng ký</a>
</div>

</div>
</body>
</html>
<script src="../function.js"></script>