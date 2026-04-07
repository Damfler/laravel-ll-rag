<template>
    <div class="page-content" v-html="html" />
</template>

<script setup>
import { computed } from 'vue'
import { generateHTML } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { TaskList } from '@tiptap/extension-task-list'
import { TaskItem } from '@tiptap/extension-task-item'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import { Link } from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'

const props = defineProps({
    content: { type: Object, default: null },
})

const extensions = [
    StarterKit,
    TaskList,
    TaskItem.configure({ nested: true }),
    Table.configure({ resizable: false }),
    TableRow,
    TableHeader,
    TableCell,
    Link.configure({ openOnClick: true }),
    Image,
]

const html = computed(() => {
    if (!props.content) return ''
    try {
        return generateHTML(props.content, extensions)
    } catch (e) {
        console.warn('PageContent render error:', e)
        return '<p class="text-gray-400 italic">Не удалось отобразить содержимое.</p>'
    }
})
</script>

<style>
.page-content h1 { @apply text-2xl font-bold mt-6 mb-3; }
.page-content h2 { @apply text-xl font-bold mt-5 mb-2; }
.page-content h3 { @apply text-lg font-semibold mt-4 mb-2; }
.page-content h4 { @apply text-base font-semibold mt-3 mb-1; }
.page-content p  { @apply my-2 leading-relaxed text-gray-800; }
.page-content ul { @apply list-disc pl-6 my-2 space-y-1; }
.page-content ol { @apply list-decimal pl-6 my-2 space-y-1; }
.page-content li { @apply text-gray-800; }
.page-content blockquote { @apply border-l-4 border-gray-300 pl-4 text-gray-600 italic my-3; }
.page-content code { @apply bg-gray-100 rounded px-1.5 py-0.5 text-sm font-mono text-red-600; }
.page-content pre  { @apply bg-gray-900 text-gray-100 rounded-xl p-4 my-3 overflow-x-auto text-sm; }
.page-content pre code { @apply bg-transparent text-inherit p-0; }
.page-content table { @apply w-full border-collapse my-4 text-sm; }
.page-content th { @apply bg-gray-50 border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700; }
.page-content td { @apply border border-gray-200 px-3 py-2 text-gray-800; }
.page-content tr:nth-child(even) td { @apply bg-gray-50; }
.page-content a  { @apply text-indigo-600 underline hover:text-indigo-800; }
.page-content img { @apply rounded-lg max-w-full my-3 shadow-sm; }
.page-content hr  { @apply border-gray-200 my-6; }
.page-content ul[data-type="taskList"] { @apply list-none pl-2; }
.page-content ul[data-type="taskList"] li { @apply flex items-start gap-2; }
.page-content ul[data-type="taskList"] li input[type="checkbox"] { @apply mt-1 flex-shrink-0; }
</style>
