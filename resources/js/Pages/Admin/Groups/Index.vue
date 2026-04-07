<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <span class="text-gray-900 font-medium">Администрирование</span>
                <span>/</span>
                <span class="text-gray-900">Группы доступа</span>
            </nav>
        </template>
        <template #actions>
            <Link :href="route('admin.groups.create')" class="btn-primary">
                + Новая группа
            </Link>
        </template>

        <div class="max-w-3xl mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Группы доступа</h1>
            <p class="text-sm text-gray-500 mb-6">
                Группы позволяют ограничить доступ к разделам для отдельных направлений или отделов.
            </p>

            <div class="space-y-3">
                <div
                    v-for="group in groups"
                    :key="group.id"
                    class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4"
                >
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm flex-shrink-0">
                        {{ group.name.slice(0, 2).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900">{{ group.name }}</p>
                        <p v-if="group.description" class="text-sm text-gray-500 truncate">{{ group.description }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ group.users_count }} участн.</p>
                    </div>
                    <Link :href="route('admin.groups.edit', group.id)" class="btn-ghost text-sm">
                        Настроить
                    </Link>
                </div>

                <div v-if="groups.length === 0" class="text-center py-16 text-gray-400">
                    <div class="text-4xl mb-3">👥</div>
                    <p class="font-medium">Групп пока нет</p>
                    <p class="text-sm mt-1">Создайте группу и добавьте участников</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    groups: Array,
})
</script>
