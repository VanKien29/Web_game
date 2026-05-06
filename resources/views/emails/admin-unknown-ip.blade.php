@php
    $lookupUrl = 'https://ipinfo.io/' . rawurlencode($newIp);
    $adminUrl = rtrim(config('app.url'), '/') . '/admin/login';
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Cảnh báo đăng nhập admin</title>
</head>

<body style="margin: 0; padding: 0; background: #eef2f7; color: #111827; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #eef2f7; padding: 28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; overflow: hidden; background: #ffffff; border: 1px solid #d9e1ec; border-radius: 12px;">
                    <tr>
                        <td style="padding: 26px 28px; background: #991b1b; color: #ffffff;">
                            <div style="font-size: 13px; line-height: 1.4; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; opacity: .9;">
                                Cảnh báo bảo mật
                            </div>
                            <h1 style="margin: 8px 0 0; font-size: 24px; line-height: 1.25; font-weight: 800;">
                                Đăng nhập admin từ IP lạ
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 26px 28px 8px;">
                            <p style="margin: 0 0 18px; color: #374151; font-size: 15px; line-height: 1.65;">
                                Hệ thống vừa ghi nhận một phiên đăng nhập admin có IP khác với IP đã lưu trước đó.
                                Nếu đây không phải bạn, hãy đổi mật khẩu ngay và kiểm tra lại quyền admin.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; font-size: 14px;">
                                <tr>
                                    <td style="width: 142px; padding: 13px 0; color: #6b7280; border-top: 1px solid #edf1f6;">Tài khoản</td>
                                    <td style="padding: 13px 0; border-top: 1px solid #edf1f6; font-weight: 700; color: #111827;">
                                        {{ $account->username }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 142px; padding: 13px 0; color: #6b7280; border-top: 1px solid #edf1f6;">IP mới</td>
                                    <td style="padding: 13px 0; border-top: 1px solid #edf1f6;">
                                        <span style="display: inline-block; max-width: 100%; padding: 8px 10px; background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; border-radius: 7px; font-family: Consolas, Monaco, monospace; font-size: 13px; line-height: 1.45; word-break: break-all;">
                                            {{ $newIp }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 142px; padding: 13px 0; color: #6b7280; border-top: 1px solid #edf1f6;">IP cũ</td>
                                    <td style="padding: 13px 0; border-top: 1px solid #edf1f6; color: #111827; font-family: Consolas, Monaco, monospace; font-size: 13px; word-break: break-all;">
                                        {{ $oldIp ?: 'Chưa có IP đã lưu' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 142px; padding: 13px 0; color: #6b7280; border-top: 1px solid #edf1f6;">Thời gian</td>
                                    <td style="padding: 13px 0; border-top: 1px solid #edf1f6; color: #111827;">
                                        {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 18px 28px 26px;">
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-radius: 8px; background: #991b1b;">
                                        <a href="{{ $lookupUrl }}" style="display: inline-block; padding: 12px 16px; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none;">
                                            Xem thông tin IP
                                        </a>
                                    </td>
                                    <td style="width: 10px;"></td>
                                    <td style="border-radius: 8px; background: #f3f4f6; border: 1px solid #d1d5db;">
                                        <a href="{{ $adminUrl }}" style="display: inline-block; padding: 11px 16px; color: #111827; font-size: 14px; font-weight: 700; text-decoration: none;">
                                            Mở trang admin
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 16px 28px; background: #f8fafc; color: #6b7280; font-size: 12px; line-height: 1.5; border-top: 1px solid #e5e7eb;">
                            Email này được gửi tự động bởi {{ config('app.name') }}. Không cần trả lời email này.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
