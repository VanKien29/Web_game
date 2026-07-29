# Mã nạp ATM cố định

## Mô tả
Nội dung chuyển khoản ATM dùng mã thanh toán cố định, ngẫu nhiên theo tài khoản thay vì chứa username, tên nhân vật hoặc ID tài khoản.

## Quy tắc
- Mỗi tài khoản game có đúng một mã nạp ATM cố định.
- Mã phải được sinh ngẫu nhiên ở backend, có prefix nhận diện `NRH`, ngắn và chỉ gồm ký tự chữ/số dễ nhập.
- Mã được liên kết bằng `account_id`, không liên kết bằng username hoặc tên nhân vật vì các trường này không bảo đảm duy nhất.
- Client chỉ nhận nội dung chuyển khoản hoàn chỉnh từ API đã xác thực; không tự tạo mã từ dữ liệu `localStorage`.
- Webhook phải chuẩn hóa chữ hoa/thường và bỏ khoảng trắng hoặc dấu phân cách trước khi tra mã.
- Cột mã và `account_id` phải có unique index để tra cứu nhanh và ngăn cấp trùng.
- Mã giao dịch từ nhà cung cấp vẫn phải được kiểm tra duy nhất để chống cộng tiền lặp.
- Cú pháp username cũ chỉ được giữ làm fallback chuyển tiếp và phải từ chối nếu username không duy nhất.

## Ví dụ

### ✅ Đúng
```text
NRH7K9M2Q4X8P6D
```

### ❌ Sai
```text
naptien hoanbuom
naptien 123
```

## Nguồn gốc
- Ngày tạo: 2026-07-29
- Lý do: Người dùng chọn mã nạp cố định để nội dung chuyển khoản không làm lộ tài khoản và vẫn nhẹ, ổn định cho webhook.
