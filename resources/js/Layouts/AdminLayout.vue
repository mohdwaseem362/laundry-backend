<template>
  <div class="min-h-screen flex bg-gray-50 text-gray-800">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-h-screen">
      <!-- Header -->
      <header class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="py-3 flex items-center justify-between">
            <div class="text-lg font-semibold">Laundry Admin</div>

            <div class="flex items-center gap-4 text-sm">
              <a href="/" class="hover:underline">Frontend</a>
              <button @click="logout" class="underline hover:text-gray-300">Logout</button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { Inertia } from '@inertiajs/inertia'
import Sidebar from '@/Components/Admin/Sidebar.vue'

function logout() {
  // use Inertia / axios as you prefer
  axios.post('/logout').then(() => Inertia.visit('/login'))
}
</script>

<style scoped>
/* no changes needed here; visual tweaks are in Sidebar */
</style>
