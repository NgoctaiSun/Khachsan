<?php 
include '../connect.php'; 
// Nếu file ../connect.php đã tạo sẵn biến $conn thì có thể bỏ dòng mysqli_connect dưới đây:
if (!$conn) {
    $conn = mysqli_connect('localhost', 'root', '', 'khachsan_db');
}

$message = '';

// 1. Xử lý lưu form POST trước khi SELECT dữ liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'them_nguoi_dung') {
    $hoten   = trim($_POST['hoten'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $matkhau = $_POST['matkhau'] ?? '';
    $sdt     = trim($_POST['sdt'] ?? '');
    $diachi  = trim($_POST['diachi'] ?? '');

    if ($hoten === '' || $email === '' || $matkhau === '') {
        $message = '<script>alert("Vui lòng nhập đầy đủ họ tên, email và mật khẩu.");</script>';
    } else {
        // Kiểm tra xem email hoặc họ tên đã tồn tại chưa
        $check = $conn->prepare("SELECT id FROM taikhoan WHERE email = ? OR hoten = ? LIMIT 1");
        $check->bind_param("ss", $email, $hoten);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            $message = '<script>alert("Email hoặc tên người dùng đã tồn tại!");</script>';
        } else {
            $hash   = password_hash($matkhau, PASSWORD_DEFAULT);
            $role   = 'khachhang';
            $status = 'hoatdong';
            
            $stmt = $conn->prepare("INSERT INTO taikhoan(hoten, matkhau, sdt, diachi, email, ngaytao, trangthai, vaitro) VALUES(?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->bind_param("sssssss", $hoten, $hash, $sdt, $diachi, $email, $status, $role);

            if ($stmt->execute()) {
                // Tải lại trang để cập nhật danh sách ngay lập tức
                echo "<script>alert('Thêm người dùng mới thành công!'); window.location.href=window.location.href;</script>";
                exit;
            } else {
                $message = '<script>alert("Lỗi SQL: '.addslashes($stmt->error).'");</script>';
            }
        }
    }
}

// 2. Truy vấn danh sách hiển thị ra bảng
$sql = "SELECT * FROM taikhoan WHERE vaitro = 'khachhang'";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Quản lý người dùng</title>
    <style>
        .admin__table {
            border-collapse: collapse;
            width: 100%;
            border: 1px solid #ddd;
            text-align: center;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
        }
        tr:nth-child(even) { background-color: aliceblue; }
        tr:hover { background-color: aquamarine; transition: 0.3s; }

        #popup {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .popup-content {
            background: white;
            padding: 25px;
            border-radius: 8px;
            width: 420px;
            max-width: 90%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .popup-header h3 { margin: 0; color: #333; }

        .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #888;
        }

        .form-group {
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            margin-bottom: 4px;
            font-weight: bold;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 9px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .popup-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-submit {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-add {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php echo $message; ?>

<div>
    <h1>Quản lý người dùng</h1>
    <button id="btn" class="btn-add">+ Thêm người mới</button>

    <!-- Modal Popup Form -->
    <div id="popup">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Thêm người dùng mới</h3>
                <button type="button" class="close-btn" id="btnCloseX">&times;</button>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="them_nguoi_dung">

                <div class="form-group">
                    <label>Họ và tên *</label>
                    <input class="form-control" type="text" name="hoten" required placeholder="Nhập họ và tên">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input class="form-control" type="email" name="email" required placeholder="example@gmail.com">
                </div>

                <div class="form-group">
                    <label>Mật khẩu *</label>
                    <input class="form-control" type="password" name="matkhau" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                </div>

                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input class="form-control" type="text" name="sdt" placeholder="Nhập số điện thoại">
                </div>

                <div class="form-group">
                    <label>Địa chỉ</label>
                    <input class="form-control" type="text" name="diachi" placeholder="Nhập địa chỉ">
                </div>

                <div class="popup-actions">
                    <button type="button" class="btn-cancel" id="btnClose">Hủy</button>
                    <button type="submit" class="btn-submit">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách người dùng -->
    <div>
        <table class="admin__table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên khách hàng</th>
                    <th>Sđt</th>
                    <th>Địa chỉ</th>
                    <th>Email</th>
                    <th>Ngày đăng ký</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr> 
                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['hoten'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['sdt'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['diachi'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['ngaytao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['trangthai'] ?? '') ?></td>
                    <td>
                        <?php if (($row['trangthai'] ?? '') === 'khoa'): ?>
                            <a href="process_blockUser.php?id=<?= $row['id'] ?>&trangthai=hoatdong" 
                               onclick="return confirm('Bạn có chắc chắn muốn MỞ KHÓA tài khoản này?')"
                               style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                               Mở khóa
                            </a>
                        <?php else: ?>
                            <a href="process_blockUser.php?id=<?= $row['id'] ?>&trangthai=khoa" 
                               onclick="return confirm('Bạn có chắc chắn muốn KHÓA tài khoản này?')"
                               style="background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                               Khóa
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div> 

<script>
    let btn = document.getElementById("btn");
    let popup = document.getElementById("popup");
    let btnClose = document.getElementById("btnClose");
    let btnCloseX = document.getElementById("btnCloseX");

    btn.onclick = function() {
        popup.style.display = "flex";
    };

    function hidePopup() {
        popup.style.display = "none";
    }

    btnClose.onclick = hidePopup;
    btnCloseX.onclick = hidePopup;
</script>
</body>
</html>