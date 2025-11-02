<template>
    <div class="min-h-screen bg-gray-50">
        <div v-if="$page.props.flash?.success"
            class="fixed top-4 right-4 bg-green-50 border border-green-200 text-green-800 p-3 rounded">
            {{ $page.props.flash.success }}
        </div>

        <!-- top nav -->
        <header class="bg-gray-900 text-white h-12">
            <div class="max-w-7xl mx-auto ml-56 h-full px-4 flex items-center justify-between">
                <div class="text-sm font-semibold">Laundry Admin</div>
                <div class="text-sm">
                    <a href="/" class="mr-4 hover:underline">Frontend</a>
                    <button @click="logout" class="underline">Logout</button>
                </div>
            </div>
        </header>

        <div class="max-w-8xl mx-auto flex">
            <!-- left sidebar -->
            <aside class="w-56 border-r border-gray-100 bg-white min-h-[calc(100vh-48px)]">
                <div class="px-4 py-6">
                    <div class="text-sm font-semibold mb-6">Laundry Admin</div>

                    <nav class="flex flex-col gap-1 text-sm">
                        <SidebarItem label="Dashboard" :icon="icons.dashboard" href="/admin" />
                        <SidebarItem label="Orders" :icon="icons.orders" href="/admin/orders" />
                        <SidebarItem label="Customers" :icon="icons.users" href="/admin/customers" />
                        <SidebarItem label="Countries" :icon="icons.chart" href="/admin/countries" />

                        <SidebarItem label="Agents" :icon="icons.truck" href="/admin/agents" />
                        <SidebarItem label="Services" :icon="icons.tag" href="/admin/services" />
                        <SidebarItem label="Reports" :icon="icons.chart" href="/admin/reports" />
                        <SidebarItem label="Settings" :icon="icons.settings" href="/admin/settings" />
                    </nav>

                    <div class="mt-8 border-t pt-4">
                        <button @click="logout" class="text-red-600 text-sm flex items-center gap-2">
                            <span class="inline-block w-4 h-4">⎋</span> Logout
                        </button>
                    </div>
                </div>
            </aside>

            <!-- main area -->
            <main class="flex-1 p-6">
                <!-- header slot (page title, actions) -->
                <div class="mb-6">
                    <div class="max-w-7xl mx-auto ">
                        <div class="flex items-center justify-between">
                            <div>
                                <!-- render page-provided header slot (named) -->
                                <slot name="header">
                                    <!-- fallback if page didn't provide header -->
                                    <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
                                </slot>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- page content -->
                <div class="max-w-7xl mx-auto ml-56">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import SidebarItem from '@/Components/Admin/SidebarItem.vue'
import { ref } from 'vue'
import axios from 'axios'
import { Inertia } from '@inertiajs/inertia'

// quick inline icon set — ensure these use stroke="currentColor"
const icons = {
    dashboard: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h4V14h6v7h4a1 1 0 001-1V10" /></svg>`,
    orders: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>`,
    users: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-3-3.9M9 20H4v-2a4 4 0 013-3.9M16 11a4 4 0 11-8 0 4 4 0 018 0z" /></svg>`,
    truck: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17V9a1 1 0 011-1h8l3 5v4M3 17h2m14 0h2m-4 0a2 2 0 11-4 0 2 2 0 014 0zM7 17a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`,
    tag: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h3l8 8-3 3-8-8V7z" /></svg>`,
    chart: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M8 17V9m4 8V5m4 12v-4" /></svg>`,
    settings: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1 1 0 011.35 0l.3.3a1 1 0 00.707.293h3.086a1 1 0 01.949.684l.737 2.21a1 1 0 00.293.437l.3.3a1 1 0 010 1.35l-.3.3a1 1 0 00-.293.707v3.086a1 1 0 01-.684.949l-2.21.737a1 1 0 00-.437.293l-.3.3a1 1 0 01-1.35 0l-.3-.3a1 1 0 00-.707-.293H8.5a1 1 0 01-.949-.684l-.737-2.21a1 1 0 00-.293-.437l-.3-.3a1 1 0 010-1.35l.3-.3a1 1 0 00.293-.707V8.5a1 1 0 01.684-.949l2.21-.737a1 1 0 00.437-.293l.3-.3z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`,
}

function logout() {
    axios.post('/logout').then(() => Inertia.visit('/login'))
}
</script>

<style scoped>
/* normalize svg inside icon blocks if used elsewhere */
svg {
    display: block;
}
</style>
