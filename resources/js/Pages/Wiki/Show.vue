<template>
    <Head><title>{{ page.title }}</title></Head>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-1.5 text-sm text-gray-500 flex-wrap">
                <Link :href="route('spaces.index')" class="hover:text-gray-700">Разделы</Link>
                <span>/</span>
                <Link :href="route('spaces.show', space.slug)" class="hover:text-gray-700">{{ space.name }}</Link>
                <template v-for="(crumb, i) in breadcrumbs" :key="crumb.id">
                    <span>/</span>
                    <Link
                        :href="route('pages.show', [space.slug, crumb.slug])"
                        class="hover:text-gray-700"
                        :class="{ 'text-gray-900 font-medium': i === breadcrumbs.length - 1 }"
                    >
                        {{ crumb.title }}
                    </Link>
                </template>
            </nav>
        </template>
        <template #actions>
            <Link v-if="canEdit" :href="route('pages.edit', [space.slug, page.slug])" class="btn-ghost text-sm">
                ✏️ Редактировать
            </Link>
            <Link :href="route('pages.history', [space.slug, page.slug])" class="btn-ghost text-sm">
                История
            </Link>
        </template>

        <div class="flex h-full">
            <!-- Sidebar: children + siblings -->
            <aside class="w-56 border-r border-gray-200 bg-white overflow-y-auto flex-shrink-0 p-3">
                <!-- Подстраницы -->
                <template v-if="page.children?.length">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-2 mb-1">Подстраницы</p>
                    <Link
                        v-for="child in page.children"
                        :key="child.id"
                        :href="route('pages.show', [space.slug, child.slug])"
                        class="block px-2 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition truncate"
                    >
                        {{ child.title }}
                    </Link>
                    <div v-if="siblings.length" class="border-t border-gray-100 mt-2 mb-1" />
                </template>

                <!-- Соседние страницы -->
                <template v-if="siblings.length">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-2 mb-1">
                        {{ page.children?.length ? 'Соседние' : 'Страницы' }}
                    </p>
                    <Link
                        v-for="s in siblings"
                        :key="s.id"
                        :href="route('pages.show', [space.slug, s.slug])"
                        class="block px-2 py-1.5 rounded-lg text-sm transition truncate"
                        :class="s.id === page.id
                            ? 'bg-indigo-50 text-indigo-700 font-medium'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    >
                        {{ s.title }}
                    </Link>
                </template>

                <!-- Нет ничего -->
                <div v-if="!page.children?.length && !siblings.length" class="px-2 py-4 text-xs text-gray-400 text-center">
                    Нет связанных страниц
                </div>
            </aside>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="max-w-3xl mx-auto px-8 py-8">
                    <!-- Title -->
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ page.title }}</h1>

                    <!-- Meta -->
                    <div class="flex items-center gap-4 text-sm text-gray-400 mb-6 pb-6 border-b border-gray-200 flex-wrap">
                        <span>Автор: {{ page.author?.name }}</span>
                        <span v-if="page.last_editor">Изменил: {{ page.last_editor.name }}</span>
                        <span>{{ formatDate(page.updated_at) }}</span>

                        <!-- Tags -->
                        <div v-if="page.tags?.length" class="flex gap-1.5 ml-auto flex-wrap">
                            <span
                                v-for="tag in page.tags"
                                :key="tag.id"
                                class="px-2 py-0.5 rounded-full text-xs font-medium"
                                :style="{ backgroundColor: (tag.color ?? '#6366f1') + '33', color: tag.color ?? '#6366f1' }"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Page content -->
                    <TipTapEditor
                        v-if="page.content"
                        :model-value="page.content"
                        :editable="false"
                    />
                    <p v-else class="text-gray-400 italic">Страница пока не имеет содержимого.</p>

                    <!-- Prev / Next навигация -->
                    <div v-if="prevPage || nextPage" class="mt-12 pt-6 border-t border-gray-200 grid grid-cols-2 gap-4">
                        <Link
                            v-if="prevPage"
                            :href="route('pages.show', [space.slug, prevPage.slug])"
                            class="group flex flex-col gap-1 p-4 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition"
                        >
                            <span class="text-xs text-gray-400 group-hover:text-indigo-500">← Предыдущая</span>
                            <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-700 truncate">{{ prevPage.title }}</span>
                        </Link>
                        <div v-else />

                        <Link
                            v-if="nextPage"
                            :href="route('pages.show', [space.slug, nextPage.slug])"
                            class="group flex flex-col gap-1 p-4 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition text-right"
                        >
                            <span class="text-xs text-gray-400 group-hover:text-indigo-500">Следующая →</span>
                            <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-700 truncate">{{ nextPage.title }}</span>
                        </Link>
                        <div v-else />
                    </div>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TipTapEditor from '@/Components/Editor/TipTapEditor.vue'

const props = defineProps({
    space:       { type: Object, required: true },
    page:        { type: Object, required: true },
    breadcrumbs: { type: Array,  default: () => [] },
    siblings:    { type: Array,  default: () => [] },
})

const authUser = usePage().props.auth.user
const canEdit = computed(() => ['admin', 'editor'].includes(authUser.default_role))

const formatDate = (date) => new Date(date).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })

const currentIndex = computed(() => props.siblings.findIndex((s) => s.id === props.page.id))
const prevPage = computed(() => currentIndex.value > 0 ? props.siblings[currentIndex.value - 1] : null)
const nextPage = computed(() => currentIndex.value < props.siblings.length - 1 ? props.siblings[currentIndex.value + 1] : null)
</script>
