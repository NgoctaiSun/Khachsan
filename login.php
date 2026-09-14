
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center; 
            align-items: center;
            flex-direction: column;
        }
        .design {
            background-color: #f0f0f0;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
            height: auto;
        }
        .design h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .design form {
            display: flex;
            flex-direction: column;
          
            
        }
        .btn-login {
            border: none;
            padding: 10px;
            background-color: #007bff;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            width: 300px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        input[type="text"], input[type="password"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .register {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="design">
        <h2>Đăng nhập</h2>
<form method="post" action="">
   
<div class="form-group">
    <label>Tên đăng nhập </label> 
    <input type="text" name="name" required><br> 
</div>

<div class="form-group">
    <label>Mật khẩu:</label>
    <input type="password" name="password" required><br>
</div>

<div >
    <input type="submit" value="Đăng nhập" class="btn-login">
</div>

</form> 
<div class="register">
    <span>Chưa có tài khoản?</span>
    <a href="register.php" style="text-decoration: none;">Đăng ký</a>
</div>
    </div>

</body>
</html>