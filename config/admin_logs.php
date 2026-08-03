<?php

return [
    // Giữ summary để tra cứu, xóa cả bản ghi sau thời hạn này.
    'retention_days' => (int) env('ADMIN_LOG_RETENTION_DAYS', 90),

    // Sau thời hạn này chỉ giữ metadata tóm tắt, bỏ before/after/meta nặng.
    'detail_retention_days' => (int) env('ADMIN_LOG_DETAIL_RETENTION_DAYS', 30),

    'prune_chunk' => (int) env('ADMIN_LOG_PRUNE_CHUNK', 1000),
    'state_string_max_length' => (int) env('ADMIN_LOG_STATE_STRING_MAX_LENGTH', 1000),

    // Runtime list/health thường bị gọi nhiều lần nhưng không tạo thay đổi dữ liệu.
    'log_runtime_read_success' => filter_var(env('ADMIN_LOG_RUNTIME_READ_SUCCESS', false), FILTER_VALIDATE_BOOL),
    'runtime_include_result' => filter_var(env('ADMIN_LOG_RUNTIME_INCLUDE_RESULT', false), FILTER_VALIDATE_BOOL),
];
