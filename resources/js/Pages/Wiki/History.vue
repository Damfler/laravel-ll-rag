<template>
    <Head><title>{{ page.title }} — История</title></Head>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('spaces.show', space.slug)" class="hover:text-gray-700">{{ space.name }}</Link>
                <span>/</span>
                <Link :href="route('pages.show', [space.slug, page.slug])" class="hover:text-gray-700">{{ page.title }}</Link>
                <span>/</span>
                <span class="text-gray-900">История изменений</span>
            </nav>
        </template>

        <div class="max-w-3xl mx-auto p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">История: {{ page.title }}</h1>
                <button
                    v-if="selected.length === 2"
                    @click="comparSelected"
                    class="btn-primary text-sm"
                >
                    Сравнить выбранные
                </button>
                <p v-else class="text-sm text-gray-400">
                    {{ selected.length === 0 ? 'Выберите до 2 версий для сравнения' : 'Выберите ещё одну версию' }}
                </p>
            </div>

            <div class="space-y-3">
                <div
                    v-for="version in versions.data"
                    :key="version.id"
                    class="flex items-center gap-4 bg-white border border-gray-200 rounded-xl px-5 py-4 transition"
                    :class="{ 'border-indigo-300 bg-indigo-50': selected.includes(version.id) }"
                >
                    <!-- Чекбокс для сравнения -->
                    <input
                        type="checkbox"
                        :value="version.id"
                        v-model="selected"
                        :disabled="selected.length >= 2 && !selected.includes(version.id)"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />

                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-sm font-bold flex-shrink-0">
                        v{{ version.version_number }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900">{{ version.title }}</p>
                        <p class="text-sm text-gray-500">
                            {{ version.author?.name }} · {{ formatDate(version.created_at) }}
                        </p>
                        <p v-if="version.change_summary" class="text-sm text-gray-600 mt-0.5 italic">
                            "{{ version.change_summary }}"
                        </p>
                    </div>

                    <!-- Действия -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <Link
                            :href="route('pages.diff', [space.slug, page.slug]) + `?from=${version.id}`"
                            class="text-xs text-gray-500 hover:text-indigo-600 px-2 py-1 rounded hover:bg-indigo-50 transition"
                        >
                            Diff
                        </Link>
                        <button
                            v-if="canEdit && version.version_number !== latestVersionNumber"
                            @click="restore(version)"
                            class="text-xs text-amber-600 hover:text-amber-800 px-2 py-1 rounded hover:bg-amber-50 transition"
                        >
                            Откатить
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="versions.last_page > 1" class="flex justify-center gap-2 mt-6">
                <Link
                    v-for="p in versions.last_page"
                    :key="p"
                    :href="`?page=${p}`"
                    class="px-3 py-1.5 rounded-lg text-sm border"
                    :class="p === versions.current_page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                >
                    {{ p }}
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    space:    { type: Object, required: true },
    page:     { type: Object, required: true },
    versions: { type: Object, required: true },
})

const authUser = usePage().props.auth.user
const canEdit = computed(() => ['admin', 'editor'].includes(authUser.default_role))

const selected = ref([])

const latestVersionNumber = computed(() =>
    Math.max(...props.versions.data.map((v) => v.version_number), 0)
)

const formatDate = (date) => new Date(date).toLocaleString('ru-RU')

const comparSelected = () => {
    const [a, b] = selected.value
    const url = route('pages.diff', [props.space.slug, props.page.slug]) + `?from=${a}&to=${b}`
    router.visit(url)
}

const restore = (version) => {
    if (!confirm(`Откатить страницу к версии #${version.version_number}? Текущее содержимое будет сохранено как новая версия.`)) return
    router.post(route('pages.restore', [props.space.slug, props.page.slug, version.id]))
}
</script>
