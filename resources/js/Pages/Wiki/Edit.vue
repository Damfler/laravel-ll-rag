<template>
    <Head><title>{{ page.title }} — Редактирование</title></Head>
    <AppLayout>
        <template #breadcrumbs>
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('spaces.show', space.slug)" class="hover:text-gray-700">{{ space.name }}</Link>
                <span>/</span>
                <Link :href="route('pages.show', [space.slug, page.slug])" class="hover:text-gray-700">{{ page.title }}</Link>
                <span>/</span>
                <span class="text-gray-900">Редактирование</span>
            </nav>
        </template>

        <div class="max-w-4xl mx-auto p-6">
            <!-- Баннер восстановления черновика -->
            <div
                v-if="draftBanner"
                class="mb-4 flex items-center gap-3 px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm"
            >
                <span class="text-yellow-700">Найден несохранённый черновик от {{ draftBanner }}</span>
                <button @click="restoreDraft" class="text-yellow-800 font-medium underline hover:no-underline">Восстановить</button>
                <button @click="discardDraft" class="text-gray-500 hover:text-gray-700 ml-auto">Удалить черновик</button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <input
                    v-model="form.title"
                    @input="autosave.touch"
                    type="text"
                    class="w-full text-3xl font-bold text-gray-900 border-0 border-b-2 border-gray-200 focus:border-indigo-500 focus:ring-0 px-0 pb-2 outline-none placeholder-gray-300"
                    placeholder="Заголовок страницы"
                />

                <div class="flex items-center gap-4 text-sm">
                    <div>
                        <label class="text-gray-500 mr-2">Родительская:</label>
                        <select v-model="form.parent_id" class="border border-gray-300 rounded-lg px-2 py-1 text-sm">
                            <option :value="null">— Корневая страница</option>
                            <option v-for="p in pages" :key="p.id" :value="p.id">{{ p.title }}</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-gray-500">
                        <input v-model="form.is_published" type="checkbox" class="rounded" />
                        Опубликовано
                    </label>
                    <span v-if="lastSaved" class="ml-auto text-xs text-gray-400">
                        Черновик сохранён {{ lastSaved }}
                    </span>
                </div>

                <TipTapEditor
                    v-model="form.content"
                    @update:modelValue="autosave.touch"
                    @update:text="form.content_text = $event"
                />

                <div>
                    <label class="form-label">Теги</label>
                    <TagInput v-model="form.tags" :available-tags="tags" />
                </div>

                <div>
                    <label class="form-label">Комментарий к правке (необязательно)</label>
                    <input
                        v-model="form.change_summary"
                        type="text"
                        class="form-input"
                        placeholder="Что изменили?"
                    />
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-gray-200">
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                    <Link :href="route('pages.show', [space.slug, page.slug])" class="btn-ghost">Отмена</Link>
                    <button
                        type="button"
                        class="ml-auto text-sm text-red-500 hover:text-red-700"
                        @click="deletePage"
                    >
                        Удалить страницу
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TipTapEditor from '@/Components/Editor/TipTapEditor.vue'
import TagInput from '@/Components/UI/TagInput.vue'
import { useAutosave } from '@/composables/useAutosave.js'

const props = defineProps({
    space: { type: Object, required: true },
    page:  { type: Object, required: true },
    pages: { type: Array,  default: () => [] },
    tags:  { type: Array,  default: () => [] },
})

const form = useForm({
    title:          props.page.title,
    content:        props.page.content,
    content_text:   props.page.content_text ?? '',
    parent_id:      props.page.parent_id,
    is_published:   props.page.is_published,
    tags:           props.page.tags?.map((t) => t.name) ?? [],
    change_summary: '',
})

const draftBanner = ref(null)
const lastSaved = ref(null)

const autosave = useAutosave(
    `edit_${props.space.slug}_${props.page.slug}`,
    form,
    ['title', 'content', 'content_text', 'parent_id', 'tags', 'change_summary'],
)

onMounted(() => {
    const draft = autosave.load()
    if (draft && draft.savedAt) {
        // Показываем баннер только если черновик отличается от текущей версии
        const saved = draft.data
        if (saved.title !== props.page.title || JSON.stringify(saved.content) !== JSON.stringify(props.page.content)) {
            draftBanner.value = new Date(draft.savedAt).toLocaleString('ru-RU')
        } else {
            autosave.clear()
        }
    }
})

const restoreDraft = () => {
    const draft = autosave.load()
    if (!draft) return
    Object.assign(form, draft.data)
    draftBanner.value = null
}

const discardDraft = () => {
    autosave.clear()
    draftBanner.value = null
}

const submit = () => {
    autosave.clear()
    form.put(route('pages.update', [props.space.slug, props.page.slug]))
}

const deletePage = () => {
    if (confirm('Удалить страницу? Это действие можно отменить через корзину.')) {
        autosave.clear()
        router.delete(route('pages.destroy', [props.space.slug, props.page.slug]))
    }
}
</script>
