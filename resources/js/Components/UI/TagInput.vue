<template>
    <div class="border border-gray-300 rounded-lg px-3 py-2 flex flex-wrap gap-2 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 min-h-[42px]">
        <!-- Selected tags -->
        <span
            v-for="tag in modelValue"
            :key="tag"
            class="flex items-center gap-1 bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full"
        >
            {{ tag }}
            <button type="button" @click="remove(tag)" class="hover:text-indigo-900 leading-none">&times;</button>
        </span>

        <!-- Input -->
        <input
            v-model="inputValue"
            @keydown.enter.prevent="add"
            @keydown.backspace="onBackspace"
            @input="showSuggestions = true"
            type="text"
            class="flex-1 min-w-24 outline-none text-sm bg-transparent"
            placeholder="Добавить тег..."
        />

        <!-- Suggestions -->
        <div
            v-if="showSuggestions && filtered.length"
            class="absolute mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 py-1 min-w-48"
        >
            <button
                v-for="tag in filtered"
                :key="tag.id"
                type="button"
                @click="selectSuggestion(tag.name)"
                class="w-full text-left px-3 py-1.5 text-sm hover:bg-gray-100"
            >
                {{ tag.name }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    modelValue:    { type: Array, default: () => [] },
    availableTags: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const inputValue   = ref('')
const showSuggestions = ref(false)

const filtered = computed(() =>
    props.availableTags.filter(
        (t) =>
            t.name.toLowerCase().includes(inputValue.value.toLowerCase()) &&
            !props.modelValue.includes(t.name),
    ).slice(0, 8),
)

const add = () => {
    const val = inputValue.value.trim()
    if (val && !props.modelValue.includes(val)) {
        emit('update:modelValue', [...props.modelValue, val])
    }
    inputValue.value = ''
    showSuggestions.value = false
}

const remove = (tag) => {
    emit('update:modelValue', props.modelValue.filter((t) => t !== tag))
}

const onBackspace = () => {
    if (!inputValue.value && props.modelValue.length) {
        emit('update:modelValue', props.modelValue.slice(0, -1))
    }
}

const selectSuggestion = (name) => {
    if (!props.modelValue.includes(name)) {
        emit('update:modelValue', [...props.modelValue, name])
    }
    inputValue.value = ''
    showSuggestions.value = false
}
</script>
