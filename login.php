
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
            gap:20px;
        }
        .btn-login {
            border: none;
            padding: 10px;
            background-color: #007bff;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            width: 300px;
            margin-top: 10px;
        }
        .btn-login:hover {
        background-color: #0056b3;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .form-group label {
        font-size: 14px;
        color: #555;
        font-weight: 600;
            }
        .Matkhau {
            position: relative;
            display: flex;
            align-items: center;
        }
        #mat {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        input[type="text"], input[type="password"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
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
    <input type="text" name="name" required>
</div>

<div class="form-group">
    <label>Mật khẩu</label>
    <div class="Matkhau">
        <input type="password" name="password" id="password" required>
        <button id="mat">👁️</button>
    </div>
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
<script>
document.getElementById('mat').addEventListener('click', function(event) {
   let mk =document.getElementById('password');
   if (mk.type === 'password') {
        mk.type = 'text';
    } else {
        mk.type = 'password';
    }
    event.preventDefault();
});
</script>