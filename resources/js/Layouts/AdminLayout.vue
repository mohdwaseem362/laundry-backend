<template>
  <div>
    <header class="bg-gray-800 text-white p-4">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between">
          <div class="text-lg font-bold">Laundry Admin</div>
          <div>
            <a href="/" class="mr-4">Frontend</a>
            <button @click="logout" class="underline">Logout</button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto p-4">
      <slot/>
    </main>
  </div>
</template>

<script setup>
import axios from 'axios'
import { Inertia } from '@inertiajs/inertia'

function logout(){
  axios.post('/logout').then(()=> Inertia.visit('/login'))
}
</script>

<style scoped>
/* small helpers if you want */
</style>
