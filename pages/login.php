
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

        .login-card { 
            background-color: #f0f0f0; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 

            /* SỬA: 300px trước đây + padding làm kích thước bị khó kiểm soát */
            /* 380px gồm cả padding, phần bên trong còn đúng 300px */
            width: 380px;

            height: auto; 
        } 

        .login-card h2 { 
            text-align: center; 
            margin-bottom: 20px; 
            color: #ad8d22; 
        } 

        .login-card form { 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
        } 

        .login-card__btn { 
            border: none; 
            padding: 10px; 
            background-color: #ebd09e; 
            border-radius: 10px; 
            color: white; 
            cursor: pointer; 

            /* SỬA: 300px cố định -> 100% để bằng input */
            width: 100%;

            margin-top: 10px; 
        } 

        .login-card__btn:hover { 
            background-color: #ad8d22; 
        } 

        .login-card__form { 
            display: flex; 
            flex-direction: column; 
            gap: 10px; 
            width: 100%; 
        } 

        .login-card__form label { 
            font-size: 14px; 
            color: #555; 
            font-weight: 600; 
        } 

        .Matkhau { 
            position: relative; 
            display: flex; 
            align-items: center; 

            /* GIỮ: đảm bảo khung mật khẩu rộng bằng khung tên */
            width: 100%; 
        } 

        #mat { 
            position: absolute; 

            /* GIỮ: icon nằm bên phải */
            right: 10px; 

            /* THÊM: đưa icon vào chính giữa theo chiều dọc */
            top: 50%;
            transform: translateY(-50%);

            background: none; 
            border: none; 
            cursor: pointer; 
            font-size: 16px; 
            opacity: 0.6; 
            transition: opacity 0.2s; 
        } 

        input[type="text"], 
        input[type="password"] { 
            padding: 10px; 
            border: 1px solid #ebd09e; 
            border-radius: 5px; 

            /* GIỮ: cả 2 input cùng width */
            width: 100%; 

            /* THÊM: tránh padding làm input rộng hơn khung */
            box-sizing: border-box;
        } 

        /* THÊM: chừa khoảng trống cho icon 👁️ */
        input[type="password"] {
            padding-right: 40px;
        }

        .login-card__register { 
            display: flex; 
            justify-content: center; 
            gap: 10px; 
            margin-top: 20px; 
        } 
    </style>
</head>
<body>
<div class="login-card">
        <h2>Đăng nhập</h2>
<form method="post" action="process_login.php">

    <div class="login-card__form">
        <label>👤 Tên đăng nhập </label> 
        <input type="text" name="name" required>
    </div>

    <div class="login-card__form">
        <label>🔒 Mật khẩu</label>
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