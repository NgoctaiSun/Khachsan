function hienMatKhau() {
let mk =document.getElementById('password');
   if (mk.type === 'password') {
        mk.type = 'text';
    } else {
        mk.type = 'password';
    }
};