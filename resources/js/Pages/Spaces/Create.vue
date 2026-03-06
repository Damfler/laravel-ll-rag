<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('spaces.index')" class="hover:text-gray-700">Разделы</Link>
                <span>/</span>
                <span class="text-gray-900">Новый раздел</span>
            </nav>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Новый раздел</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Иконка + Название -->
                <div class="flex gap-3">
                    <div class="w-20">
                        <label class="form-label">Иконка</label>
                        <input
                            v-model="form.icon"
                            type="text"
                            maxlength="2"
                            class="form-input text-center text-2xl"
                            placeholder="📁"
                        />
                    </div>
                    <div class="flex-1">
                        <label class="form-label">Название *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="form-input"
                            :class="{ 'border-red-400': form.errors.name }"
                            placeholder="Разработка, HR, Маркетинг..."
                            autofocus
                        />
                        <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
                    </div>
                </div>

                <!-- Описание -->
                <div>
                    <label class="form-label">Описание</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="form-input resize-none"
                        placeholder="О чём этот раздел?"
                    />
                </div>

                <!-- Цвет -->
                <div>
                    <label class="form-label">Цвет</label>
                    <div class="flex items-center gap-3">
                        <input v-model="form.color" type="color" class="w-10 h-10 rounded cursor-pointer border border-gray-300" />
                        <div class="flex gap-2">
                            <button
                                v-for="color in presetColors"
                                :key="color"
                                type="button"
                                @click="form.color = color"
                                class="w-7 h-7 rounded-full border-2 transition"
                                :class="form.color === color ? 'border-gray-800 scale-110' : 'border-transparent'"
                                :style="{ backgroundColor: color }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Приватный -->
                <div class="flex items-center gap-3">
                    <input
                        id="is_public"
                        v-model="form.is_public"
                        type="checkbox"
                        class="w-4 h-4 text-indigo-600 rounded"
                    />
                    <label for="is_public" class="text-sm text-gray-700">
                        Публичный (доступен всем пользователям)
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Создание...' : 'Создать раздел' }}
                    </button>
                    <Link :href="route('spaces.index')" class="btn-ghost">Отмена</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const presetColors = ['#6366f1', '#8b5cf6', '#ec4899', '#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4']

const form = useForm({
    name: '',
    description: '',
    icon: '📁',
    color: '#6366f1',
    is_public: true,
})

const submit = () => {
    form.post(route('spaces.store'))
}
</script>
