<template>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('spaces.show', space.slug)" class="hover:text-gray-700">{{ space.name }}</Link>
                <span>/</span>
                <span class="text-gray-900">Новая страница</span>
            </nav>
        </template>

        <div class="max-w-4xl mx-auto p-6">
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Title -->
                <input
                    v-model="form.title"
                    type="text"
                    class="w-full text-3xl font-bold text-gray-900 border-0 border-b-2 border-gray-200 focus:border-indigo-500 focus:ring-0 px-0 pb-2 outline-none placeholder-gray-300"
                    placeholder="Заголовок страницы"
                    autofocus
                />
                <p v-if="form.errors.title" class="form-error">{{ form.errors.title }}</p>

                <!-- Options row -->
                <div class="flex items-center gap-4 text-sm">
                    <div>
                        <label class="text-gray-500 mr-2">Родительская:</label>
                        <select v-model="form.parent_id" class="border border-gray-300 rounded-lg px-2 py-1 text-sm">
                            <option :value="null">— Корневая страница</option>
                            <option v-for="p in pages" :key="p.id" :value="p.id">
                                {{ '·'.repeat(p.depth ?? 0) }} {{ p.title }}
                            </option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-gray-500">
                        <input v-model="form.is_published" type="checkbox" class="rounded" />
                        Опубликовать
                    </label>
                </div>

                <!-- Editor -->
                <TipTapEditor
                    v-model="form.content"
                    @update:text="form.content_text = $event"
                    placeholder="Начните писать содержимое страницы..."
                />

                <!-- Tags -->
                <div>
                    <label class="form-label">Теги</label>
                    <TagInput v-model="form.tags" :available-tags="tags" />
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2 border-t border-gray-200">
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Сохранение...' : 'Создать страницу' }}
                    </button>
                    <Link :href="route('spaces.show', space.slug)" class="btn-ghost">Отмена</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TipTapEditor from '@/Components/Editor/TipTapEditor.vue'
import TagInput from '@/Components/UI/TagInput.vue'

const props = defineProps({
    space: { type: Object, required: true },
    pages: { type: Array,  default: () => [] },
    tags:  { type: Array,  default: () => [] },
})

const form = useForm({
    title:        '',
    content:      null,
    content_text: '',
    parent_id:    null,
    is_published: true,
    tags:         [],
})

const submit = () => {
    form.post(route('pages.store', props.space.slug))
}
</script>
