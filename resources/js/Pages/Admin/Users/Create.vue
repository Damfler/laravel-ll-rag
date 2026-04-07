<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('admin.users.index')" class="hover:text-gray-700">Пользователи</Link>
                <span>/</span>
                <span class="text-gray-900">Новый пользователь</span>
            </nav>
        </template>

        <div class="max-w-lg mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Новый пользователь</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="form-label">Имя *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="form-input"
                        :class="{ 'border-red-400': form.errors.name }"
                        placeholder="Иван Иванов"
                        autofocus
                    />
                    <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="form-label">Email *</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="form-input"
                        :class="{ 'border-red-400': form.errors.email }"
                        placeholder="user@example.com"
                    />
                    <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="form-label">Пароль *</label>
                    <input
                        v-model="form.password"
                        type="password"
                        class="form-input"
                        :class="{ 'border-red-400': form.errors.password }"
                        placeholder="Минимум 8 символов"
                    />
                    <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="form-label">Роль *</label>
                    <select
                        v-model="form.role"
                        class="form-input"
                        :class="{ 'border-red-400': form.errors.role }"
                    >
                        <option v-for="role in roles" :key="role.name" :value="role.name">
                            {{ role.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.role" class="form-error">{{ form.errors.role }}</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Создание...' : 'Создать пользователя' }}
                    </button>
                    <Link :href="route('admin.users.index')" class="btn-ghost">Отмена</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    roles: Array,
})

const form = useForm({
    name:     '',
    email:    '',
    password: '',
    role:     'viewer',
})

const submit = () => {
    form.post(route('admin.users.store'))
}
</script>
