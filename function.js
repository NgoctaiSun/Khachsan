console.log("function.js đã chạy");
// Hàm hiển thị mật khẩu
function hienMatKhau() {
    let mk = document.getElementById('password');

    if (!mk) return;

    if (mk.type === 'password') {
        mk.type = 'text';
    } else {
        mk.type = 'password';
    }
}
// Hàm hiển thị popup
document.addEventListener("DOMContentLoaded", function () {

    let btn = document.getElementById("btn");
    let popup = document.getElementById("popup");
    let btnClose = document.getElementById("btnClose");
    let btnCloseX = document.getElementById("btnCloseX");

    if (btn && popup) {
        btn.onclick = function () {
            popup.style.display = "flex";
        };
    }

    function hidePopup() {
        if (popup) {
            popup.style.display = "none";
        }
    }

    if (btnClose) {
        btnClose.onclick = hidePopup;
    }

    if (btnCloseX) {
        btnCloseX.onclick = hidePopup;
    }

});