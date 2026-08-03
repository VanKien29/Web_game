# Deploy NRO WebGL trên VPS

Các file này triển khai Unity WebGL cùng origin với website:

```text
https://domain/play/  -> public/play
wss://domain/game    -> Nginx -> 127.0.0.1:8080 -> TCP 127.0.0.1:14445
```

Gateway chỉ bind vào localhost. Không mở cổng `8080` trên firewall.

## Giả định

- Repo được đặt tại `/var/www/nro-laravel`.
- Website Laravel đã chạy HTTPS bằng Nginx.
- Game server TCP chạy tại `127.0.0.1:14445`.
- Node.js và npm đã được cài trên VPS.

Nếu đường dẫn hoặc cổng khác, sửa đồng thời
`nginx-locations.conf` và `nro-webgl-gateway.service`.

## Cài lần đầu

```bash
cd /var/www/nro-laravel/deploy/webgl-gateway
npm ci --omit=dev

sudo cp /var/www/nro-laravel/deploy/webgl/nro-webgl-gateway.service \
  /etc/systemd/system/nro-webgl-gateway.service
sudo systemctl daemon-reload
sudo systemctl enable --now nro-webgl-gateway
```

Chép file Nginx thành snippet:

```bash
sudo cp /var/www/nro-laravel/deploy/webgl/nginx-locations.conf \
  /etc/nginx/snippets/nro-webgl.conf
```

Thêm dòng này vào bên trong HTTPS `server {}` hiện tại:

```nginx
include /etc/nginx/snippets/nro-webgl.conf;
```

Kiểm tra rồi reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Mỗi lần deploy

Sau khi merge nhánh tính năng vào `main`:

```bash
cd /var/www/nro-laravel
git pull origin main

npm ci
npm run build

cd deploy/webgl-gateway
npm ci --omit=dev

sudo systemctl restart nro-webgl-gateway
sudo nginx -t
sudo systemctl reload nginx
```

## Kiểm tra

```bash
curl http://127.0.0.1:8080/health
curl -I https://domain-cua-ban/play/Build/WebGLBuild.wasm
sudo journalctl -u nro-webgl-gateway -n 100 --no-pager
```

Kết quả `.wasm` phải có:

```text
Content-Type: application/wasm
```

Sau đó mở `https://domain-cua-ban/play/` và kiểm tra kết nối

## Lớp bảo vệ trình duyệt

WebGL đã có lớp ngăn sao chép phổ thông (menu chuột phải, kéo thả và một số
phím tắt mở DevTools/View Source), cùng các HTTP security headers trong
`nginx-locations.conf` và gateway Node. Đây chỉ là biện pháp hạn chế người
dùng thông thường; không thể bảo vệ tuyệt đối HTML/CSS/WASM đã gửi xuống
trình duyệt khỏi người có kỹ thuật.

Sau khi cập nhật cấu hình Nginx, kiểm tra và reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```
`wss://domain-cua-ban/game?port=14445` trong DevTools.
