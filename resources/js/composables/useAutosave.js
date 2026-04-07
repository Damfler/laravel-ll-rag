import { onMounted, onUnmounted } from 'vue'

/**
 * Автосохранение черновика в localStorage.
 *
 * @param {string} key     - уникальный ключ (напр. 'create_my-space' или 'edit_my-space_page-slug')
 * @param {object} formRef - объект с данными (useForm или ref)
 * @param {string[]} fields - поля которые сохранять/восстанавливать
 */
export function useAutosave(key, formRef, fields) {
    const storageKey = `wiki_draft_${key}`
    let autoTimer = null
    let debounceTimer = null

    const save = () => {
        const data = {}
        for (const f of fields) {
            data[f] = formRef[f]
        }
        try {
            localStorage.setItem(storageKey, JSON.stringify({ data, savedAt: new Date().toISOString() }))
        } catch {
            // localStorage может быть недоступен (private mode и т.д.)
        }
    }

    const load = () => {
        try {
            const raw = localStorage.getItem(storageKey)
            return raw ? JSON.parse(raw) : null
        } catch {
            return null
        }
    }

    const clear = () => {
        localStorage.removeItem(storageKey)
    }

    const hasDraft = () => {
        try {
            return !!localStorage.getItem(storageKey)
        } catch {
            return false
        }
    }

    // Debounced save — вызывается при изменении контента
    const touch = () => {
        clearTimeout(debounceTimer)
        debounceTimer = setTimeout(save, 3000)
    }

    onMounted(() => {
        // Автосохранение каждые 30 секунд
        autoTimer = setInterval(save, 30_000)
    })

    onUnmounted(() => {
        clearInterval(autoTimer)
        clearTimeout(debounceTimer)
    })

    return { save, load, clear, hasDraft, touch }
}
