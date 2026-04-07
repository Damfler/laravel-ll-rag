<template>
    <Head><title>Все разделы</title></Head>
    <AppLayout>
        <template #breadcrumbs>
            <span class="text-sm text-gray-500 font-medium">Все разделы</span>
        </template>
        <template #actions>
            <Link
                v-if="$page.props.auth.user.default_role !== 'viewer'"
                :href="route('spaces.create')"
                class="btn-primary"
            >
                + Новый раздел
            </Link>
        </template>

        <div class="p-6">
            <div v-if="spaces.length === 0" class="text-center py-20 text-gray-400">
                <div class="text-5xl mb-3">📭</div>
                <p class="text-lg font-medium">Разделов пока нет</p>
                <p class="text-sm mt-1">Создайте первый раздел для вашей вики</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="space in spaces"
                    :key="space.id"
                    :href="route('spaces.show', space.slug)"
                    class="group block bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="w-10 h-10 rounded-lg flex items-center justify-center text-xl flex-shrink-0"
                            :style="{ backgroundColor: space.color + '22', borderColor: space.color + '44', borderWidth: '1px' }"
                        >
                            {{ space.icon }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700 truncate">
                                {{ space.name }}
                            </h3>
                            <p v-if="space.description" class="text-sm text-gray-500 mt-0.5 line-clamp-2">
                                {{ space.description }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-4 text-xs text-gray-400">
                        <span>{{ space.pages_count }} стр.</span>
                        <span v-if="space.visibility === 'restricted'" class="flex items-center gap-1">🔒 Группы</span>
                        <span v-else-if="space.visibility === 'private'" class="flex items-center gap-1">🔒 Приватный</span>
                        <span class="ml-auto">{{ space.owner.name }}</span>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    spaces: {
        type: Array,
        required: true,
    },
})
</script>
