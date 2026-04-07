<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('admin.users.index')" class="hover:text-gray-700">Пользователи</Link>
                <span>/</span>
                <span class="text-gray-900">{{ user.name }}</span>
            </nav>
        </template>

        <div class="max-w-lg mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Редактировать пользователя</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="form-label">Имя *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="form-input"
                        :class="{ 'border-red-400': form.errors.name }"
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
                    />
                    <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="form-label">Новый пароль <span class="text-gray-400 font-normal">(оставьте пустым, чтобы не менять)</span></label>
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
                        {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                    <Link :href="route('admin.users.index')" class="btn-ghost">Отмена</Link>
                </div>
            </form>

            <!-- Опасная зона -->
            <div v-if="user.id !== $page.props.auth.user.id" class="mt-10 pt-6 border-t border-red-100">
                <h2 class="text-base font-semibold text-red-600 mb-3">Опасная зона</h2>
                <button
                    type="button"
                    class="px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition"
                    @click="confirmDelete"
                >
                    Удалить пользователя
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    user:  Object,
    roles: Array,
})

const form = useForm({
    name:     props.user.name,
    email:    props.user.email,
    password: '',
    role:     props.user.default_role,
})

const submit = () => {
    form.put(route('admin.users.update', props.user.id))
}

const confirmDelete = () => {
    if (confirm(`Удалить пользователя «${props.user.name}»?`)) {
        router.delete(route('admin.users.destroy', props.user.id))
    }
}
</script>
