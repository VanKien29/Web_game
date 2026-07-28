# Giao diện pixel cho client công khai

## Mô tả
Toàn bộ giao diện client công khai của Ngọc Rồng Horizon sử dụng ngôn ngữ thiết kế RPG 16-bit, theme sáng và có thể thay đổi UX để tạo trải nghiệm nhất quán. Quy tắc này áp dụng cho các route client hiện tại và mọi route client được bổ sung trong tương lai; không áp dụng cho khu vực quản trị nếu chưa có yêu cầu riêng.

## Quy tắc
- Dùng chính ngôn ngữ hình ảnh của game Ngọc Rồng Online làm chuẩn: sprite chibi tỉ lệ nhỏ, pixel rõ, nền map 2D và UI ô vuông gợi cảm giác game mobile đời cũ; không chuyển sang phong cách tiên hiệp, JRPG fantasy hoặc pixel art chung chung.
- Chỉ dùng theme sáng; không triển khai dark mode cho client.
- Thay các banner, background và hình nhân vật anime hiện tại bằng artwork pixel đồng bộ.
- Ưu tiên tái sử dụng sprite và tài nguyên game NRO sẵn có trong dự án để giữ đúng nhận diện; artwork tạo mới phải bám sát tỉ lệ, mật độ chi tiết và bảng màu của hình ảnh trong game.
- Icon điều hướng, icon ứng dụng, vật phẩm nhận diện và logo ngọc rồng phải dùng sprite pixel đồng bộ; không dùng icon font hiện đại hoặc ghép nhiều phong cách icon khác nhau cho các vị trí chính.
- Được phép tổ chức lại bố cục và UX của từng trang nhưng phải giữ nguyên nghiệp vụ, dữ liệu và hợp đồng API trừ khi người dùng yêu cầu khác.
- Dùng Handjet tự host làm font pixel dễ đọc ở các vị trí tạo điểm nhấn như logo chữ, tiêu đề ngắn, badge hoặc nút chính.
- Nội dung dài, bài viết, tin tức, bảng dữ liệu và thông tin cần đọc rõ phải giữ font sans-serif tiếng Việt dễ đọc hiện tại.
- Không dùng ảnh nền lớn với `background-attachment: fixed`; ảnh trang trí phải được nén sang WebP và tránh lặp lại nhiều lớp để giữ cuộn trang mượt.
- Design token và theme dùng Tailwind CSS v4 theo hướng CSS-first; component Vue mới dùng Composition API với `<script setup lang="ts">`.
- Thiết kế phải responsive và nhất quán trên desktop lẫn mobile.
- Trang Home đã được người dùng chốt; không thay đổi `HomePage.vue` hoặc CSS riêng của Home nếu chưa có yêu cầu mới nói rõ cần sửa lại trang này.

## Ví dụ

### ✅ Đúng
```vue
<h1 class="font-pixel text-pixel-ink">Đua Top</h1>
<article class="font-sans text-readable-ink">
    {{ post.content }}
</article>
```

### ❌ Sai
```vue
<article class="font-pixel text-xs">
    {{ post.content }}
</article>
```

## Nguồn gốc
- Ngày tạo: 2026-07-28
- Lý do: Người dùng xác nhận redesign toàn bộ client theo phong cách pixel của game Ngọc Rồng Online, thay toàn bộ artwork, dùng theme sáng, cho phép thay đổi UX và chỉ dùng font pixel ở một số vị trí trang trí. Người dùng sửa lại hướng hình ảnh sau khi bản nháp đầu tiên bị lệch sang phong cách tiên hiệp/JRPG.
