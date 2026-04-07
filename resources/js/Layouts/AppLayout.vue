<template>
    <div class="flex h-screen bg-gray-50 overflow-hidden">

        <!-- Sidebar -->
        <aside
            class="bg-white border-r border-gray-200 flex flex-col flex-shrink-0 overflow-hidden transition-[width] duration-200 ease-in-out"
            :style="{ width: collapsed ? '3.5rem' : '15rem' }"
        >
            <!-- Logo -->
            <div class="flex items-center h-16 border-b border-gray-200 flex-shrink-0 px-3">
                <template v-if="!collapsed">
                    <img :src="logoFull" alt="Rosberg Wiki" class="h-7 w-auto object-contain flex-shrink-0" />
                    <span class="ml-2 font-semibold text-gray-600 text-sm whitespace-nowrap">Rosberg Wiki</span>
                </template>
                <template v-else>
                    <img :src="logoFull" alt="R" class="h-6 w-full object-contain" />
                </template>
            </div>

            <!-- Search -->
            <div class="px-2 py-2 flex-shrink-0">
                <button
                    type="button"
                    @click="goSearch"
                    class="flex items-center gap-2 w-full rounded-lg text-sm text-gray-500 bg-gray-100 hover:bg-gray-200 transition"
                    :class="collapsed ? 'justify-center p-2' : 'px-3 py-2'"
                    title="Поиск (Ctrl+K)"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <template v-if="!collapsed">
                        <span>Поиск...</span>
                        <kbd class="ml-auto text-xs bg-white border border-gray-300 rounded px-1.5">⌃K</kbd>
                    </template>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-2 py-1 space-y-0.5">
                <Link
                    :href="route('spaces.index')"
                    class="sidebar-link"
                    :class="[{ active: route().current('spaces.index') }, collapsed && 'justify-center !px-0']"
                    title="Все разделы"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span v-if="!collapsed" class="truncate">Все разделы</span>
                </Link>

                <!-- Spaces -->
                <template v-if="spaces?.length">
                    <p v-if="!collapsed" class="px-2 pt-3 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Разделы</p>
                    <div v-else class="border-t border-gray-100 my-1" />

                    <template v-if="!collapsed">
                        <SidebarSpaceItem v-for="space in spaces" :key="space.id" :space="space" />
                    </template>
                    <template v-else>
                        <Link
                            v-for="space in spaces"
                            :key="space.id"
                            :href="route('spaces.show', space.slug)"
                            class="sidebar-link justify-center !px-0"
                            :title="space.name"
                        >
                            <span class="text-base leading-none">{{ space.icon ?? '📄' }}</span>
                        </Link>
                    </template>
                </template>

                <!-- Admin -->
                <template v-if="isAdmin">
                    <div class="border-t border-gray-100 my-1" />
                    <p v-if="!collapsed" class="px-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Администрирование</p>
                    <Link
                        :href="route('admin.users.index')"
                        class="sidebar-link"
                        :class="[{ active: route().current('admin.users.*') }, collapsed && 'justify-center !px-0']"
                        title="Пользователи"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span v-if="!collapsed" class="truncate">Пользователи</span>
                    </Link>
                    <Link
                        :href="route('admin.groups.index')"
                        class="sidebar-link"
                        :class="[{ active: route().current('admin.groups.*') }, collapsed && 'justify-center !px-0']"
                        title="Группы доступа"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span v-if="!collapsed" class="truncate">Группы доступа</span>
                    </Link>
                </template>
            </nav>

            <!-- User -->
            <div class="border-t border-gray-200 p-2 flex-shrink-0">
                <div class="flex items-center gap-2" :class="collapsed && 'justify-center'">
                    <div
                        class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium flex-shrink-0 cursor-default"
                        :title="$page.props.auth.user.name"
                    >
                        {{ userInitials }}
                    </div>
                    <template v-if="!collapsed">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.default_role }}</p>
                        </div>
                        <Link :href="route('profile.edit')" class="text-gray-400 hover:text-gray-600 flex-shrink-0" title="Профиль">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </Link>
                    </template>
                </div>
            </div>
        </aside>

        <!-- Main area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top header -->
            <header class="flex items-center gap-3 px-4 h-16 bg-white border-b border-gray-200 flex-shrink-0">
                <button
                    type="button"
                    @click="toggleSidebar"
                    class="text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg p-1.5 transition flex-shrink-0"
                    :title="collapsed ? 'Развернуть меню' : 'Свернуть меню'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <div class="flex-1 min-w-0">
                    <slot name="breadcrumbs" />
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <slot name="actions" />
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto relative">
                <slot />
            </main>
        </div>

        <!-- Toast notifications (поверх контента, не сдвигают layout) -->
        <Transition name="toast">
            <div
                v-if="toastMessage"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium max-w-sm"
                :class="toastType === 'success'
                    ? 'bg-green-600 text-white'
                    : 'bg-red-600 text-white'"
            >
                <svg v-if="toastType === 'success'" class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <svg v-else class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ toastMessage }}
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import SidebarSpaceItem from '@/Components/Sidebar/SpaceItem.vue'
import logoFull from '../../img/rosberg_logo.png'

const STORAGE_KEY = 'rosberg_wiki_sidebar_collapsed'
const collapsed = ref(localStorage.getItem(STORAGE_KEY) === 'true')

const toggleSidebar = () => {
    collapsed.value = !collapsed.value
    localStorage.setItem(STORAGE_KEY, String(collapsed.value))
}

// ─── Ctrl+K → поиск ──────────────────────────────────────────────────────────
const goSearch = () => router.visit(route('search'))

const onKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault()
        e.stopPropagation()
        goSearch()
    }
}

// capture:true — перехватываем до браузера и других обработчиков
onMounted(() => window.addEventListener('keydown', onKeydown, true))
onUnmounted(() => window.removeEventListener('keydown', onKeydown, true))

// ─── Toast ────────────────────────────────────────────────────────────────────
const toastMessage = ref(null)
const toastType = ref('success')
let toastTimer = null

const showToast = (msg, type = 'success') => {
    toastMessage.value = msg
    toastType.value = type
    clearTimeout(toastTimer)
    toastTimer = setTimeout(() => { toastMessage.value = null }, 3500)
}

const page = usePage()
const flash = computed(() => page.props.flash ?? {})

watch(flash, (f) => {
    if (f.success) showToast(f.success, 'success')
    if (f.error)   showToast(f.error, 'error')
}, { immediate: true, deep: true })

// ─── Данные ───────────────────────────────────────────────────────────────────
const spaces  = computed(() => page.props.spaces ?? [])
const isAdmin = computed(() => page.props.auth?.user?.default_role === 'admin')

const userInitials = computed(() => {
    const name = page.props.auth?.user?.name ?? ''
    return name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase()
})
</script>

<style scoped>
.sidebar-link {
    @apply flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition cursor-pointer w-full;
}
.sidebar-link.active {
    @apply bg-indigo-50 text-indigo-700 font-medium;
}

.toast-enter-active, .toast-leave-active { transition: all .25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px); }
</style>
