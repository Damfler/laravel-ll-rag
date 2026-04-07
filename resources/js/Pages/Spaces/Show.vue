<template>
    <Head><title>{{ space.name }}</title></Head>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('spaces.index')" class="hover:text-gray-700">Разделы</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">{{ space.name }}</span>
            </nav>
        </template>
        <template #actions>
            <Link
                v-if="canEdit"
                :href="route('pages.create', space.slug)"
                class="btn-primary"
            >
                + Новая страница
            </Link>
        </template>

        <div class="flex h-full">
            <!-- Pages tree -->
            <aside class="w-64 border-r border-gray-200 bg-white overflow-y-auto flex-shrink-0 p-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-2 mb-2">Страницы</p>
                <div v-if="!pages.length" class="px-2 py-4 text-sm text-gray-400 text-center">
                    Страниц пока нет
                </div>
                <PageTreeItem
                    v-for="page in pages"
                    :key="page.id"
                    :page="page"
                    :space="space"
                />
            </aside>

            <!-- Space overview -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-3xl">
                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl"
                            :style="{ backgroundColor: (space.color ?? '#6366f1') + '22' }"
                        >
                            {{ space.icon }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ space.name }}</h1>
                            <p class="text-gray-500 text-sm mt-0.5">{{ space.owner.name }} · {{ allPages.length }} страниц</p>
                        </div>
                        <Link
                            v-if="canEdit"
                            :href="route('spaces.edit', space.slug)"
                            class="ml-auto btn-ghost text-sm"
                        >
                            Настройки
                        </Link>
                    </div>

                    <p v-if="space.description" class="text-gray-600 mb-6">{{ space.description }}</p>

                    <!-- Tag filter -->
                    <div v-if="tags.length" class="mb-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Фильтр по тегам</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in tags"
                                :key="tag.id"
                                @click="toggleTag(tag.id)"
                                class="px-3 py-1 rounded-full text-xs font-medium border transition"
                                :class="selectedTags.includes(tag.id)
                                    ? 'border-transparent shadow-sm'
                                    : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                :style="selectedTags.includes(tag.id)
                                    ? { backgroundColor: tag.color + '33', color: tag.color, borderColor: tag.color + '66' }
                                    : {}"
                            >
                                {{ tag.name }}
                            </button>
                            <button
                                v-if="selectedTags.length"
                                @click="selectedTags = []"
                                class="px-3 py-1 rounded-full text-xs text-gray-400 hover:text-gray-600 border border-gray-200 hover:border-gray-300 transition"
                            >
                                × Сбросить
                            </button>
                        </div>
                    </div>

                    <!-- Pages list -->
                    <div v-if="filteredPages.length">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            {{ selectedTags.length ? `Страниц по фильтру: ${filteredPages.length}` : 'Страницы' }}
                        </h2>
                        <div class="space-y-2">
                            <Link
                                v-for="page in filteredPages"
                                :key="page.id"
                                :href="route('pages.show', [space.slug, page.slug])"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition"
                            >
                                <span class="text-gray-400">📄</span>
                                <div class="flex-1 min-w-0">
                                    <span class="text-gray-900 font-medium">{{ page.title }}</span>
                                    <!-- Tags -->
                                    <div v-if="page.tags?.length" class="flex gap-1 mt-1 flex-wrap">
                                        <span
                                            v-for="tag in page.tags"
                                            :key="tag.id"
                                            class="px-1.5 py-0.5 rounded text-xs"
                                            :style="{ backgroundColor: tag.color + '22', color: tag.color }"
                                        >{{ tag.name }}</span>
                                    </div>
                                </div>
                                <span v-if="page.children?.length" class="text-xs text-gray-400">
                                    {{ page.children.length }} подстр.
                                </span>
                            </Link>
                        </div>
                    </div>

                    <div v-else-if="selectedTags.length" class="text-center py-12 text-gray-400">
                        <p>Нет страниц с выбранными тегами</p>
                        <button @click="selectedTags = []" class="text-indigo-500 hover:underline text-sm mt-2">Сбросить фильтр</button>
                    </div>

                    <div v-else class="text-center py-20 text-gray-400">
                        <div class="text-5xl mb-3">📝</div>
                        <p class="font-medium">В этом разделе пока нет страниц</p>
                        <Link v-if="canEdit" :href="route('pages.create', space.slug)" class="btn-primary mt-4 inline-block">
                            Создать первую страницу
                        </Link>
                    </div>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageTreeItem from '@/Components/Sidebar/PageTreeItem.vue'

const props = defineProps({
    space: { type: Object, required: true },
    pages: { type: Array,  required: true },
    tags:  { type: Array,  default: () => [] },
})

const authUser = usePage().props.auth.user
const canEdit = computed(() => ['admin', 'editor'].includes(authUser.default_role))

const selectedTags = ref([])

const toggleTag = (id) => {
    const idx = selectedTags.value.indexOf(id)
    if (idx === -1) selectedTags.value.push(id)
    else selectedTags.value.splice(idx, 1)
}

// Плоский список всех страниц (включая дочерние) для подсчёта и фильтрации
function flattenPages(list) {
    const result = []
    for (const p of list) {
        result.push(p)
        if (p.children?.length) result.push(...flattenPages(p.children))
    }
    return result
}

const allPages = computed(() => flattenPages(props.pages))

const filteredPages = computed(() => {
    if (!selectedTags.value.length) return props.pages
    // При активном фильтре показываем плоский список совпадающих страниц
    return allPages.value.filter((p) =>
        p.tags?.some((t) => selectedTags.value.includes(t.id))
    )
})
</script>
