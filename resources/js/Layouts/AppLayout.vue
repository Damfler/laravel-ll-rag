<template>
    <div class="flex h-screen bg-gray-50 overflow-hidden">

        <!-- Sidebar -->
        <aside
            class="w-64 bg-white border-r border-gray-200 flex flex-col flex-shrink-0 transition-all duration-200"
            :class="{ '-translate-x-full absolute z-20 h-full': !sidebarOpen }"
        >
            <!-- Logo -->
            <div class="flex items-center gap-2 px-4 py-4 border-b border-gray-200">
                <span class="text-2xl">📚</span>
                <span class="font-bold text-gray-900 text-lg">Company Wiki</span>
            </div>

            <!-- Search shortcut -->
            <div class="px-3 py-2">
                <Link
                    :href="route('search')"
                    class="flex items-center gap-2 w-full px-3 py-2 rounded-lg text-sm text-gray-500 bg-gray-100 hover:bg-gray-200 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Поиск...
                    <kbd class="ml-auto text-xs bg-white border border-gray-300 rounded px-1">Ctrl K</kbd>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1">
                <Link
                    :href="route('spaces.index')"
                    class="sidebar-link"
                    :class="{ active: route().current('spaces.index') }"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Все разделы
                </Link>

                <!-- Spaces tree -->
                <div v-if="spaces?.length" class="mt-2">
                    <p class="px-2 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Разделы</p>
                    <SidebarSpaceItem
                        v-for="space in spaces"
                        :key="space.id"
                        :space="space"
                    />
                </div>
            </nav>

            <!-- User -->
            <div class="border-t border-gray-200 p-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium">
                        {{ userInitials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.default_role }}</p>
                    </div>
                    <Link :href="route('profile.edit')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top header -->
            <header class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 flex-shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 lg:hidden">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <!-- Breadcrumbs slot -->
                <slot name="breadcrumbs" />

                <div class="flex items-center gap-2 ml-auto">
                    <slot name="actions" />
                </div>
            </header>

            <!-- Flash messages -->
            <div v-if="flash.success" class="mx-4 mt-3 px-4 py-2 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ flash.success }}
            </div>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import SidebarSpaceItem from '@/Components/Sidebar/SpaceItem.vue'

defineProps({
    spaces: {
        type: Array,
        default: () => [],
    },
})

const sidebarOpen = ref(true)
const page = usePage()

const flash = computed(() => page.props.flash ?? {})

const userInitials = computed(() => {
    const name = page.props.auth?.user?.name ?? ''
    return name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase()
})
</script>

<style scoped>
.sidebar-link {
    @apply flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition cursor-pointer;
}
.sidebar-link.active {
    @apply bg-indigo-50 text-indigo-700 font-medium;
}
</style>
