# Bàn phím hệ thống trên WebGL mobile

## Mô tả
Unity WebGL trên điện thoại dùng một dialog HTML gọn nằm trong trang fullscreen để gọi bàn phím hệ thống của Android/iOS mà không làm mất khóa landscape.

## Quy tắc
- Không dùng `window.prompt()` vì hộp thoại trình duyệt có thể thoát fullscreen và làm thiết bị quay dọc.
- Android, iPhone và iPad dùng một `input` HTML trong dialog sáng, gọn, nằm trong vùng nhìn thấy phía trên bàn phím hệ thống.
- Không dùng input ẩn, dải nhập đen hoặc cho input phủ sai vị trí trên canvas.
- Kết quả nhập, thao tác xác nhận và hủy phải được chuyển lại đúng `TField` đang focus.
- Game, dialog và bàn phím phải duy trì landscape trước, trong và sau khi nhập.
- Khi bàn phím làm thay đổi `visualViewport`, giữ nguyên kích thước game và chỉ căn lại dialog; sau khi đóng mới đồng bộ viewport và focus canvas.
- Client PC vẫn nhập trực tiếp bằng bàn phím như hiện tại.

## Ví dụ

### ✅ Đúng
```javascript
input.focus({ preventScroll: true });
```

### ❌ Sai
```javascript
const value = window.prompt(caption, currentValue);
```

## Nguồn gốc
- Ngày tạo: 2026-08-05
- Lý do: Hộp thoại `window.prompt()` làm một số Android/iOS thoát fullscreen và quay dọc; dialog trong trang giữ landscape trong khi vẫn dùng bàn phím hệ thống.
