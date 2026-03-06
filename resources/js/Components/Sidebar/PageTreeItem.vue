<template>
    <div>
        <Link
            :href="route('pages.show', [space.slug, page.slug])"
            class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition group"
            :style="{ paddingLeft: `${(page.depth ?? 0) * 12 + 8}px` }"
        >
            <button
                v-if="page.children?.length"
                @click.prevent="open = !open"
                class="w-4 h-4 flex items-center justify-center text-gray-400 hover:text-gray-700 flex-shrink-0"
            >
                <span class="transition-transform" :class="{ 'rotate-90': open }">▶</span>
            </button>
            <span v-else class="w-4 h-4 flex-shrink-0" />
            <span class="truncate">{{ page.title }}</span>
        </Link>

        <div v-if="open && page.children?.length">
            <PageTreeItem
                v-for="child in page.children"
                :key="child.id"
                :page="child"
                :space="space"
            />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    page: { type: Object, required: true },
    space: { type: Object, required: true },
})

const open = ref(true)
</script>
