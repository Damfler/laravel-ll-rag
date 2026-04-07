<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('spaces.index')" class="hover:text-gray-700">Разделы</Link>
                <span>/</span>
                <Link :href="route('spaces.show', space.slug)" class="hover:text-gray-700">{{ space.name }}</Link>
                <span>/</span>
                <span class="text-gray-900">Настройки</span>
            </nav>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Настройки раздела</h1>

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

                <!-- Доступ -->
                <div>
                    <label class="form-label">Доступ</label>
                    <div class="space-y-2">
                        <label v-for="opt in visibilityOptions" :key="opt.value"
                            class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition"
                            :class="form.visibility === opt.value
                                ? 'border-indigo-400 bg-indigo-50'
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                :value="opt.value"
                                v-model="form.visibility"
                                class="mt-0.5 text-indigo-600"
                            />
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ opt.label }}</p>
                                <p class="text-xs text-gray-500">{{ opt.hint }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Группы (только для restricted) -->
                <div v-if="form.visibility === 'restricted' && groups.length">
                    <label class="form-label">Группы с доступом</label>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <label
                            v-for="group in groups"
                            :key="group.id"
                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                        >
                            <input
                                type="checkbox"
                                :value="group.id"
                                v-model="form.group_ids"
                                class="w-4 h-4 text-indigo-600 rounded"
                            />
                            <span class="text-sm text-gray-900">{{ group.name }}</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                    <Link :href="route('spaces.show', space.slug)" class="btn-ghost">Отмена</Link>
                </div>
            </form>

            <!-- Опасная зона -->
            <div class="mt-10 pt-6 border-t border-red-100">
                <h2 class="text-base font-semibold text-red-600 mb-3">Опасная зона</h2>
                <p class="text-sm text-gray-500 mb-4">Удаление раздела приведёт к удалению всех его страниц.</p>
                <button
                    type="button"
                    class="px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition"
                    @click="confirmDelete"
                >
                    Удалить раздел
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    space:  Object,
    groups: { type: Array, default: () => [] },
})

const presetColors = ['#6366f1', '#8b5cf6', '#ec4899', '#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4']

const visibilityOptions = [
    { value: 'public',     label: 'Публичный',    hint: 'Виден всем авторизованным пользователям' },
    { value: 'restricted', label: 'Ограниченный', hint: 'Только участники выбранных групп (+ владелец и админы)' },
    { value: 'private',    label: 'Приватный',    hint: 'Только вы и администраторы' },
]

const form = useForm({
    name:        props.space.name,
    description: props.space.description ?? '',
    icon:        props.space.icon ?? '📁',
    color:       props.space.color ?? '#6366f1',
    visibility:  props.space.visibility ?? 'public',
    group_ids:   props.space.groups?.map((g) => g.id) ?? [],
})

const submit = () => {
    form.put(route('spaces.update', props.space.slug))
}

const confirmDelete = () => {
    if (confirm(`Удалить раздел «${props.space.name}»? Все страницы будут удалены.`)) {
        router.delete(route('spaces.destroy', props.space.slug))
    }
}
</script>
