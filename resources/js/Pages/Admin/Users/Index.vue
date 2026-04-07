<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <span class="text-gray-900 font-medium">Администрирование</span>
                <span>/</span>
                <span class="text-gray-900">Пользователи</span>
            </nav>
        </template>
        <template #actions>
            <Link :href="route('admin.users.create')" class="btn-primary">
                + Новый пользователь
            </Link>
        </template>

        <div class="max-w-5xl mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Пользователи</h1>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Пользователь</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Email</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Роль</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Зарегистрирован</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b border-gray-100 hover:bg-gray-50 transition"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                        {{ initials(user.name) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ user.name }}</span>
                                    <span v-if="user.id === $page.props.auth.user.id" class="text-xs text-gray-400">(вы)</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="roleClass(user.default_role)">
                                    {{ roleLabel(user.default_role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ formatDate(user.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('admin.users.edit', user.id)" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    Изменить
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Пользователи не найдены</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div v-if="users.last_page > 1" class="flex justify-center gap-1 mt-4">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="px-3 py-1.5 rounded-lg text-sm border transition"
                    :class="link.active
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    users: Object,
    roles: Array,
})

const initials = (name) =>
    name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase()

const roleLabel = (role) => ({ admin: 'Администратор', editor: 'Редактор', viewer: 'Читатель' }[role] ?? role)

const roleClass = (role) => ({
    admin:  'bg-red-100 text-red-700',
    editor: 'bg-indigo-100 text-indigo-700',
    viewer: 'bg-gray-100 text-gray-600',
}[role] ?? 'bg-gray-100 text-gray-600')

const formatDate = (date) =>
    new Date(date).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' })
</script>
