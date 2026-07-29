# Bố cục trang xác thực client

## Mô tả
Trang đăng nhập và đăng ký dùng chung khung điều hướng của client, không còn là trang độc lập tách khỏi website.

## Quy tắc
- Trang đăng nhập và đăng ký phải hiển thị header và footer chung của client.
- Không đặt thêm header thương hiệu riêng bên trong form xác thực để tránh lặp giao diện.
- Input xác thực không đổi sang border hoặc outline màu xanh khi focus.
- Nút hiện/ẩn mật khẩu phải render glyph Font Awesome thật, không bị thay bằng sprite pixel toàn cục.
- Thông báo validate phải nằm trong vùng có chiều cao cố định; việc hiện hoặc ẩn thông báo không được làm form, background hay banner bên cạnh thay đổi kích thước.

## Ví dụ

### ✅ Đúng
```vue
<header class="game-header">...</header>
<main class="inner-page auth-page-host">...</main>
<footer class="game-footer">...</footer>
```

### ❌ Sai
```vue
<header v-if="!isAuthPage" class="game-header">...</header>
```

## Nguồn gốc
- Ngày tạo: 2026-07-29
- Lý do: Người dùng yêu cầu gắn lại header/footer cho hai trang xác thực, dùng icon con mắt Font Awesome thật và bỏ border xanh khi focus input.
