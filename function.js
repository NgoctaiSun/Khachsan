
console.log("Harmony Hotel - Function loaded");

// Hiển thị / ẩn mật khẩu
function hienMatKhau() {
    const password = document.getElementById('password');
    if (!password) return;
    password.type = password.type === 'password' ? 'text' : 'password';
}

document.addEventListener('DOMContentLoaded', function () {

    // Popup khuyến mãi cũ
    const btn = document.getElementById('btn');
    const popup = document.getElementById('popup');
    const btnClose = document.getElementById('btnClose');
    const btnCloseX = document.getElementById('btnCloseX');

    if (btn && popup) {
        btn.addEventListener('click', () => popup.style.display = 'flex');
    }

    function hidePromotionPopup() {
        if (popup) popup.style.display = 'none';
    }

    if (btnClose) btnClose.addEventListener('click', hidePromotionPopup);
    if (btnCloseX) btnCloseX.addEventListener('click', hidePromotionPopup);

    // Popup xác nhận thao tác quản trị
    document.querySelectorAll('form[data-confirm]').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const message = form.getAttribute('data-confirm') || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Đóng popup khi nhấn ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.hh-modal.is-open').forEach(function(modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            });
        }
    });
});
