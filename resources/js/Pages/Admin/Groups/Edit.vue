<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('admin.groups.index')" class="hover:text-gray-700">Группы</Link>
                <span>/</span>
                <span class="text-gray-900">{{ group.name }}</span>
            </nav>
        </template>

        <div class="max-w-2xl mx-auto p-6 space-y-8">
            <!-- Основная информация -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ group.name }}</h1>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="form-label">Название *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="form-input"
                            :class="{ 'border-red-400': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="form-label">Описание</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="form-input resize-none"
                        />
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="btn-primary" :disabled="form.processing">
                            {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Участники -->
            <div>
                <h2 class="text-base font-semibold text-gray-900 mb-1">Участники группы</h2>
                <p class="text-sm text-gray-500 mb-4">Выберите пользователей, которые входят в эту группу.</p>

                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <!-- Поиск -->
                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                        <input
                            v-model="search"
                            type="text"
                            class="form-input text-sm"
                            placeholder="Поиск пользователей..."
                        />
                    </div>
                    <!-- Список -->
                    <div class="max-h-72 overflow-y-auto divide-y divide-gray-100">
                        <label
                            v-for="user in filteredUsers"
                            :key="user.id"
                            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="user.id"
                                v-model="form.user_ids"
                                class="w-4 h-4 text-indigo-600 rounded"
                            />
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-medium text-gray-900">{{ user.name }}</span>
                                <span class="text-xs text-gray-400 ml-2">{{ user.email }}</span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full" :class="roleClass(user.default_role)">
                                {{ roleLabel(user.default_role) }}
                            </span>
                        </label>
                        <div v-if="filteredUsers.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
                            Пользователи не найдены
                        </div>
                    </div>
                    <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 text-xs text-gray-400">
                        Выбрано: {{ form.user_ids.length }} из {{ allUsers.length }}
                    </div>
                </div>
            </div>

            <!-- Опасная зона -->
            <div class="pt-4 border-t border-red-100">
                <h2 class="text-base font-semibold text-red-600 mb-3">Опасная зона</h2>
                <button
                    type="button"
                    class="px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition"
                    @click="confirmDelete"
                >
                    Удалить группу
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    group:    Object,
    allUsers: Array,
})

const search = ref('')

const form = useForm({
    name:        props.group.name,
    description: props.group.description ?? '',
    user_ids:    props.group.users.map((u) => u.id),
})

const filteredUsers = computed(() =>
    props.allUsers.filter((u) =>
        u.name.toLowerCase().includes(search.value.toLowerCase()) ||
        u.email.toLowerCase().includes(search.value.toLowerCase())
    )
)

const roleLabel = (role) => ({ admin: 'Администратор', editor: 'Редактор', viewer: 'Читатель' }[role] ?? role)

const roleClass = (role) => ({
    admin:  'bg-red-100 text-red-700',
    editor: 'bg-indigo-100 text-indigo-700',
    viewer: 'bg-gray-100 text-gray-600',
}[role] ?? 'bg-gray-100 text-gray-600')

const submit = () => {
    form.put(route('admin.groups.update', props.group.id))
}

const confirmDelete = () => {
    if (confirm(`Удалить группу «${props.group.name}»?`)) {
        router.delete(route('admin.groups.destroy', props.group.id))
    }
}
</script>
