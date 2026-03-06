<template>
    <AppLayout :spaces="[$page.props.space]">
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
                            :style="{ backgroundColor: space.color + '22' }"
                        >
                            {{ space.icon }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ space.name }}</h1>
                            <p class="text-gray-500 text-sm mt-0.5">{{ space.owner.name }} · {{ pages.length }} страниц</p>
                        </div>
                        <Link
                            v-if="canEdit"
                            :href="route('spaces.edit', space.slug)"
                            class="ml-auto btn-ghost text-sm"
                        >
                            Настройки
                        </Link>
                    </div>

                    <p v-if="space.description" class="text-gray-600 mb-8">{{ space.description }}</p>

                    <!-- Recent pages -->
                    <div v-if="pages.length">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Страницы</h2>
                        <div class="space-y-2">
                            <Link
                                v-for="page in pages"
                                :key="page.id"
                                :href="route('pages.show', [space.slug, page.slug])"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition"
                            >
                                <span class="text-gray-400">📄</span>
                                <span class="text-gray-900 font-medium">{{ page.title }}</span>
                                <span v-if="page.children?.length" class="ml-auto text-xs text-gray-400">
                                    {{ page.children.length }} подстр.
                                </span>
                            </Link>
                        </div>
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
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageTreeItem from '@/Components/Sidebar/PageTreeItem.vue'

const props = defineProps({
    space: { type: Object, required: true },
    pages: { type: Array, required: true },
})

const authUser = usePage().props.auth.user
const canEdit = computed(() => ['admin', 'editor'].includes(authUser.default_role))
</script>
