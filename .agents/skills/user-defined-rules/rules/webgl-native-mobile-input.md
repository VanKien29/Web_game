# Nhập liệu native trên WebGL mobile

## Mô tả
Unity WebGL trên điện thoại dùng hộp thoại nhập native do trình duyệt và hệ điều hành cung cấp, thay vì dựng một input hoặc modal HTML riêng phủ lên game.

## Quy tắc
- Android, iPhone và iPad phải mở hộp thoại nhập native của trình duyệt cho mọi trường nhập trong game WebGL.
- Không tạo `input`, `textarea`, modal hoặc dải nhập HTML tùy chỉnh phủ lên canvas.
- Kết quả nhập, thao tác xác nhận và hủy phải được chuyển lại đúng `TField` đang focus.
- Game và hộp thoại native phải duy trì landscape trước, trong và sau khi nhập; không được chuyển sang portrait ở bất kỳ bước nào.
- Trước khi mở hộp thoại phải khóa landscape; sau khi đóng chỉ đồng bộ viewport, âm thanh và focus canvas trong khi tiếp tục giữ landscape.
- Client PC vẫn nhập trực tiếp bằng bàn phím như hiện tại.

## Ví dụ

### ✅ Đúng
```javascript
const value = window.prompt(caption, currentValue);
```

### ❌ Sai
```javascript
const input = document.createElement('input');
document.body.appendChild(input);
input.focus();
```

## Nguồn gốc
- Ngày tạo: 2026-08-05
- Lý do: Người dùng yêu cầu bỏ ô nhập HTML tự dựng trên mobile và dùng hộp thoại riêng của thiết bị để giảm lỗi giao diện, xoay màn hình và bàn phím.
