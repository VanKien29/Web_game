# Validation mật khẩu client

## Mô tả

Mật khẩu đăng ký tài khoản game phải đủ an toàn nhưng không bắt buộc người chơi sử dụng ký tự đặc biệt.

## Quy tắc

- Mật khẩu đăng ký dài từ 6 đến 18 ký tự.
- Mật khẩu bắt buộc có ít nhất một chữ cái và một chữ số.
- Ký tự đặc biệt có thể có hoặc không.
- Không chấp nhận khoảng trắng hoặc ký tự Unicode trong mật khẩu.
- Frontend và backend phải áp dụng cùng một quy tắc.
- Nút hiện/ẩn mật khẩu dùng icon Font Awesome, có nhãn truy cập bằng `aria-label`, không dùng chữ “Hiện” hoặc “Ẩn” làm nội dung nhìn thấy.

## Ví dụ

### ✅ Đúng

```text
abc123
abc123!
```

### ❌ Sai

```text
abcdef
123456
abc 123
```

## Nguồn gốc

- Ngày tạo: 2026-07-29
- Lý do: Người dùng xác nhận ký tự đặc biệt trong mật khẩu là tùy chọn và nút hiện/ẩn mật khẩu phải dùng icon.
