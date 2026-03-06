<template>
    <div>
        <Link
            :href="route('spaces.show', space.slug)"
            class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition group"
        >
            <span class="text-base leading-none">{{ space.icon }}</span>
            <span class="flex-1 truncate">{{ space.name }}</span>
            <span class="text-xs text-gray-400 group-hover:text-gray-600">{{ space.pages_count }}</span>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    space: {
        type: Object,
        required: true,
    },
})
</script>
