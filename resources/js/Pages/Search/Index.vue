<template>
    <AppLayout>
        <template #breadcrumbs>
            <span class="text-sm text-gray-500 font-medium">Поиск</span>
        </template>

        <div class="max-w-3xl mx-auto p-6">
            <!-- Search input -->
            <div class="relative mb-6">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    v-model="searchQuery"
                    @input="debouncedSearch"
                    type="text"
                    class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl text-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                    placeholder="Поиск по вики..."
                    autofocus
                />
            </div>

            <!-- Results -->
            <div v-if="results.length" class="space-y-3">
                <p class="text-sm text-gray-500">Найдено {{ results.length }} результатов</p>

                <Link
                    v-for="result in results"
                    :key="result.id"
                    :href="route('pages.show', [result.space.slug, result.slug])"
                    class="block bg-white border border-gray-200 rounded-xl p-5 hover:border-indigo-300 hover:shadow-sm transition"
                >
                    <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
                        <span>{{ result.space.name }}</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-base mb-1" v-html="highlight(result.title)" />
                    <p v-if="result.excerpt" class="text-sm text-gray-500 line-clamp-2" v-html="highlight(result.excerpt)" />
                    <div v-if="result.tags?.length" class="flex gap-1.5 mt-2 flex-wrap">
                        <span
                            v-for="tag in result.tags"
                            :key="tag.id"
                            class="px-2 py-0.5 rounded-full text-xs"
                            :style="{ backgroundColor: tag.color + '33', color: tag.color }"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </Link>
            </div>

            <div v-else-if="query && query.length >= 2" class="text-center py-16 text-gray-400">
                <div class="text-4xl mb-3">🔍</div>
                <p>Ничего не найдено по запросу «{{ query }}»</p>
            </div>

            <div v-else-if="!query" class="text-center py-16 text-gray-400">
                <div class="text-4xl mb-3">💡</div>
                <p>Введите запрос для поиска по вики</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    query:   { type: String, default: '' },
    results: { type: Array,  default: () => [] },
})

const searchQuery = ref(props.query)

let timer = null
const debouncedSearch = () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get(route('search'), { q: searchQuery.value }, { preserveState: true, replace: true })
    }, 300)
}

const highlight = (text) => {
    if (!searchQuery.value) return text
    const escaped = searchQuery.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark class="bg-yellow-100 text-yellow-900 rounded px-0.5">$1</mark>')
}
</script>
