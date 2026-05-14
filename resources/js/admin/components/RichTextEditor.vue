<template>
    <div class="rich-editor">
        <div class="rich-editor__toolbar">
            <button type="button" title="Đậm" @click="run('bold')">
                <span class="mi">format_bold</span>
            </button>
            <button type="button" title="Nghiêng" @click="run('italic')">
                <span class="mi">format_italic</span>
            </button>
            <button type="button" title="Gạch chân" @click="run('underline')">
                <span class="mi">format_underlined</span>
            </button>
            <span class="rich-editor__divider"></span>
            <button type="button" title="Tiêu đề" @click="block('h3')">
                <span class="mi">title</span>
            </button>
            <button type="button" title="Đoạn văn" @click="block('p')">
                <span class="mi">notes</span>
            </button>
            <button type="button" title="Danh sách" @click="run('insertUnorderedList')">
                <span class="mi">format_list_bulleted</span>
            </button>
            <button type="button" title="Danh sách số" @click="run('insertOrderedList')">
                <span class="mi">format_list_numbered</span>
            </button>
            <button type="button" title="Trích dẫn" @click="block('blockquote')">
                <span class="mi">format_quote</span>
            </button>
            <span class="rich-editor__divider"></span>
            <button type="button" title="Chèn liên kết" @click="link">
                <span class="mi">link</span>
            </button>
            <button type="button" title="Xóa định dạng" @click="run('removeFormat')">
                <span class="mi">format_clear</span>
            </button>
        </div>

        <div
            ref="editor"
            class="rich-editor__surface"
            contenteditable="true"
            :data-placeholder="placeholder"
            @input="sync"
            @blur="sync"
        ></div>
    </div>
</template>

<script>
export default {
    name: "RichTextEditor",
    props: {
        modelValue: {
            type: String,
            default: "",
        },
        placeholder: {
            type: String,
            default: "Nhập nội dung...",
        },
    },
    emits: ["update:modelValue"],
    mounted() {
        this.renderValue();
    },
    watch: {
        modelValue() {
            if (this.$refs.editor && this.$refs.editor.innerHTML !== this.modelValue) {
                this.renderValue();
            }
        },
    },
    methods: {
        renderValue() {
            this.$refs.editor.innerHTML = this.modelValue || "";
        },
        focus() {
            this.$refs.editor?.focus();
        },
        run(command, value = null) {
            this.focus();
            document.execCommand(command, false, value);
            this.sync();
        },
        block(tag) {
            this.run("formatBlock", tag);
        },
        link() {
            const url = window.prompt("Nhập URL liên kết");
            if (!url) return;
            this.run("createLink", url);
        },
        sync() {
            this.$emit("update:modelValue", this.$refs.editor?.innerHTML || "");
        },
    },
};
</script>

<style scoped>
.rich-editor {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-body-bg);
    overflow: hidden;
}
.rich-editor__toolbar {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
    padding: 8px;
    border-bottom: 1px solid var(--ds-border);
    background: var(--ds-gray-100);
}
.rich-editor__toolbar button {
    width: 34px;
    height: 32px;
    border: 1px solid transparent;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: var(--ds-text);
    cursor: pointer;
}
.rich-editor__toolbar button:hover {
    border-color: var(--ds-border);
    background: var(--ds-surface);
}
.rich-editor__toolbar .mi {
    font-size: 18px;
}
.rich-editor__divider {
    width: 1px;
    height: 22px;
    background: var(--ds-border);
    margin: 0 4px;
}
.rich-editor__surface {
    min-height: 220px;
    padding: 14px;
    color: var(--ds-text-emphasis);
    outline: none;
    line-height: 1.65;
}
.rich-editor__surface:empty::before {
    content: attr(data-placeholder);
    color: var(--ds-text-muted);
}
.rich-editor__surface :deep(h3) {
    margin: 0 0 10px;
    font-size: 18px;
}
.rich-editor__surface :deep(p) {
    margin: 0 0 10px;
}
.rich-editor__surface :deep(blockquote) {
    margin: 10px 0;
    padding: 8px 12px;
    border-left: 3px solid var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.1);
    border-radius: 6px;
}
</style>
