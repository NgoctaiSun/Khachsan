<?php 
require_once __DIR__ . '/../connect.php';
$sql="SELECT * FROM lienhe";
$result=mysqli_query($conn,$sql);

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $delete_sql = "DELETE FROM lienhe WHERE id = $id";
    if(mysqli_query($conn, $delete_sql)) {
        header("Location: admin.php?page=contactManagement&msg=success");
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}

?>
<div>
    <p>
        <h1>Quản lý liên hệ</h1>
</p>
    <div>
        <table class="admin__table">
            <tr>
                <th>ID</th>
                <th>Họ và tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Nội dung</th>
                <th>Thời gian</th>
                <th>Thao tác</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id'] ?? ''; ?></td>
                <td><?php echo $row['hoten'] ?? ''; ?></td>
                <td><?php echo $row['email'] ?? ''; ?></td>
                <td><?php echo $row['sdt'] ?? ''; ?></td>
                <td><?php echo $row['noidung'] ?? ''; ?></td>
                <td><?php echo $row['thoigian'] ?? ''; ?></td>
                <td><a href="admin.php?page=contactManagement&id=<?= $row['id'] ?>">Xóa</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div> 
