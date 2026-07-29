# Kết nối Unity WebGL qua WebSocket

## Mô tả
Unity WebGL kết nối tới game server thông qua WebSocket gateway, trong khi các client native tiếp tục sử dụng TCP hiện tại.

## Quy tắc
- Build Unity WebGL phải dùng WebSocket nhị phân.
- Gateway chỉ chuyển tiếp byte giữa WebSocket và TCP, không thay đổi protocol packet của game.
- WebGL chỉ kết nối WebSocket cùng origin qua `/game`; không ghi log hoặc nhúng hostname/IP TCP của VPS vào binary WebGL.
- Địa chỉ TCP thật chỉ được giữ trong nhánh build native. Production phải đặt WebSocket origin sau reverse proxy/CDN và chặn truy cập công khai trực tiếp tới cổng TCP game.
- Client Windows, Android và các nền tảng native tiếp tục dùng TCP.
- Logic mã hóa, command và payload hiện tại phải được dùng chung giữa hai transport.
- Giai đoạn phát triển chạy local bằng HTTP và `ws://`; production mới chuyển sang HTTPS và `wss://`.

## Ví dụ

### ✅ Đúng
```text
Unity WebGL -> ws://localhost:8080/game -> gateway -> 127.0.0.1:14445
Unity Android -> TCP -> game server
```

### ❌ Sai
```text
Unity WebGL -> System.Net.Sockets.TcpClient -> game server
```

## Nguồn gốc
- Ngày tạo: 2026-07-29
- Lý do: Người dùng chọn WebSocket để phát hành client web cho người chơi iPhone mà không cần App Store hoặc TestFlight.
