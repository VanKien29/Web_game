export const WELFARE_TYPES = {
    attendance_daily: {
        label: "Điểm danh hằng ngày",
        refLabel: "",
        hint: "Danh sách vật phẩm dùng để chọn ngẫu nhiên khi người chơi điểm danh.",
    },
    attendance_milestone: {
        label: "Mốc điểm danh",
        refLabel: "Số ngày điểm danh",
        hint: "Mốc tích lũy số ngày điểm danh.",
    },
    level: {
        label: "Mốc cấp độ",
        refLabel: "Cấp độ / mốc sức mạnh",
        hint: "Mốc cấp hoặc sức mạnh mà game server dùng để xét nhận quà.",
    },
    online: {
        label: "Mốc online",
        refLabel: "Số phút online",
        hint: "Số phút online trong ngày để mở mốc quà.",
    },
    daily_package: {
        label: "Gói ngày",
        refLabel: "ID gói",
        hint: "Gói mua lại theo ngày bằng cash.",
    },
    vip_package: {
        label: "Gói ưu đãi",
        refLabel: "ID gói",
        hint: "Gói ưu đãi mua một lần cho tài khoản.",
    },
    first_topup: {
        label: "Nạp đầu",
        refLabel: "Mức nạp yêu cầu",
        hint: "Tổng giá trị nạp tối thiểu để nhận quà nạp đầu.",
    },
    message: {
        label: "Nội dung hệ thống",
        refLabel: "",
        hint: "Nội dung tiếng Việt game server gửi tới người chơi.",
    },
} as const;

export type WelfareType = keyof typeof WELFARE_TYPES;

export interface WelfareItemOption {
    id: number;
    param: number;
}

export interface WelfareReward {
    item_id: number;
    amount: number;
    options: WelfareItemOption[];
    name?: string;
    icon_id?: number | null;
    key?: string;
}

export const WELFARE_TYPE_OPTIONS = Object.entries(WELFARE_TYPES).map(
    ([value, config]) => ({ value: value as WelfareType, label: config.label }),
);

export function isPackageType(type: WelfareType): boolean {
    return type === "daily_package" || type === "vip_package";
}

export function usesReference(type: WelfareType): boolean {
    return type !== "attendance_daily" && type !== "message";
}
