<?php
session_start();
require_once __DIR__ . '/../connect.php'; // Điều chỉnh lại đường dẫn kết nối DB cho đúng với thư mục của bạn

// 1. Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=login");
    exit;
}

$username =$_SESSION['user'];

// 2. Lấy dữ liệu từ form gửi lên (POST)
$id_loaiphong = isset($_POST['id_loaiphong']) ? (int)$_POST['id_loaiphong'] : 0;
$id_phong     = isset($_POST['id_phong']) ? (int)$_POST['id_phong'] : 0;
$sao          = isset($_POST['sao']) ? (int)$_POST['sao'] : 5;
$noidung      = trim($_POST['noidung'] ?? '');

// Kiểm tra dữ liệu cơ bản
if ($id_loaiphong <= 0 || $id_phong <= 0 || empty($noidung) || $sao < 1 || $sao > 5) {
    echo "<script>alert('Dữ liệu không hợp lệ!'); window.history.back();</script>";
    exit;
}

// 3. Lấy ID của tài khoản từ bảng taikhoan dựa vào username trong session
$stmtUser =$conn->prepare("SELECT id FROM taikhoan WHERE username = ? LIMIT 1");
$stmtUser->bind_param("s", $username);$stmtUser->execute();
$userResult =$stmtUser->get_result()->fetch_assoc();

if (!$userResult) {
    echo "<script>alert('Không tìm thấy thông tin tài khoản!'); window.history.back();</script>";
    exit;
}
$id_taikhoan =$userResult['id'];

// 4. KIỂM TRA BẢO MẬT: Người dùng này đã từng đặt phòng ($id_phong) này chưa?
// Đồng thời kiểm tra phòng đó có thực sự thuộc loại phòng ($id_loaiphong) đang đánh giá hay không.
$sqlCheck = "SELECT datphong.id 
             FROM datphong 
             JOIN phong ON datphong.id_phong = phong.id
             WHERE datphong.id_taikhoan = ? 
               AND datphong.id_phong = ? 
               AND phong.id_loaiphong = ? 
             LIMIT 1";

$stmtCheck =$conn->prepare($sqlCheck);$stmtCheck->bind_param("iii", $id_taikhoan,$id_phong, $id_loaiphong);$stmtCheck->execute();
$checkResult =$stmtCheck->get_result();

if ($checkResult->num_rows === 0) {
    // Nếu chưa từng đặt phòng này -> Từ chối lưu đánh giá
    echo "<script>alert('Lỗi: Bạn chưa từng đặt phòng này nên không có quyền đánh giá!'); window.history.back();</script>";
    exit;
}

// 5. THỰC THI LƯU ĐÁNH GIÁ: Nếu hợp lệ, tiến hành INSERT vào bảng binhluan
// (Lưu ý: Đảm bảo tên các cột `id_taikhoan`, `id_phong`, `sao`, `noidung`, `ngaybinhluan` trùng khớp với cấu trúc CSDL của bạn)
$sqlInsert = "INSERT INTO binhluan (id_taikhoan, id_phong, sao, noidung, ngaybinhluan) VALUES (?, ?, ?, ?, NOW())";
$stmtInsert =$conn->prepare($sqlInsert);$stmtInsert->bind_param("iiis", $id_taikhoan,$id_phong, $sao,$noidung);

if ($stmtInsert->execute()) {
    // Thành công -> Quay lại trang chi tiết loại phòng trước đó
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    echo "<script>alert('Có lỗi xảy ra khi gửi đánh giá, vui lòng thử lại sau!'); window.history.back();</script>";
    exit;
}