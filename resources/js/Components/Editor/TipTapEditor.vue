<template>
    <div class="tiptap-wrapper">
        <!-- Toolbar -->
        <div v-if="editor" class="tiptap-toolbar">
            <button @click="editor.chain().focus().toggleBold().run()" :class="{ active: editor.isActive('bold') }" title="Bold">
                <strong>B</strong>
            </button>
            <button @click="editor.chain().focus().toggleItalic().run()" :class="{ active: editor.isActive('italic') }" title="Italic">
                <em>I</em>
            </button>
            <button @click="editor.chain().focus().toggleStrike().run()" :class="{ active: editor.isActive('strike') }" title="Strikethrough">
                <s>S</s>
            </button>

            <span class="divider" />

            <button
                v-for="level in [1, 2, 3]"
                :key="level"
                @click="editor.chain().focus().toggleHeading({ level }).run()"
                :class="{ active: editor.isActive('heading', { level }) }"
                :title="`Heading ${level}`"
            >
                H{{ level }}
            </button>

            <span class="divider" />

            <button @click="editor.chain().focus().toggleBulletList().run()" :class="{ active: editor.isActive('bulletList') }" title="Bullet list">
                ≡
            </button>
            <button @click="editor.chain().focus().toggleOrderedList().run()" :class="{ active: editor.isActive('orderedList') }" title="Numbered list">
                1.
            </button>
            <button @click="editor.chain().focus().toggleTaskList().run()" :class="{ active: editor.isActive('taskList') }" title="Task list">
                ☑
            </button>

            <span class="divider" />

            <button @click="editor.chain().focus().toggleCodeBlock().run()" :class="{ active: editor.isActive('codeBlock') }" title="Code block">
                &lt;/&gt;
            </button>
            <button @click="editor.chain().focus().toggleBlockquote().run()" :class="{ active: editor.isActive('blockquote') }" title="Quote">
                "
            </button>

            <span class="divider" />

            <button @click="insertTable" title="Table">
                ⊞
            </button>

            <span class="ml-auto text-xs text-gray-400 self-center pr-2">
                {{ charCount }} симв.
            </span>
        </div>

        <!-- Editor -->
        <EditorContent :editor="editor" class="tiptap-content" />
    </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import { computed, watch, onBeforeUnmount } from 'vue'
import StarterKit from '@tiptap/starter-kit'
import { Placeholder } from '@tiptap/extension-placeholder'
import { CharacterCount } from '@tiptap/extension-character-count'
import { TaskList } from '@tiptap/extension-task-list'
import { TaskItem } from '@tiptap/extension-task-item'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import { Link } from '@tiptap/extension-link'

const props = defineProps({
    modelValue: {
        type: Object,
        default: null,
    },
    placeholder: {
        type: String,
        default: 'Начните писать...',
    },
    editable: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['update:modelValue', 'update:text'])

const editor = useEditor({
    editable: props.editable,
    content: props.modelValue ?? '',
    extensions: [
        StarterKit,
        Placeholder.configure({ placeholder: props.placeholder }),
        CharacterCount,
        TaskList,
        TaskItem.configure({ nested: true }),
        Table.configure({ resizable: true }),
        TableRow,
        TableHeader,
        TableCell,
        Link.configure({ openOnClick: false }),
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getJSON())
        emit('update:text', editor.getText())
    },
})

const charCount = computed(() => editor.value?.storage.characterCount.characters() ?? 0)

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return
        const isSame = JSON.stringify(editor.value.getJSON()) === JSON.stringify(value)
        if (!isSame) editor.value.commands.setContent(value ?? '', false)
    },
)

const insertTable = () => {
    editor.value
        ?.chain()
        .focus()
        .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
        .run()
}

onBeforeUnmount(() => editor.value?.destroy())
</script>

<style>
.tiptap-wrapper {
    @apply border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500;
}

.tiptap-toolbar {
    @apply flex items-center gap-1 px-2 py-1.5 border-b border-gray-200 bg-gray-50 flex-wrap;
}

.tiptap-toolbar button {
    @apply px-2 py-1 rounded text-sm text-gray-600 hover:bg-gray-200 hover:text-gray-900 transition;
}

.tiptap-toolbar button.active {
    @apply bg-indigo-100 text-indigo-700;
}

.tiptap-toolbar .divider {
    @apply w-px h-5 bg-gray-300 mx-1;
}

.tiptap-content .ProseMirror {
    @apply p-4 min-h-64 outline-none;
}

.tiptap-content .ProseMirror p.is-editor-empty:first-child::before {
    @apply text-gray-400;
    content: attr(data-placeholder);
    float: left;
    pointer-events: none;
    height: 0;
}

.tiptap-content .ProseMirror h1 { @apply text-2xl font-bold mt-4 mb-2; }
.tiptap-content .ProseMirror h2 { @apply text-xl font-bold mt-3 mb-2; }
.tiptap-content .ProseMirror h3 { @apply text-lg font-semibold mt-2 mb-1; }
.tiptap-content .ProseMirror ul { @apply list-disc pl-6 my-2; }
.tiptap-content .ProseMirror ol { @apply list-decimal pl-6 my-2; }
.tiptap-content .ProseMirror blockquote { @apply border-l-4 border-gray-300 pl-4 text-gray-600 italic my-2; }
.tiptap-content .ProseMirror code { @apply bg-gray-100 rounded px-1 text-sm font-mono; }
.tiptap-content .ProseMirror pre { @apply bg-gray-900 text-gray-100 rounded-lg p-4 my-2 overflow-x-auto; }
.tiptap-content .ProseMirror pre code { @apply bg-transparent text-inherit; }
.tiptap-content .ProseMirror table { @apply w-full border-collapse my-2; }
.tiptap-content .ProseMirror th { @apply bg-gray-100 border border-gray-300 px-3 py-2 text-left font-semibold; }
.tiptap-content .ProseMirror td { @apply border border-gray-300 px-3 py-2; }
.tiptap-content .ProseMirror ul[data-type="taskList"] { @apply list-none pl-2; }
.tiptap-content .ProseMirror ul[data-type="taskList"] li { @apply flex items-start gap-2; }
.tiptap-content .ProseMirror a { @apply text-indigo-600 underline hover:text-indigo-800; }
</style>
