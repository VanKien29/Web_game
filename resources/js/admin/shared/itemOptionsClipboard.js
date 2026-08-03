const CLIPBOARD_KEY = "admin_item_options_clipboard_v1";
export const ITEM_OPTIONS_CLIPBOARD_EVENT = "admin:item-options-clipboard-updated";

export function cloneItemOptions(options) {
    if (!Array.isArray(options)) return [];

    return options
        .filter((option) => {
            if (!option || option._pending || option.confirmed === false) {
                return false;
            }

            const id = Number(Array.isArray(option) ? option[0] : option.id);
            return Number.isFinite(id) && id >= 0;
        })
        .map((option) => ({
            id: Number(Array.isArray(option) ? option[0] : option.id),
            param: Math.max(
                0,
                Number(Array.isArray(option) ? option[1] : option.param) || 0,
            ),
        }));
}

export function readItemOptionsClipboard() {
    if (typeof window === "undefined") return [];

    try {
        const value = JSON.parse(window.localStorage.getItem(CLIPBOARD_KEY) || "[]");
        return cloneItemOptions(value);
    } catch {
        return [];
    }
}

export function writeItemOptionsClipboard(options) {
    const cloned = cloneItemOptions(options);
    if (typeof window === "undefined" || !cloned.length) return cloned;

    try {
        window.localStorage.setItem(CLIPBOARD_KEY, JSON.stringify(cloned));
        window.dispatchEvent(new CustomEvent(ITEM_OPTIONS_CLIPBOARD_EVENT));
    } catch {
        // Clipboard is a convenience; ignore storage errors.
    }

    return cloned;
}
