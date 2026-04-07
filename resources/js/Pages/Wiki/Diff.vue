<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('spaces.show', space.slug)" class="hover:text-gray-700">{{ space.name }}</Link>
                <span>/</span>
                <Link :href="route('pages.show', [space.slug, page.slug])" class="hover:text-gray-700">{{ page.title }}</Link>
                <span>/</span>
                <Link :href="route('pages.history', [space.slug, page.slug])" class="hover:text-gray-700">История</Link>
                <span>/</span>
                <span class="text-gray-900">Сравнение</span>
            </nav>
        </template>

        <div class="max-w-4xl mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Сравнение версий</h1>

            <!-- Заголовки версий -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    <p class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-1">Версия {{ versionA.version_number }}</p>
                    <p class="text-sm text-gray-700 font-medium">{{ versionA.title }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ versionA.author?.name }} · {{ formatDate(versionA.created_at) }}</p>
                    <p v-if="versionA.change_summary" class="text-xs text-gray-500 italic mt-0.5">"{{ versionA.change_summary }}"</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    <p class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-1">
                        {{ versionB ? `Версия ${versionB.version_number}` : 'Текущая версия' }}
                    </p>
                    <p class="text-sm text-gray-700 font-medium">{{ (versionB ?? page).title }}</p>
                    <p v-if="versionB" class="text-xs text-gray-500 mt-0.5">{{ versionB.author?.name }} · {{ formatDate(versionB.created_at) }}</p>
                    <p v-if="versionB?.change_summary" class="text-xs text-gray-500 italic mt-0.5">"{{ versionB.change_summary }}"</p>
                </div>
            </div>

            <!-- Изменения заголовка -->
            <div v-if="titleChanged" class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm">
                <p class="font-medium text-gray-600 mb-1">Заголовок изменён:</p>
                <p><span class="line-through text-red-600">{{ versionA.title }}</span> → <span class="text-green-700 font-medium">{{ (versionB ?? page).title }}</span></p>
            </div>

            <!-- Diff контента -->
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-600">Изменения содержимого</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-100 border border-green-300 inline-block"></span> добавлено</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-100 border border-red-300 inline-block"></span> удалено</span>
                    </div>
                </div>

                <div v-if="diffLines.length" class="font-mono text-sm divide-y divide-gray-100">
                    <div
                        v-for="(line, i) in diffLines"
                        :key="i"
                        class="flex items-start px-4 py-1 leading-6"
                        :class="{
                            'bg-green-50': line.type === 'add',
                            'bg-red-50': line.type === 'remove',
                            'bg-white': line.type === 'same',
                        }"
                    >
                        <span class="w-6 flex-shrink-0 text-center select-none"
                            :class="{
                                'text-green-600': line.type === 'add',
                                'text-red-500': line.type === 'remove',
                                'text-gray-300': line.type === 'same',
                            }"
                        >{{ line.type === 'add' ? '+' : line.type === 'remove' ? '−' : ' ' }}</span>
                        <span
                            class="flex-1 whitespace-pre-wrap break-words"
                            :class="{
                                'text-green-800': line.type === 'add',
                                'text-red-700 line-through': line.type === 'remove',
                                'text-gray-700': line.type === 'same',
                            }"
                        >{{ line.text || '\u00a0' }}</span>
                    </div>
                </div>

                <div v-else class="px-4 py-8 text-center text-gray-400 text-sm">
                    Содержимое не изменилось
                </div>
            </div>

            <!-- Кнопка откатиться -->
            <div v-if="canEdit" class="mt-4 flex justify-end">
                <button @click="restoreVersion" class="text-sm text-amber-600 hover:text-amber-800 px-4 py-2 border border-amber-200 rounded-lg hover:bg-amber-50 transition">
                    Откатить к версии {{ versionA.version_number }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    space:    { type: Object, required: true },
    page:     { type: Object, required: true },
    versionA: { type: Object, required: true },
    versionB: { type: Object, default: null },
})

const authUser = usePage().props.auth.user
const canEdit = computed(() => ['admin', 'editor'].includes(authUser.default_role))

const formatDate = (date) => new Date(date).toLocaleString('ru-RU')

const titleChanged = computed(() => {
    const titleB = props.versionB ? props.versionB.title : props.page.title
    return props.versionA.title !== titleB
})

// ─── LCS Diff algorithm ──────────────────────────────────────────────────────

function computeLCS(a, b) {
    const m = a.length
    const n = b.length
    const dp = Array.from({ length: m + 1 }, () => new Uint16Array(n + 1))

    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            dp[i][j] = a[i - 1] === b[j - 1]
                ? dp[i - 1][j - 1] + 1
                : Math.max(dp[i - 1][j], dp[i][j - 1])
        }
    }

    const result = []
    let i = m, j = n
    while (i > 0 || j > 0) {
        if (i > 0 && j > 0 && a[i - 1] === b[j - 1]) {
            result.unshift({ type: 'same', text: a[i - 1] })
            i--; j--
        } else if (j > 0 && (i === 0 || dp[i][j - 1] >= dp[i - 1][j])) {
            result.unshift({ type: 'add', text: b[j - 1] })
            j--
        } else {
            result.unshift({ type: 'remove', text: a[i - 1] })
            i--
        }
    }
    return result
}

const diffLines = computed(() => {
    const textA = props.versionA.content_text ?? ''
    const textB = props.versionB
        ? (props.versionB.content_text ?? '')
        : (props.page.content_text ?? '')

    const linesA = textA.split('\n')
    const linesB = textB.split('\n')

    // Ограничиваем для больших файлов чтобы не зависнуть
    if (linesA.length > 2000 || linesB.length > 2000) {
        return [{ type: 'same', text: '(Файл слишком большой для построчного сравнения)' }]
    }

    const diff = computeLCS(linesA, linesB)

    // Сворачиваем большие блоки одинакового текста для читаемости
    const result = []
    let sameRun = []

    const flushSame = () => {
        if (sameRun.length === 0) return
        if (sameRun.length <= 4) {
            result.push(...sameRun)
        } else {
            result.push(sameRun[0])
            if (sameRun.length > 3) {
                result.push({ type: 'same', text: `··· ${sameRun.length - 2} строк без изменений ···` })
            }
            result.push(sameRun[sameRun.length - 1])
        }
        sameRun = []
    }

    for (const item of diff) {
        if (item.type === 'same') {
            sameRun.push(item)
        } else {
            flushSame()
            result.push(item)
        }
    }
    flushSame()

    return result
})

const restoreVersion = () => {
    if (!confirm(`Откатить страницу к версии #${props.versionA.version_number}? Текущее содержимое будет сохранено как новая версия.`)) return
    router.post(route('pages.restore', [props.space.slug, props.page.slug, props.versionA.id]))
}
</script>
