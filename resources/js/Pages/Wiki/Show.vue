<template>
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
            <!-- Sidebar: children -->
            <aside v-if="page.children?.length" class="w-56 border-r border-gray-200 bg-white overflow-y-auto p-3 flex-shrink-0">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-2 mb-2">Подстраницы</p>
                <Link
                    v-for="child in page.children"
                    :key="child.id"
                    :href="route('pages.show', [space.slug, child.slug])"
                    class="block px-2 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition truncate"
                >
                    {{ child.title }}
                </Link>
            </aside>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="max-w-3xl mx-auto px-8 py-8">
                    <!-- Title -->
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ page.title }}</h1>

                    <!-- Meta -->
                    <div class="flex items-center gap-4 text-sm text-gray-400 mb-6 pb-6 border-b border-gray-200">
                        <span>Автор: {{ page.author?.name }}</span>
                        <span v-if="page.last_editor">Изменил: {{ page.last_editor.name }}</span>
                        <span>{{ formatDate(page.updated_at) }}</span>

                        <!-- Tags -->
                        <div v-if="page.tags?.length" class="flex gap-1.5 ml-auto flex-wrap">
                            <span
                                v-for="tag in page.tags"
                                :key="tag.id"
                                class="px-2 py-0.5 rounded-full text-xs font-medium"
                                :style="{ backgroundColor: tag.color + '33', color: tag.color }"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Page content (read-only TipTap) -->
                    <TipTapEditor
                        v-if="page.content"
                        :model-value="page.content"
                        :editable="false"
                    />
                    <p v-else class="text-gray-400 italic">Страница пока не имеет содержимого.</p>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
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
</script>
