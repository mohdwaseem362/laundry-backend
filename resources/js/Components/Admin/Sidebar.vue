<template>
  <div class="flex">
    <!-- desktop -->
    <aside class="hidden md:flex md:flex-col md:w-56 md:h-screen md:sticky md:top-0 bg-white border-r border-gray-100">
      <div class="px-4 py-6 h-full flex flex-col">
        <div class="mb-6">
          <div class="text-base font-semibold text-gray-900">Laundry Admin</div>
        </div>

        <nav class="flex-1 space-y-1">
          <SidebarItem
            v-for="item in items"
            :key="item.name"
            :label="item.label"
            :icon="item.icon"
            :href="item.href"
            :exact="item.exact"
          />
        </nav>

        <div class="mt-6 pt-4 border-t border-gray-100">
          <button @click="logout" class="w-full text-left text-sm text-red-600 hover:text-red-800">
            <span class="inline-block mr-2" v-html="logoutIcon"></span> Logout
          </button>
        </div>
      </div>
    </aside>

    <!-- mobile -->
    <div class="md:hidden w-full bg-white border-b">
      <div class="px-4 py-3 flex items-center justify-between">
        <div class="text-base font-medium">Admin</div>
        <button @click="show = !show" class="p-2 rounded-md hover:bg-gray-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path v-if="!show" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <transition name="slide">
        <div v-if="show" class="px-3 pb-4">
          <nav class="space-y-1">
            <SidebarItem
              v-for="item in items"
              :key="item.name"
              :label="item.label"
              :icon="item.icon"
              :href="item.href"
              :exact="item.exact"
              @click.native="show = false"
            />
            <button @click="logout" class="w-full text-left text-sm text-red-600 hover:text-red-800 mt-2">
              <span class="inline-block mr-2" v-html="logoutIcon"></span> Logout
            </button>
          </nav>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Inertia } from '@inertiajs/inertia'
import SidebarItem from '@/Components/Admin/SidebarItem.vue'

const show = ref(false)

const items = [
  { name: 'dashboard', label: 'Dashboard', href: routeSafe('dashboard', '/dashboard'), exact: true, icon: dashboardIcon() },
  { name: 'orders', label: 'Orders', href: routeSafe('admin.orders.index', '/admin/orders'), exact: false, icon: ordersIcon() },
  { name: 'customers', label: 'Customers', href: routeSafe('admin.customers.index', '/admin/customers'), exact: false, icon: usersIcon() },
  { name: 'agents', label: 'Agents', href: routeSafe('admin.agents.index', '/admin/agents'), exact: false, icon: truckIcon() },
  { name: 'services', label: 'Services', href: routeSafe('admin.services.index', '/admin/services'), exact: false, icon: tagIcon() },
  { name: 'reports', label: 'Reports', href: routeSafe('admin.reports.index', '/admin/reports'), exact: false, icon: chartIcon() },
  { name: 'settings', label: 'Settings', href: routeSafe('admin.settings', '/admin/settings'), exact: false, icon: settingsIcon() },
]

function routeSafe(name, fallback = '/') {
  try { return route(name) } catch (e) { return fallback }
}

function logout() {
  Inertia.post('/logout')
}

/* icons (small, inline svg strings) */
function dashboardIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h4V14h6v7h4a1 1 0 001-1V10" />
  </svg>`
}
function ordersIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/>
  </svg>`
}
function usersIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-3-3.9M9 20H4v-2a4 4 0 013-3.9M16 11a4 4 0 11-8 0 4 4 0 018 0z" />
  </svg>`
}
function truckIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17V9a1 1 0 011-1h8l3 5v4M3 17h2m14 0h2m-4 0a2 2 0 11-4 0 2 2 0 014 0zM7 17a2 2 0 11-4 0 2 2 0 014 0z" />
  </svg>`
}
function tagIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h3l8 8-3 3-8-8V7z" />
  </svg>`
}
function chartIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M8 17V9m4 8V5m4 12v-4" />
  </svg>`
}
function settingsIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1 1 0 011.35 0l.3.3a1 1 0 00.707.293h3.086a1 1 0 01.949.684l.737 2.21a1 1 0 00.293.437l.3.3a1 1 0 010 1.35l-.3.3a1 1 0 00-.293.707v3.086a1 1 0 01-.684.949l-2.21.737a1 1 0 00-.437.293l-.3.3a1 1 0 01-1.35 0l-.3-.3a1 1 0 00-.707-.293H8.5a1 1 0 01-.949-.684l-.737-2.21a1 1 0 00-.293-.437l-.3-.3a1 1 0 010-1.35l.3-.3a1 1 0 00.293-.707V8.5a1 1 0 01.684-.949l2.21-.737a1 1 0 00.437-.293l.3-.3z" />
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
  </svg>`
}

const logoutIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/></svg>`
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all .16s ease; }
.slide-enter-from { transform: translateY(-6px); opacity: 0; }
.slide-enter-to { transform: translateY(0); opacity: 1; }
.slide-leave-from { transform: translateY(0); opacity: 1; }
.slide-leave-to { transform: translateY(-6px); opacity: 0; }
</style>
