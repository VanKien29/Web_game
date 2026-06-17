<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Hộp quà</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span class="sep">/</span>
                    <span class="current">Hộp quà</span>
                </nav>
            </div>
            <button class="btn btn-primary" type="button" @click="openCreate">
                <span class="mi" style="font-size: 16px">add</span>
                Tạo hộp quà
            </button>
        </div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="search"
                        class="form-input search-input"
                        placeholder="Tìm ID hoặc tên hộp..."
                        @input="debouncedLoadPage"
                    />
                </div>
                <button class="btn btn-primary btn-sm" type="submit">
                    <span class="mi" style="font-size: 16px">filter_list</span>
                    Lọc
                </button>
            </form>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="card">
            <div class="table-wrap">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Hộp quà</th>
                            <th>Type</th>
                            <th>Reward</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="box in boxes" :key="box.id">
                            <td>#{{ box.item_id }}</td>
                            <td><AdminIcon :icon-id="box.icon_id" class="box-icon" /></td>
                            <td>
                                <div class="item-name">{{ box.name }}</div>
                                <div class="item-meta">{{ box.description || box.item_name || "" }}</div>
                            </td>
                            <td><span class="badge badge-info">{{ box.item_type ?? 27 }}</span></td>
                            <td>{{ box.reward_count }}</td>
                            <td>
                                <span class="badge" :class="box.active ? 'badge-success' : 'badge-muted'">
                                    {{ box.active ? "Đang bật" : "Tắt" }}
                                </span>
                            </td>
                            <td class="action-cell">
                                <button class="btn btn-primary btn-sm" type="button" @click="openEdit(box.id)">
                                    <span class="mi" style="font-size: 14px">edit</span>
                                    Sửa
                                </button>
                                <button class="btn btn-outline btn-sm" type="button" @click="deleteBox(box)">
                                    <span class="mi" style="font-size: 14px">delete</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="loading" class="admin-loading-row">
                            <td colspan="7"><span class="admin-loading-row__content"><span class="admin-loading-spinner"></span></span></td>
                        </tr>
                        <tr v-if="!boxes.length && !loading">
                            <td colspan="7" class="empty-cell">Chưa có hộp quà nào.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="loadPage(page - 1)">&laquo;</button>
                <template v-for="p in paginationItems" :key="String(p)">
                    <span v-if="typeof p !== 'number'" class="pagination-ellipsis">...</span>
                    <button v-else :class="{ active: p === page }" @click="loadPage(p)">{{ p }}</button>
                </template>
                <button :disabled="page >= totalPages" @click="loadPage(page + 1)">&raquo;</button>
            </div>
        </div>

        <div v-if="editor.open" class="editor-overlay" @click.self="closeEditor">
            <div class="editor-panel gift-box-editor">
                <div class="editor-head">
                    <div>
                        <h3>{{ editor.isEdit ? "Sửa hộp quà" : "Tạo hộp quà" }}</h3>
                        <p>Item hộp là item_template type 27, reward được game đọc từ bảng cấu hình.</p>
                    </div>
                    <button class="icon-action" type="button" @click="closeEditor"><span class="mi">close</span></button>
                </div>

                <div v-if="editor.error" class="alert alert-error">{{ editor.error }}</div>

                <div class="editor-body">
                    <div class="editor-grid">
                        <label>
                            <span class="form-label">Item ID hộp</span>
                            <input v-model.number="editor.form.item_id" class="form-input" type="number" min="0" :disabled="editor.isEdit" placeholder="Tự tăng nếu bỏ trống" />
                        </label>
                        <label>
                            <span class="form-label">Tên hộp</span>
                            <input v-model.trim="editor.form.name" class="form-input" required />
                        </label>
                        <label>
                            <span class="form-label">Type</span>
                            <input v-model.number="editor.form.type" class="form-input" type="number" />
                        </label>
                        <div>
                            <span class="form-label">Icon PNG x4{{ editor.isEdit ? "" : " *" }}</span>
                            <div
                                class="file-box"
                                @dragover.prevent="iconDrag = true"
                                @dragleave.prevent="iconDrag = false"
                                @drop.prevent="dropIconFile"
                            >
                                <input
                                    id="gift-box-icon-upload"
                                    class="file-input-hidden"
                                    type="file"
                                    accept="image/png"
                                    @change="onIconFile"
                                />
                                <label class="drop-box icon-drop-box" :class="{ dragging: iconDrag }" for="gift-box-icon-upload">
                                    <span class="mi">upload_file</span>
                                    <strong>Chọn icon hộp</strong>
                                    <small>{{ editor.iconFile ? editor.iconFile.name : "PNG x4, tự sinh icon_id" }}</small>
                                </label>
                            </div>
                            <small class="field-hint">
                                Icon ID tự lấy theo tên file ảnh nếu còn trống, nếu trùng sẽ tự chọn ID kế tiếp.
                            </small>
                        </div>
                        <label>
                            <span class="form-label">Ô trống tối thiểu</span>
                            <input v-model.number="editor.form.min_empty_slots" class="form-input" type="number" min="1" />
                        </label>
                    </div>

                    <label class="editor-description">
                        <span class="form-label">Mô tả</span>
                        <textarea v-model="editor.form.description" class="form-input" rows="3"></textarea>
                    </label>

                    <label>
                        <span class="form-label">Thông báo nhận quà</span>
                        <input v-model="editor.form.success_message" class="form-input" />
                    </label>

                    <div class="switch-row compact-switch-row">
                        <label><input v-model="editor.form.active" type="checkbox" /> Bật hộp quà</label>
                    </div>

                    <div class="rewards-card">
                        <div class="rewards-head">
                            <div>
                                <h3>
                                    <span class="mi">inventory_2</span>
                                    Vật phẩm
                                </h3>
                                <small>{{ editor.form.rewards.length }} vật phẩm · Tổng tỉ lệ item: {{ rewardPercentTotal }}%</small>
                            </div>
                            <button class="btn btn-outline btn-sm" type="button" @click="openItemPicker">
                                <span class="mi" style="font-size: 15px">list</span>
                                {{ itemPicker.open ? "Ẩn chọn item" : "Chọn item" }}
                            </button>
                        </div>

                        <div v-if="itemPicker.open" class="inline-picker">
                            <div class="picker-tools">
                                <div class="search-input-wrap">
                                    <span class="mi search-icon">search</span>
                                    <input
                                        v-model="itemPicker.search"
                                        class="form-input search-input picker-search"
                                        placeholder="Tìm vật phẩm theo tên hoặc ID..."
                                        @input="debouncedLoadItemPicker"
                                        @keyup.enter="loadItemPicker(1)"
                                    />
                                </div>
                                <select v-model="itemPicker.type" class="form-input" @change="loadItemPicker(1)">
                                    <option value="">Tất cả TYPE</option>
                                    <option v-for="type in itemPicker.typeOptions" :key="type.id" :value="String(type.id)">
                                        {{ type.name }} ({{ type.id }})
                                    </option>
                                </select>
                                <button class="btn btn-primary btn-sm" type="button" @click="loadItemPicker(1)">
                                    <span class="mi" style="font-size: 15px">search</span>
                                    Lọc
                                </button>
                            </div>
                            <div class="inline-picker-list">
                                <div v-if="itemPicker.loading" class="picker-empty">Đang tải item...</div>
                                <div v-else-if="!itemPicker.rows.length" class="picker-empty">Không tìm thấy item.</div>
                                <button
                                    v-else
                                    v-for="row in itemPicker.rows"
                                    :key="row.id"
                                    class="picker-item"
                                    type="button"
                                    @click="addReward(row)"
                                >
                                    <AdminIcon :icon-id="row.icon_id" />
                                    <div class="picker-item-info">
                                        <div class="picker-item-name">{{ row.name }}</div>
                                        <div class="picker-item-meta">ID: {{ row.id }} | TYPE: {{ row.type }}</div>
                                    </div>
                                    <span class="mi add-icon">add_circle</span>
                                </button>
                            </div>
                            <div class="picker-foot">
                                <span>{{ itemPicker.total.toLocaleString("vi-VN") }} item</span>
                                <div class="picker-pagination">
                                    <button class="btn btn-outline btn-sm" type="button" :disabled="itemPicker.page <= 1" @click="loadItemPicker(itemPicker.page - 1)">
                                        &laquo;
                                    </button>
                                    <template v-for="p in itemPickerPaginationItems" :key="String(p)">
                                        <span v-if="typeof p !== 'number'" class="picker-pagination-ellipsis">...</span>
                                        <button
                                            v-else
                                            class="btn btn-outline btn-sm"
                                            type="button"
                                            :class="{ active: p === itemPicker.page }"
                                            @click="loadItemPicker(p)"
                                        >
                                            {{ p }}
                                        </button>
                                    </template>
                                    <button class="btn btn-outline btn-sm" type="button" :disabled="itemPicker.page >= itemPicker.totalPages" @click="loadItemPicker(itemPicker.page + 1)">
                                        &raquo;
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="!editor.form.rewards.length" class="empty-rewards">Chưa có reward.</div>
                        <div v-else class="reward-table-wrap">
                            <table class="reward-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th></th>
                                        <th>Vật phẩm</th>
                                        <th>Số lượng</th>
                                        <th>Tỉ lệ item (%)</th>
                                        <th>Options</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(reward, index) in editor.form.rewards" :key="reward._local_id">
                                        <td class="td-idx">{{ index + 1 }}</td>
                                        <td><AdminIcon :icon-id="reward.icon_id" /></td>
                                        <td class="td-name">
                                            <div class="t-name">{{ reward.reward_name || "Item #" + reward.reward_item_id }}</div>
                                            <div class="t-id">ID: {{ reward.reward_item_id }}</div>
                                        </td>
                                        <td><input v-model.number="reward.quantity" class="form-input input-sm qty-input" type="number" min="1" max="999" /></td>
                                        <td><input v-model.number="reward.chance_percent" class="form-input input-sm percent-input" type="number" min="0" max="100" step="0.01" /></td>
                                        <td class="td-options">
                                            <div class="options-line">
                                                <span v-for="(opt, optIndex) in reward.options" :key="optIndex" class="option-chip">
                                                    {{ optionLabel(opt) }}
                                                    <button type="button" @click="reward.options.splice(optIndex, 1)">&times;</button>
                                                </span>
                                                <button
                                                    class="t-opt-add"
                                                    :class="{ active: reward._new_option_show }"
                                                    type="button"
                                                    title="Thêm option"
                                                    @click="reward._new_option_show = !reward._new_option_show"
                                                >
                                                    <span class="mi" style="font-size: 14px">add</span>
                                                </button>
                                                <button
                                                    class="t-opt-add group-toggle"
                                                    :class="{ active: reward._groups_open }"
                                                    type="button"
                                                    title="Nhóm random option"
                                                    @click="reward._groups_open = !reward._groups_open"
                                                >
                                                    <span class="mi" style="font-size: 14px">casino</span>
                                                </button>
                                            </div>
                                            <div v-if="reward._new_option_show" class="option-editor-line">
                                                <div class="option-select-wrap" :class="{ open: reward._new_option_dropdown }">
                                                    <input
                                                        v-model="reward._new_option_search"
                                                        class="form-input input-sm option-select"
                                                        placeholder="Tìm option..."
                                                        autocomplete="off"
                                                        @focus="reward._new_option_dropdown = true"
                                                        @input="reward._new_option_dropdown = true"
                                                    />
                                                    <div v-if="reward._new_option_dropdown" class="option-dropdown">
                                                        <button
                                                            v-for="opt in filteredOptions(reward._new_option_search)"
                                                            :key="opt.id"
                                                            type="button"
                                                            class="option-dropdown-item"
                                                            @mousedown.prevent="selectNewOption(reward, opt)"
                                                        >
                                                            <span class="opt-id">{{ opt.id }}</span>
                                                            <span>{{ opt.name }}</span>
                                                        </button>
                                                        <div v-if="!filteredOptions(reward._new_option_search).length" class="option-dropdown-empty">
                                                            Không tìm thấy option
                                                        </div>
                                                    </div>
                                                </div>
                                                <input v-model.number="reward._new_param" class="form-input input-sm param-input" type="number" placeholder="Param" />
                                                <button class="t-opt-confirm" type="button" title="Xác nhận" @mousedown.prevent="addOption(reward)">
                                                    <span class="mi" style="font-size: 16px">check</span>
                                                </button>
                                                <button class="t-opt-cancel" type="button" title="Hủy chọn option" @mousedown.prevent="cancelNewOption(reward)">
                                                    <span class="mi" style="font-size: 16px">close</span>
                                                </button>
                                            </div>
                                            <div v-if="reward.option_groups.length" class="option-groups-summary">
                                                <span v-for="(group, groupIndex) in reward.option_groups" :key="group._local_id" class="group-pill" :class="{ warn: groupPercentTotal(group) !== 100 }">
                                                    <span>{{ groupLabel(group) }} · {{ group.entries.length }} lựa chọn · {{ groupPercentTotal(group) }}%</span>
                                                    <button class="group-pill-remove" type="button" title="Xóa nhóm option" @click.stop="removeOptionGroup(reward, groupIndex)">
                                                        <span class="mi">close</span>
                                                    </button>
                                                </span>
                                            </div>
                                            <div v-if="reward._groups_open" class="option-groups-editor">
                                                <div class="group-toolbar">
                                                    <button class="btn btn-outline btn-sm" type="button" @click="addHsdGroup(reward)">
                                                        <span class="mi" style="font-size: 14px">schedule</span>
                                                        HSD
                                                    </button>
                                                    <button class="btn btn-outline btn-sm" type="button" @click="addComboGroup(reward)">
                                                        <span class="mi" style="font-size: 14px">auto_awesome</span>
                                                        Combo option
                                                    </button>
                                                    <button class="btn btn-outline btn-sm group-close-btn" type="button" @click="reward._groups_open = false">
                                                        <span class="mi" style="font-size: 14px">close</span>
                                                        Đóng
                                                    </button>
                                                </div>
                                                <div v-if="!reward.option_groups.length" class="group-empty">Chưa có nhóm random option.</div>
                                                <div v-for="(group, groupIndex) in reward.option_groups" :key="group._local_id" class="option-group-card">
                                                    <div class="option-group-head">
                                                        <strong>{{ groupLabel(group) }}</strong>
                                                        <span class="group-weight" :class="{ ok: groupPercentTotal(group) === 100, warn: groupPercentTotal(group) !== 100 }">
                                                            Tổng: {{ groupPercentTotal(group) }}%
                                                        </span>
                                                        <button class="icon-action small danger" type="button" title="Xóa nhóm" @click="reward.option_groups.splice(groupIndex, 1)">
                                                            <span class="mi">delete</span>
                                                        </button>
                                                    </div>
                                                    <div v-if="isHsdGroup(group)" class="hsd-entry-list">
                                                        <div v-for="(entry, entryIndex) in group.entries" :key="entry._local_id" class="hsd-entry-row">
                                                            <input
                                                                v-model.trim="entry.hsd_value"
                                                                class="form-input input-sm hsd-day-input"
                                                                placeholder="1, 3, 5 hoặc vv"
                                                                @input="syncHsdEntry(entry)"
                                                            />
                                                            <input v-model.number="entry.chance_weight" class="form-input input-sm entry-percent-input" type="number" min="0" max="100" step="0.01" placeholder="%" />
                                                            <button class="icon-action small danger" type="button" title="Xóa lựa chọn" @click="group.entries.splice(entryIndex, 1)">
                                                                <span class="mi">close</span>
                                                            </button>
                                                        </div>
                                                        <button class="btn btn-outline btn-sm group-add-entry" type="button" @click="addHsdEntry(group)">
                                                            <span class="mi" style="font-size: 14px">add</span>
                                                            Thêm mốc ngày
                                                        </button>
                                                    </div>
                                                    <div v-else class="group-entry-list">
                                                        <div v-for="(entry, entryIndex) in group.entries" :key="entry._local_id" class="group-entry-row">
                                                            <input v-model.trim="entry.label" class="form-input input-sm entry-label-input" placeholder="Nhãn" />
                                                            <input v-model.number="entry.chance_weight" class="form-input input-sm entry-percent-input" type="number" min="0" max="100" step="0.01" title="Tỉ lệ %" />
                                                            <div class="entry-options">
                                                                <span v-for="(opt, optIndex) in entry.options" :key="optIndex" class="option-chip">
                                                                    {{ optionLabel(opt) }}
                                                                    <button type="button" @click="entry.options.splice(optIndex, 1)">&times;</button>
                                                                </span>
                                                                <div class="entry-option-editor">
                                                                    <div class="option-select-wrap" :class="{ open: entry._new_option_dropdown }">
                                                                        <input
                                                                            v-model="entry._new_option_search"
                                                                            class="form-input input-sm entry-option-search"
                                                                            placeholder="Option..."
                                                                            autocomplete="off"
                                                                            @focus="entry._new_option_dropdown = true"
                                                                            @input="entry._new_option_dropdown = true"
                                                                        />
                                                                        <div v-if="entry._new_option_dropdown" class="option-dropdown">
                                                                            <button
                                                                                v-for="opt in filteredOptions(entry._new_option_search)"
                                                                                :key="opt.id"
                                                                                type="button"
                                                                                class="option-dropdown-item"
                                                                                @mousedown.prevent="selectEntryOption(entry, opt)"
                                                                            >
                                                                                <span class="opt-id">{{ opt.id }}</span>
                                                                                <span>{{ opt.name }}</span>
                                                                            </button>
                                                                            <div v-if="!filteredOptions(entry._new_option_search).length" class="option-dropdown-empty">
                                                                                Không tìm thấy option
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <input v-model.number="entry._new_param" class="form-input input-sm entry-param-input" type="number" placeholder="Param" />
                                                                    <button class="t-opt-confirm" type="button" title="Thêm option vào lựa chọn" @mousedown.prevent="addEntryOption(entry)">
                                                                        <span class="mi" style="font-size: 16px">check</span>
                                                                    </button>
                                                                    <button class="t-opt-cancel" type="button" title="Hủy chọn option" @mousedown.prevent="cancelEntryOption(entry)">
                                                                        <span class="mi" style="font-size: 16px">close</span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <button class="icon-action small danger" type="button" title="Xóa lựa chọn" @click="group.entries.splice(entryIndex, 1)">
                                                                <span class="mi">close</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <button v-if="!isHsdGroup(group)" class="btn btn-outline btn-sm group-add-entry" type="button" @click="addGroupEntry(group)">
                                                        <span class="mi" style="font-size: 14px">add</span>
                                                        Thêm lựa chọn
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="td-act">
                                            <button class="icon-action small danger" type="button" title="Xóa reward" @click="editor.form.rewards.splice(index, 1)">
                                                <span class="mi">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="editor-actions">
                    <button class="btn btn-outline" type="button" :disabled="editor.saving" @click="closeEditor">Hủy</button>
                    <button class="btn btn-primary" type="button" :disabled="editor.saving" @click="saveEditor">
                        <span class="mi" style="font-size: 16px">save</span>
                        {{ editor.saving ? "Đang lưu..." : "Lưu" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { buildPaginationItems, csrfToken } from "../../shared/format";
import { readJsonResponse } from "../../shared/api";

export default {
    data() {
        return {
            boxes: [],
            options: [],
            search: "",
            searchTimer: null,
            loading: false,
            error: "",
            success: "",
            page: 1,
            totalPages: 1,
            iconDrag: false,
            itemPickerTimer: null,
            itemPicker: {
                open: false,
                loading: false,
                search: "",
                type: "",
                rows: [],
                typeOptions: [],
                page: 1,
                total: 0,
                totalPages: 1,
            },
            editor: this.emptyEditor(),
        };
    },
    computed: {
        paginationItems() {
            return buildPaginationItems(this.page, this.totalPages);
        },
        rewardPercentTotal() {
            return this.roundPercent(this.editor.form.rewards.reduce((sum, item) => sum + Number(item.chance_percent || 0), 0));
        },
        itemPickerPaginationItems() {
            return buildPaginationItems(this.itemPicker.page, this.itemPicker.totalPages);
        },
    },
    created() {
        this.loadOptions();
        this.loadPage(1);
    },
    mounted() {
        document.addEventListener("click", this.handleDocumentClick);
        document.addEventListener("keydown", this.handleDocumentKeydown);
    },
    unmounted() {
        document.removeEventListener("click", this.handleDocumentClick);
        document.removeEventListener("keydown", this.handleDocumentKeydown);
        window.clearTimeout(this.searchTimer);
        window.clearTimeout(this.itemPickerTimer);
    },
    methods: {
        emptyEditor() {
            return {
                open: false,
                isEdit: false,
                saving: false,
                error: "",
                iconFile: null,
                id: null,
                form: {
                    item_id: "",
                    name: "",
                    description: "",
                    type: 27,
                    part: 0,
                    gender: 3,
                    icon_id: 0,
                    active: true,
                    can_trade: true,
                    is_up_to_up: true,
                    min_empty_slots: 1,
                    success_message: "Bạn mở rương nhận được {item}",
                    rewards: [],
                },
            };
        },
        debouncedLoadPage() {
            window.clearTimeout(this.searchTimer);
            this.searchTimer = window.setTimeout(() => this.loadPage(1), 300);
        },
        async loadPage(page) {
            this.loading = true;
            this.error = "";
            try {
                const params = new URLSearchParams({ page: String(Math.max(1, page)) });
                if (this.search) params.set("search", this.search);
                const res = await fetch(`/admin/api/gift-boxes?${params.toString()}`, { headers: { "X-Requested-With": "XMLHttpRequest" } });
                const data = await readJsonResponse(res, "Không thể tải hộp quà");
                this.boxes = data.data || [];
                this.page = data.page || 1;
                this.totalPages = data.total_pages || 1;
            } catch (e) {
                this.error = e?.message || "Không thể tải hộp quà";
                this.boxes = [];
            } finally {
                this.loading = false;
            }
        },
        async loadOptions() {
            try {
                const res = await fetch("/admin/api/options", { headers: { "X-Requested-With": "XMLHttpRequest" } });
                this.options = await readJsonResponse(res, "Không thể tải option");
            } catch {
                this.options = [];
            }
        },
        openCreate() {
            this.editor = this.emptyEditor();
            this.editor.open = true;
        },
        async openEdit(id) {
            this.editor = this.emptyEditor();
            this.editor.open = true;
            this.editor.isEdit = true;
            this.editor.id = id;
            try {
                const res = await fetch(`/admin/api/gift-boxes/${id}`, { headers: { "X-Requested-With": "XMLHttpRequest" } });
                const data = await readJsonResponse(res, "Không thể tải hộp quà");
                const box = data.data || {};
                const rewards = (data.rewards || []).map(this.decorateReward);
                this.normalizeRewardPercents(rewards);
                this.editor.form = {
                    item_id: box.item_id,
                    name: box.name || box.item_name || "",
                    description: box.description || box.item_description || "",
                    type: Number(box.item_type ?? 27),
                    part: Number(box.part ?? 0),
                    gender: Number(box.gender ?? 3),
                    icon_id: Number(box.icon_id ?? 0),
                    active: !!box.active,
                    can_trade: true,
                    is_up_to_up: true,
                    min_empty_slots: Number(box.min_empty_slots ?? 1),
                    success_message: box.success_message || "Bạn mở rương nhận được {item}",
                    rewards,
                };
            } catch (e) {
                this.editor.error = e?.message || "Không thể tải hộp quà";
            }
        },
        closeEditor() {
            if (this.editor.saving) return;
            this.editor.open = false;
        },
        onIconFile(event) {
            this.editor.iconFile = event.target.files?.[0] || null;
        },
        dropIconFile(event) {
            this.iconDrag = false;
            const file = Array.from(event.dataTransfer?.files || []).find((item) => item.type === "image/png" || item.name.toLowerCase().endsWith(".png"));
            if (file) {
                this.editor.iconFile = file;
            }
        },
        decorateReward(reward) {
            return {
                _local_id: `${Date.now()}-${Math.random()}`,
                reward_item_id: Number(reward.reward_item_id),
                reward_name: reward.reward_name || reward.name || "",
                icon_id: Number(reward.icon_id ?? 0),
                quantity_min: Number(reward.quantity_min ?? 1),
                quantity_max: Number(reward.quantity_max ?? reward.quantity_min ?? 1),
                quantity: Number(reward.quantity ?? reward.quantity_min ?? 1),
                chance_weight: Number(reward.chance_weight ?? 1),
                chance_percent: Number(reward.chance_percent ?? reward.chance_weight ?? 1),
                options: Array.isArray(reward.options) ? reward.options : [],
                option_groups: (Array.isArray(reward.option_groups) ? reward.option_groups : []).map((group) => this.decorateOptionGroup(group)),
                _new_option_id: null,
                _new_option_search: "",
                _new_option_show: false,
                _new_option_dropdown: false,
                _new_param: 0,
                _groups_open: false,
            };
        },
        decorateOptionGroup(group) {
            const entries = (Array.isArray(group.entries) ? group.entries : []).map((entry) => this.decorateOptionEntry(entry));
            this.normalizeGroupPercents(entries);
            return {
                _local_id: `${Date.now()}-${Math.random()}`,
                name: group.name || "Nhóm option",
                kind: group.kind || (String(group.name || "").toLowerCase().includes("hạn") ? "hsd" : "option"),
                entries,
            };
        },
        decorateOptionEntry(entry = {}) {
            return {
                _local_id: `${Date.now()}-${Math.random()}`,
                label: entry.label || "",
                chance_weight: Number(entry.chance_weight ?? entry.weight ?? 1),
                options: Array.isArray(entry.options) ? entry.options : [],
                hsd_value: entry.hsd_value || this.hsdValueFromEntry(entry),
                _new_option_id: null,
                _new_option_search: "",
                _new_option_dropdown: false,
                _new_param: 0,
            };
        },
        openItemPicker() {
            this.itemPicker.open = !this.itemPicker.open;
            if (this.itemPicker.open && !this.itemPicker.rows.length) {
                this.loadItemPicker(1);
            }
        },
        debouncedLoadItemPicker() {
            window.clearTimeout(this.itemPickerTimer);
            this.itemPickerTimer = window.setTimeout(() => this.loadItemPicker(1), 300);
        },
        async loadItemPicker(page = 1) {
            this.itemPicker.loading = true;
            try {
                const params = new URLSearchParams({
                    page: String(Math.max(1, Number(page) || 1)),
                    per_page: "40",
                    lite: "1",
                });
                if (this.itemPicker.search.trim()) {
                    params.set("search", this.itemPicker.search.trim());
                }
                if (this.itemPicker.type) {
                    params.set("type", this.itemPicker.type);
                }

                const res = await fetch(`/admin/api/items?${params.toString()}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(res, "Không thể tải danh sách item");
                this.itemPicker.rows = data.data || [];
                this.itemPicker.typeOptions = data.type_options || this.itemPicker.typeOptions || [];
                this.itemPicker.page = data.page || 1;
                this.itemPicker.total = data.total || 0;
                this.itemPicker.totalPages = data.total_pages || 1;
            } catch (e) {
                this.editor.error = e?.message || "Không thể tải danh sách item";
                this.itemPicker.rows = [];
            } finally {
                this.itemPicker.loading = false;
            }
        },
        addReward(item) {
            const remaining = Math.max(0, this.roundPercent(100 - this.rewardPercentTotal));
            this.editor.form.rewards.push(this.decorateReward({
                reward_item_id: item.id,
                reward_name: item.name,
                icon_id: item.icon_id,
                quantity_min: 1,
                quantity_max: 1,
                quantity: 1,
                chance_weight: remaining || 1,
                chance_percent: remaining || 1,
                options: [{ id: 30, param_min: 1, param_max: 1 }],
            }));
        },
        filteredOptions(search) {
            const keyword = String(search || "").trim().toLowerCase();
            const rows = Array.isArray(this.options) ? this.options : [];
            if (!keyword) return rows.slice(0, 40);
            return rows
                .filter((option) => String(option.id).includes(keyword) || String(option.name || "").toLowerCase().includes(keyword))
                .slice(0, 40);
        },
        selectNewOption(reward, option) {
            reward._new_option_id = Number(option.id);
            reward._new_option_search = `${option.id} - ${option.name}`;
            reward._new_option_dropdown = false;
        },
        addOption(reward) {
            if (reward._new_option_id === null || reward._new_option_id === undefined) return;
            const param = Number(reward._new_param || 0);
            reward.options.push({ id: Number(reward._new_option_id), param_min: param, param_max: param });
            this.cancelNewOption(reward);
        },
        cancelNewOption(reward) {
            reward._new_option_id = null;
            reward._new_option_search = "";
            reward._new_option_show = false;
            reward._new_option_dropdown = false;
            reward._new_param = 0;
        },
        addOptionGroup(reward) {
            reward.option_groups.push(this.decorateOptionGroup({
                name: "Nhóm option",
                kind: "option",
                entries: [
                    { label: "Lựa chọn 1", chance_weight: 100, options: [] },
                ],
            }));
            reward._groups_open = true;
        },
        addHsdGroup(reward) {
            const existing = reward.option_groups.find((group) => this.isHsdGroup(group));
            if (existing) {
                reward._groups_open = true;
                return;
            }
            reward.option_groups.push(this.decorateOptionGroup({
                name: "Hạn sử dụng",
                kind: "hsd",
                entries: [
                    { label: "1 ngày", hsd_value: "1", chance_weight: 35, options: [{ id: 93, param_min: 1, param_max: 1 }] },
                    { label: "3 ngày", hsd_value: "3", chance_weight: 25, options: [{ id: 93, param_min: 3, param_max: 3 }] },
                    { label: "5 ngày", hsd_value: "5", chance_weight: 18, options: [{ id: 93, param_min: 5, param_max: 5 }] },
                    { label: "7 ngày", hsd_value: "7", chance_weight: 12, options: [{ id: 93, param_min: 7, param_max: 7 }] },
                    { label: "15 ngày", hsd_value: "15", chance_weight: 8, options: [{ id: 93, param_min: 15, param_max: 15 }] },
                    { label: "Vĩnh viễn", hsd_value: "vv", chance_weight: 2, options: [] },
                ],
            }));
            reward._groups_open = true;
        },
        addComboGroup(reward) {
            reward.option_groups.push(this.decorateOptionGroup({
                name: "Combo chỉ số",
                kind: "option",
                entries: [
                    { label: "Combo 1", chance_weight: 34, options: [{ id: 50, param_min: 1, param_max: 1 }, { id: 0, param_min: 500, param_max: 500 }] },
                    { label: "Combo 2", chance_weight: 33, options: [{ id: 77, param_min: 1, param_max: 1 }, { id: 6, param_min: 5000, param_max: 5000 }] },
                    { label: "Combo 3", chance_weight: 33, options: [{ id: 103, param_min: 1, param_max: 1 }, { id: 7, param_min: 5000, param_max: 5000 }] },
                ],
            }));
            reward._groups_open = true;
        },
        removeOptionGroup(reward, groupIndex) {
            reward.option_groups.splice(groupIndex, 1);
        },
        addGroupEntry(group) {
            group.entries.push(this.decorateOptionEntry({
                label: `Lựa chọn ${group.entries.length + 1}`,
                chance_weight: Math.max(0, this.roundPercent(100 - this.groupPercentTotal(group))) || 1,
                options: [],
            }));
        },
        addHsdEntry(group) {
            group.entries.push(this.decorateOptionEntry({
                label: "Vĩnh viễn",
                hsd_value: "vv",
                chance_weight: Math.max(0, this.roundPercent(100 - this.groupPercentTotal(group))) || 1,
                options: [],
            }));
        },
        groupPercentTotal(group) {
            return this.roundPercent((group.entries || []).reduce((sum, entry) => sum + Number(entry.chance_weight || 0), 0));
        },
        selectEntryOption(entry, option) {
            entry._new_option_id = Number(option.id);
            entry._new_option_search = `${option.id} - ${option.name}`;
            entry._new_option_dropdown = false;
        },
        addEntryOption(entry) {
            if (entry._new_option_id === null || entry._new_option_id === undefined) return;
            const param = Number(entry._new_param || 0);
            entry.options.push({ id: Number(entry._new_option_id), param_min: param, param_max: param });
            this.cancelEntryOption(entry);
        },
        cancelEntryOption(entry) {
            entry._new_option_id = null;
            entry._new_option_search = "";
            entry._new_option_dropdown = false;
            entry._new_param = 0;
        },
        cleanOptions(options) {
            return (Array.isArray(options) ? options : []).map((option) => ({
                id: Number(option.id),
                param_min: Number(option.param_min ?? option.param ?? 0),
                param_max: Number(option.param_max ?? option.param_min ?? option.param ?? 0),
            })).filter((option) => Number.isFinite(option.id) && option.id >= 0);
        },
        cleanOptionGroups(groups) {
            return (Array.isArray(groups) ? groups : []).map((group) => ({
                name: group.name || "Nhóm option",
                kind: group.kind || (this.isHsdGroup(group) ? "hsd" : "option"),
                entries: (Array.isArray(group.entries) ? group.entries : []).map((entry) => ({
                    label: this.isHsdGroup(group) ? this.hsdEntryLabel(entry) : (entry.label || ""),
                    hsd_value: this.isHsdGroup(group) ? this.normalizeHsdValue(entry.hsd_value) : undefined,
                    chance_weight: Math.max(0, Math.round(this.roundPercent(Number(entry.chance_weight || 0)) * 100)),
                    options: this.isHsdGroup(group) ? this.hsdOptions(entry) : this.cleanOptions(entry.options),
                })).filter((entry) => entry.chance_weight > 0 || entry.options.length || entry.label),
            })).filter((group) => group.entries.length);
        },
        isHsdGroup(group) {
            return group?.kind === "hsd" || String(group?.name || "").toLowerCase().includes("hạn");
        },
        groupLabel(group) {
            return this.isHsdGroup(group) ? "Hạn sử dụng" : (group?.name || "Nhóm option");
        },
        hsdValueFromEntry(entry) {
            const option = (entry.options || []).find((item) => Number(item.id) === 93);
            if (!option) return "vv";
            return String(option.param_min ?? option.param ?? "");
        },
        normalizeHsdValue(value) {
            const raw = String(value || "").trim().toLowerCase();
            if (!raw || ["vv", "vĩnh viễn", "vinh vien", "permanent"].includes(raw)) {
                return "vv";
            }
            const number = Number(raw.replace(/[^\d]/g, ""));
            return Number.isFinite(number) && number > 0 ? String(number) : "vv";
        },
        hsdOptions(entry) {
            const value = this.normalizeHsdValue(entry.hsd_value);
            if (value === "vv") return [];
            const days = Number(value);
            return [{ id: 93, param_min: days, param_max: days }];
        },
        hsdEntryLabel(entry) {
            const value = this.normalizeHsdValue(entry.hsd_value);
            return value === "vv" ? "Vĩnh viễn" : `${value} ngày`;
        },
        syncHsdEntry(entry) {
            entry.hsd_value = this.normalizeHsdValue(entry.hsd_value);
            entry.label = this.hsdEntryLabel(entry);
            entry.options = this.hsdOptions(entry);
        },
        roundPercent(value) {
            const number = Number(value || 0);
            return Math.round(number * 100) / 100;
        },
        normalizeRewardPercents(rewards) {
            const total = rewards.reduce((sum, reward) => sum + Math.max(0, Number(reward.chance_weight || 0)), 0);
            if (!total) return;
            rewards.forEach((reward) => {
                reward.chance_percent = this.roundPercent((Math.max(0, Number(reward.chance_weight || 0)) / total) * 100);
            });
            const diff = this.roundPercent(100 - rewards.reduce((sum, reward) => sum + Number(reward.chance_percent || 0), 0));
            if (rewards.length && diff !== 0) {
                rewards[rewards.length - 1].chance_percent = this.roundPercent(Number(rewards[rewards.length - 1].chance_percent || 0) + diff);
            }
        },
        normalizeGroupPercents(entries) {
            const total = entries.reduce((sum, entry) => sum + Math.max(0, Number(entry.chance_weight || 0)), 0);
            if (!total || total === 100) return;
            entries.forEach((entry) => {
                entry.chance_weight = this.roundPercent((Math.max(0, Number(entry.chance_weight || 0)) / total) * 100);
            });
            const diff = this.roundPercent(100 - entries.reduce((sum, entry) => sum + Number(entry.chance_weight || 0), 0));
            if (entries.length && diff !== 0) {
                entries[entries.length - 1].chance_weight = this.roundPercent(Number(entries[entries.length - 1].chance_weight || 0) + diff);
            }
        },
        validatePercents() {
            if (this.rewardPercentTotal !== 100) {
                return `Tổng tỉ lệ item phải bằng 100%, hiện tại là ${this.rewardPercentTotal}%.`;
            }
            for (const reward of this.editor.form.rewards) {
                for (const group of reward.option_groups || []) {
                    const total = this.groupPercentTotal(group);
                    if (total !== 100) {
                        return `Nhóm "${this.groupLabel(group)}" của ${reward.reward_name || "item #" + reward.reward_item_id} phải đủ 100%, hiện tại là ${total}%.`;
                    }
                }
            }
            return "";
        },
        handleDocumentClick(event) {
            if (event.target.closest(".option-select-wrap")) return;
            this.closeOptionDropdowns();
            if (event.target.closest(".option-groups-editor") || event.target.closest(".group-toggle")) return;
            this.closeGroupEditors();
        },
        handleDocumentKeydown(event) {
            if (event.key !== "Escape") return;
            this.closeOptionDropdowns();
            this.closeGroupEditors();
        },
        closeOptionDropdowns() {
            this.editor.form.rewards.forEach((reward) => {
                reward._new_option_dropdown = false;
                (reward.option_groups || []).forEach((group) => {
                    (group.entries || []).forEach((entry) => {
                        entry._new_option_dropdown = false;
                    });
                });
            });
        },
        closeGroupEditors() {
            this.editor.form.rewards.forEach((reward) => {
                reward._groups_open = false;
            });
        },
        optionLabel(option) {
            const found = this.options.find((item) => Number(item.id) === Number(option.id));
            const name = found ? found.name : `Option ${option.id}`;
            const min = Number(option.param_min ?? option.param ?? 0);
            const max = Number(option.param_max ?? min);
            const param = min === max ? String(min) : `${min}-${max}`;
            return String(name).includes("#") ? String(name).replace("#", param) : `${name}: ${param}`;
        },
        async saveEditor() {
            if (!this.editor.form.name.trim()) {
                this.editor.error = "Tên hộp không được để trống.";
                return;
            }
            if (!this.editor.form.rewards.length) {
                this.editor.error = "Hộp quà cần ít nhất 1 reward.";
                return;
            }
            const percentError = this.validatePercents();
            if (percentError) {
                this.editor.error = percentError;
                return;
            }
            if (!this.editor.isEdit && !this.editor.iconFile) {
                this.editor.error = "Vui lòng chọn icon PNG x4 để hệ thống tự sinh icon_id.";
                return;
            }

            this.editor.saving = true;
            this.editor.error = "";
            try {
                const formData = new FormData();
                const payload = { ...this.editor.form };
                payload.rewards = payload.rewards.map((reward, index) => ({
                    reward_item_id: reward.reward_item_id,
                    quantity_min: Math.max(1, Math.min(999, Number(reward.quantity || 1))),
                    quantity_max: Math.max(1, Math.min(999, Number(reward.quantity || 1))),
                    chance_weight: Math.max(1, Math.round(this.roundPercent(Number(reward.chance_percent || 0)) * 100)),
                    options: this.cleanOptions(reward.options),
                    option_groups: this.cleanOptionGroups(reward.option_groups),
                    sort_order: index,
                }));
                Object.entries(payload).forEach(([key, value]) => {
                    if (!this.editor.isEdit && key === "icon_id") return;
                    formData.append(key, key === "rewards" ? JSON.stringify(value) : String(value ?? ""));
                });
                if (this.editor.iconFile) {
                    formData.append("icon_x4", this.editor.iconFile);
                }
                ["active", "can_trade", "is_up_to_up"].forEach((key) => {
                    if (Object.prototype.hasOwnProperty.call(payload, key)) {
                        formData.set(key, payload[key] ? "1" : "0");
                    }
                });

                const url = this.editor.isEdit ? `/admin/api/gift-boxes/${this.editor.id}` : "/admin/api/gift-boxes";
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": csrfToken(),
                    },
                    body: formData,
                });
                const data = await readJsonResponse(res, "Không thể lưu hộp quà");
                if (!data.ok) throw new Error(data.message || "Không thể lưu hộp quà");
                this.success = data.message || "Đã lưu hộp quà";
                this.closeEditor();
                await this.loadPage(this.page);
            } catch (e) {
                this.editor.error = e?.message || "Không thể lưu hộp quà";
            } finally {
                this.editor.saving = false;
            }
        },
        async deleteBox(box) {
            if (!confirm(`Xóa cấu hình hộp quà "${box.name}"? Item template #${box.item_id} vẫn được giữ lại.`)) return;
            try {
                const res = await fetch(`/admin/api/gift-boxes/${box.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": csrfToken(),
                    },
                });
                const data = await readJsonResponse(res, "Không thể xóa hộp quà");
                this.success = data.message || "Đã xóa hộp quà";
                await this.loadPage(this.page);
            } catch (e) {
                this.error = e?.message || "Không thể xóa hộp quà";
            }
        },
    },
};
</script>

<style scoped>
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.page-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 4px;
}
.breadcrumb,
.search-form,
.switch-row,
.reward-main,
.options-line,
.rewards-head {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.breadcrumb {
    font-size: 13px;
}
.breadcrumb a,
.breadcrumb .sep,
.item-meta,
.rewards-head small,
.reward-name small {
    color: var(--ds-text-muted);
}
.filter-bar {
    margin-bottom: 20px;
}
.search-input-wrap {
    position: relative;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    font-size: 18px;
    pointer-events: none;
}
.search-input {
    padding-left: 38px !important;
    width: 280px;
}
.box-icon {
    width: 36px;
    height: 36px;
}
.field-hint {
    display: block;
    margin-top: 5px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.file-box {
    min-width: 0;
}
.file-input-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
.drop-box {
    min-height: 88px;
    border: 1px dashed var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 12px;
    text-align: center;
    cursor: pointer;
    transition:
        border-color 0.16s ease,
        background-color 0.16s ease,
        color 0.16s ease,
        transform 0.16s ease,
        box-shadow 0.16s ease;
}
.drop-box:hover,
.drop-box.dragging,
.file-input-hidden:focus-visible + .drop-box {
    border-color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.08);
    transform: translateY(-1px);
    box-shadow: var(--ds-shadow-sm);
}
.drop-box:active {
    transform: translateY(0);
    box-shadow: none;
}
.drop-box .mi {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: rgba(var(--ds-primary-rgb), 0.12);
    color: var(--ds-primary);
    font-size: 22px;
    transition:
        background-color 0.16s ease,
        transform 0.16s ease;
}
.drop-box:hover .mi,
.drop-box.dragging .mi {
    background: rgba(var(--ds-primary-rgb), 0.18);
    transform: scale(1.04);
}
.drop-box strong {
    color: var(--ds-text-emphasis);
    font-size: 13px;
}
.drop-box small {
    max-width: 100%;
    overflow: hidden;
    color: var(--ds-text-muted);
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.icon-preview-line {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 38px;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.item-name {
    font-weight: 600;
    color: var(--ds-text-emphasis);
}
.badge-muted {
    background: rgba(148, 163, 184, 0.14);
    color: var(--ds-text-muted);
    border: 1px solid rgba(148, 163, 184, 0.22);
}
.action-cell {
    text-align: right;
    white-space: nowrap;
}
.empty-cell,
.empty-rewards {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 28px;
}
.pagination-ellipsis {
    color: var(--ds-text-muted);
}
.editor-overlay {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(4, 8, 12, 0.66);
    backdrop-filter: blur(4px);
}
.editor-panel {
    width: min(1240px, calc(100vw - 32px));
    max-height: calc(100vh - 40px);
    overflow: auto;
    border: 1px solid var(--ds-border);
    border-radius: 14px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-lg, 0 24px 70px rgba(0, 0, 0, 0.45));
    padding: 18px;
}
.editor-head,
.editor-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.editor-head {
    margin-bottom: 14px;
}
.editor-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.editor-head p {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.icon-action {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
    display: inline-grid;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-family: inherit;
    line-height: 1;
    transition:
        background-color 0.16s ease,
        border-color 0.16s ease,
        color 0.16s ease,
        transform 0.16s ease,
        box-shadow 0.16s ease;
}
.icon-action.small {
    width: 30px;
    height: 30px;
    min-width: 30px;
}
.icon-action .mi {
    font-size: 20px;
}
.icon-action.small .mi {
    font-size: 17px;
}
.icon-action:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.35);
    background: var(--ds-gray-100);
    color: var(--ds-text-emphasis);
    transform: translateY(-1px);
    box-shadow: var(--ds-shadow-sm);
}
.icon-action:active {
    transform: translateY(0);
    box-shadow: none;
}
.icon-action:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.18);
}
.icon-action.danger:hover {
    border-color: rgba(var(--ds-danger-rgb), 0.55);
    background: rgba(var(--ds-danger-rgb), 0.1);
    color: var(--ds-danger);
}
.editor-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.editor-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.form-label {
    display: block;
    color: var(--ds-text-muted);
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
}
.editor-description textarea {
    min-height: 82px;
    resize: vertical;
}
.switch-row label {
    color: var(--ds-text);
    font-size: 13px;
}
.rewards-card {
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    padding: 12px;
    background: var(--ds-surface-2);
}
.rewards-head {
    justify-content: space-between;
    margin-bottom: 12px;
}
.rewards-head h3 {
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 0 0 4px;
    color: var(--ds-text-emphasis);
    font-size: 15px;
}
.rewards-head h3 .mi {
    color: var(--ds-info);
    font-size: 18px;
}
.rewards-head strong,
.rewards-head small,
.reward-name strong,
.reward-name small {
    display: block;
}
.inline-picker {
    margin-bottom: 12px;
    overflow: hidden;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-body-bg);
}
.picker-tools {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) 180px auto;
    gap: 10px;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid var(--ds-border);
}
.picker-search {
    width: 100%;
}
.inline-picker-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 6px;
    max-height: 280px;
    overflow: auto;
    padding: 8px;
}
.picker-item {
    width: 100%;
    min-height: 52px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: var(--ds-text);
    padding: 8px;
    cursor: pointer;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 9px;
    transition:
        border-color 0.16s ease,
        background-color 0.16s ease,
        transform 0.16s ease,
        box-shadow 0.16s ease;
}
.picker-item:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.3);
    background: rgba(var(--ds-primary-rgb), 0.08);
    transform: translateY(-1px);
    box-shadow: var(--ds-shadow-sm);
}
.picker-item:active {
    transform: translateY(0);
    box-shadow: none;
}
.picker-item:focus-visible {
    outline: none;
    border-color: var(--ds-primary);
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.18);
}
.picker-item-info {
    flex: 1;
    min-width: 0;
}
.picker-item-name {
    overflow: hidden;
    color: var(--ds-text-emphasis);
    font-size: 13px;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.picker-item-meta {
    margin-top: 2px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.picker-empty {
    grid-column: 1 / -1;
    min-height: 92px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.picker-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 10px;
    border-top: 1px solid var(--ds-border);
    color: var(--ds-text-muted);
    font-size: 12px;
}
.picker-pagination {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    flex-wrap: wrap;
}
.picker-pagination-ellipsis {
    color: var(--ds-text-muted);
}
.add-icon {
    color: var(--ds-primary);
    font-size: 20px;
    transition: transform 0.16s ease;
}
.picker-item:hover .add-icon {
    transform: scale(1.12);
}
.reward-table-wrap {
    overflow: visible;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-body-bg);
}
.reward-table {
    width: 100%;
    min-width: 760px;
    border-collapse: collapse;
}
.reward-table th {
    padding: 9px 10px;
    border-bottom: 1px solid var(--ds-border);
    color: var(--ds-text-muted);
    font-size: 11px;
    font-weight: 800;
    text-align: left;
    text-transform: uppercase;
}
.reward-table td {
    padding: 10px;
    border-bottom: 1px solid var(--ds-border);
    vertical-align: top;
}
.reward-table tr:last-child td {
    border-bottom: 0;
}
.td-idx {
    width: 42px;
    color: var(--ds-text-muted);
}
.td-name {
    min-width: 190px;
}
.t-name {
    color: var(--ds-text-emphasis);
    font-weight: 700;
}
.t-id {
    margin-top: 2px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.td-options {
    min-width: 360px;
    position: relative;
}
.td-act {
    width: 46px;
    text-align: right;
}
.qty-input {
    width: 76px !important;
}
.percent-input {
    width: 88px !important;
}
.options-line {
    align-items: center;
    margin-top: 0;
}
.option-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 999px;
    background: rgba(var(--ds-primary-rgb), 0.1);
    border: 1px solid rgba(var(--ds-primary-rgb), 0.22);
    color: var(--ds-text);
    padding: 4px 8px;
    font-size: 12px;
    transition:
        border-color 0.15s ease,
        background-color 0.15s ease;
}
.option-chip:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.38);
    background: rgba(var(--ds-primary-rgb), 0.14);
}
.option-chip button {
    border: 0;
    background: transparent;
    color: var(--ds-text-muted);
    cursor: pointer;
    border-radius: 999px;
    line-height: 1;
    transition:
        background-color 0.15s ease,
        color 0.15s ease;
}
.option-chip button:hover {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
}
.option-select-wrap {
    position: relative;
}
.option-select-wrap.open {
    z-index: 220;
}
.option-select {
    min-width: 220px;
}
.option-editor-line {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    flex-wrap: wrap;
    margin-top: 8px;
    position: relative;
    z-index: 120;
    width: fit-content;
    max-width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 8px;
    background: rgba(2, 6, 12, 0.42);
    padding: 7px;
}
.option-groups-summary {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.group-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid rgba(45, 212, 191, 0.22);
    border-radius: 999px;
    background: rgba(45, 212, 191, 0.08);
    color: var(--ds-text);
    padding: 4px 8px;
    font-size: 11px;
}
.group-pill-remove {
    display: inline-grid;
    place-items: center;
    width: 16px;
    height: 16px;
    min-width: 16px;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: var(--ds-text-muted);
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition:
        background-color 0.15s ease,
        color 0.15s ease;
}
.group-pill-remove .mi {
    font-size: 13px;
    line-height: 1;
}
.group-pill-remove:hover {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
}
.group-pill.warn {
    border-color: rgba(var(--ds-warning-rgb), 0.34);
    background: rgba(var(--ds-warning-rgb), 0.1);
    color: var(--ds-warning);
}
.option-groups-editor {
    margin-top: 10px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.02);
    padding: 10px;
}
.group-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.group-empty {
    color: var(--ds-text-muted);
    font-size: 12px;
    padding: 8px 0;
}
.option-group-card {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    padding: 9px;
}
.option-group-card + .option-group-card {
    margin-top: 8px;
}
.option-group-head,
.group-entry-row,
.entry-option-editor {
    display: flex;
    align-items: center;
    gap: 8px;
}
.option-group-head {
    margin-bottom: 8px;
}
.group-name-input {
    min-width: 180px;
    flex: 1;
}
.group-weight {
    color: var(--ds-text-muted);
    font-size: 12px;
    white-space: nowrap;
}
.group-weight.ok {
    color: var(--ds-success);
}
.group-weight.warn {
    color: var(--ds-warning);
}
.group-entry-list {
    display: grid;
    gap: 8px;
}
.group-entry-row {
    align-items: flex-start;
    border-top: 1px solid rgba(148, 163, 184, 0.12);
    padding-top: 8px;
}
.hsd-entry-list {
    display: grid;
    gap: 8px;
}
.hsd-entry-row {
    display: grid;
    grid-template-columns: minmax(120px, 1fr) 88px 30px;
    gap: 8px;
    align-items: center;
}
.hsd-day-input {
    min-width: 0;
}
.entry-label-input {
    width: 112px !important;
    flex-shrink: 0;
}
.entry-percent-input {
    width: 88px !important;
    flex-shrink: 0;
}
.entry-options {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    flex-wrap: wrap;
    min-width: 250px;
}
.entry-option-editor {
    flex-wrap: wrap;
    position: relative;
    z-index: 80;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 8px;
    background: rgba(2, 6, 12, 0.34);
    padding: 6px;
}
.entry-option-search {
    width: 220px !important;
}
.entry-param-input {
    width: 70px !important;
}
.group-add-entry {
    margin-top: 9px;
}
.option-dropdown {
    position: absolute;
    top: calc(100% + 3px);
    left: 0;
    right: auto;
    z-index: 500;
    width: max(100%, 260px);
    max-height: 220px;
    overflow: auto;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-xl);
}
.option-dropdown-item {
    width: 100%;
    border: 0;
    background: transparent;
    color: var(--ds-text);
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    cursor: pointer;
    text-align: left;
    transition:
        background-color 0.15s ease,
        color 0.15s ease;
}
.option-dropdown-item:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
}
.opt-id {
    min-width: 34px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.option-dropdown-empty {
    padding: 12px;
    color: var(--ds-text-muted);
    font-size: 12px;
    text-align: center;
}
.param-input {
    width: 82px !important;
}
.t-opt-add,
.t-opt-confirm,
.t-opt-cancel {
    width: 28px;
    height: 28px;
    border: 1px dashed rgba(var(--ds-primary-rgb), 0.35);
    border-radius: 999px;
    background: rgba(var(--ds-primary-rgb), 0.08);
    color: var(--ds-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition:
        background-color 0.15s ease,
        border-color 0.15s ease,
        color 0.15s ease,
        transform 0.15s ease,
        box-shadow 0.15s ease;
}
.t-opt-confirm {
    border-style: solid;
}
.t-opt-cancel {
    border-style: solid;
    border-color: rgba(148, 163, 184, 0.28);
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
}
.t-opt-add:hover,
.t-opt-add.active,
.t-opt-confirm:hover {
    border-color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.14);
    transform: translateY(-1px);
    box-shadow: var(--ds-shadow-sm);
}
.t-opt-cancel:hover {
    border-color: rgba(var(--ds-danger-rgb), 0.48);
    background: rgba(var(--ds-danger-rgb), 0.1);
    color: var(--ds-danger);
    transform: translateY(-1px);
    box-shadow: var(--ds-shadow-sm);
}
.t-opt-add:active,
.t-opt-confirm:active,
.t-opt-cancel:active {
    transform: translateY(0);
    box-shadow: none;
}
.t-opt-add:focus-visible,
.t-opt-confirm:focus-visible,
.t-opt-cancel:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.18);
}
.group-toggle.active {
    border-color: rgba(45, 212, 191, 0.7);
    background: rgba(45, 212, 191, 0.14);
    color: #2dd4bf;
}
.editor-actions {
    justify-content: flex-end;
    margin-top: 16px;
}
@media (max-width: 900px) {
    .editor-grid {
        grid-template-columns: 1fr;
    }
    .picker-tools {
        grid-template-columns: 1fr;
    }
    .picker-foot {
        align-items: flex-start;
        flex-direction: column;
    }
    .group-entry-row {
        flex-wrap: wrap;
    }
    .hsd-entry-row {
        grid-template-columns: 1fr 88px 30px;
    }
    .entry-options {
        min-width: 100%;
    }
    .reward-table-wrap {
        overflow-x: auto;
    }
    .entry-option-search {
        width: 180px !important;
    }
}
</style>
