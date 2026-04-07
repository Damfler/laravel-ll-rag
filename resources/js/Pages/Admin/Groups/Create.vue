<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('admin.groups.index')" class="hover:text-gray-700">Группы</Link>
                <span>/</span>
                <span class="text-gray-900">Новая группа</span>
            </nav>
        </template>

        <div class="max-w-lg mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Новая группа</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="form-label">Название *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="form-input"
                        :class="{ 'border-red-400': form.errors.name }"
                        placeholder="Направление 1, Маркетинг..."
                        autofocus
                    />
                    <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="form-label">Описание</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="form-input resize-none"
                        placeholder="Для чего эта группа?"
                    />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Создание...' : 'Создать группу' }}
                    </button>
                    <Link :href="route('admin.groups.index')" class="btn-ghost">Отмена</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const form = useForm({
    name:        '',
    description: '',
})

const submit = () => {
    form.post(route('admin.groups.store'))
}
</script>
