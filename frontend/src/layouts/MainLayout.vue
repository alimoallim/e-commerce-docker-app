<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const router = useRouter()
const route = useRoute()

const menu = [
  { name: 'Dashboard', path: '/dashboard', icon: '📊' },
  { name: 'Products', path: '/products', icon: '📦' },
  { name: 'Customers', path: '/customers', icon: '👥' },
  { name: 'Orders', path: '/orders', icon: '🧾' },
  { name: 'Payments', path: '/payments', icon: '💳' },
]

const go = (path) => {
  if (route.path !== path) router.push(path)
}

const getTitle = () => {
  const current = menu.find(item => item.path === route.path)
  return current ? current.name : 'Dashboard'
}

/**
 * Dark mode:
 * - Uses <html class="dark"> to enable Tailwind dark variants
 * - Reads user preference on load
 * - Persists in localStorage
 */
const isDark = ref(false)

const applyTheme = (dark) => {
  const root = document.documentElement
  if (dark) root.classList.add('dark')
  else root.classList.remove('dark')
}

onMounted(() => {
  const saved = localStorage.getItem('theme') // 'dark' | 'light' | null
  if (saved === 'dark') isDark.value = true
  else if (saved === 'light') isDark.value = false
  else isDark.value = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false

  applyTheme(isDark.value)
})

watch(isDark, (val) => {
  applyTheme(val)
  localStorage.setItem('theme', val ? 'dark' : 'light')
})

const toggleTheme = () => {
  isDark.value = !isDark.value
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 dark:bg-gray-950">
    <div class="flex min-h-screen">

      <!-- Sidebar -->
      <aside
        class="w-72 flex flex-col border-r
               border-gray-200 dark:border-gray-800
               bg-white dark:bg-gray-950 text-gray-900 dark:text-white"
        aria-label="Sidebar"
      >
        <!-- Logo / Brand -->
        <div
          class="px-5 py-4 flex items-center gap-3 border-b
                 border-gray-200 dark:border-gray-800"
        >
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center">
            <span class="text-lg">🏪</span>
          </div>

          <div class="leading-tight">
            <div class="text-sm text-gray-600 dark:text-gray-300">E-Commerce</div>
            <div class="text-base font-semibold tracking-tight">Admin Panel</div>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-4">
          <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 px-2 mb-3">
            Navigation
          </div>

          <div class="space-y-1">
            <button
              v-for="item in menu"
              :key="item.name"
              type="button"
              @click="go(item.path)"
              class="w-full flex items-center gap-3 px-3 py-3 rounded-xl cursor-pointer transition-colors duration-150
                     focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                     focus:ring-offset-white dark:focus:ring-offset-gray-950"
              :class="route.path === item.path
                ? 'bg-blue-600 text-white shadow-sm'
                : 'text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-900'"
              :aria-current="route.path === item.path ? 'page' : undefined"
            >
              <span class="text-lg w-7 text-center" aria-hidden="true">
                {{ item.icon }}
              </span>

              <span class="font-medium text-sm">
                {{ item.name }}
              </span>

              <span
                v-if="route.path === item.path"
                class="ml-auto text-xs font-semibold text-white/90"
              >
                Active
              </span>
            </button>
          </div>

          <div class="h-6" />
        </nav>

        <!-- Footer -->
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 text-sm text-gray-500 dark:text-gray-400">
          <div class="flex items-center justify-between gap-3">
            <span>© 2026 Admin Panel</span>
            <span class="text-gray-500 dark:text-gray-500">v1.0</span>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="flex-1">
        <!-- Header -->
        <header class="p-6">
          <div class="flex justify-between items-center gap-4">
            <div>
              <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ getTitle() }}
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Manage your store’s data and operations.
              </p>
            </div>

            <div class="flex items-center gap-3">
              <!-- Theme toggle -->
              <button
                type="button"
                @click="toggleTheme"
                class="hidden sm:inline-flex items-center gap-2 px-3 py-2 rounded-xl border
                       border-gray-200 dark:border-gray-800
                       bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200
                       hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                aria-label="Toggle dark mode"
              >
                <span class="text-lg" aria-hidden="true">{{ isDark ? '🌙' : '☀️' }}</span>
                <span class="text-sm font-medium">{{ isDark ? 'Dark' : 'Light' }}</span>
              </button>

              <!-- Admin / user -->
              <div class="flex items-center gap-3">
                <div class="hidden sm:block text-right">
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Admin</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Administrator</div>
                </div>

                <div
                  class="w-10 h-10 rounded-full flex items-center justify-center border
                         bg-gradient-to-br from-gray-200 to-gray-300
                         border-gray-300 dark:border-gray-700
                         dark:bg-gradient-to-br dark:from-gray-800 dark:to-gray-700"
                >
                  <span class="text-gray-800 dark:text-gray-100 font-semibold">A</span>
                </div>
              </div>
            </div>
          </div>
        </header>

        <!-- Content Card -->
        <section class="px-6 pb-10">
          <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border
                   border-gray-100 dark:border-gray-800 overflow-hidden"
          >
            <div class="p-6">
              <router-view />
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>