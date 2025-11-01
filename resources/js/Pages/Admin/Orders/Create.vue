<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">Create New Order</h2>
        <button @click="backToOrders" class="text-sm text-gray-600">← Back</button>
      </div>
    </template>

    <div class="max-w-3xl mx-auto p-6 bg-white rounded shadow">
      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block text-sm font-medium">Customer</label>
          <select v-model="form.user_id" required class="w-full rounded border p-2 text-sm">
            <option value="" disabled>Select customer</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} — {{ c.email }}</option>
          </select>
        </div>

        <h3 class="font-medium mb-2">Items</h3>

        <div v-for="(item, idx) in form.items" :key="idx" class="mb-3 p-3 border rounded bg-gray-50">
          <div class="grid grid-cols-12 gap-2 items-center">
            <div class="col-span-6">
              <label class="text-xs text-gray-600">Service name</label>
              <input v-model="item.service_name" type="text" required class="w-full rounded border p-2 text-sm" />
            </div>

            <div class="col-span-2">
              <label class="text-xs text-gray-600">Qty</label>
              <input v-model.number="item.quantity" type="number" min="1" required class="w-full rounded border p-2 text-sm" />
            </div>

            <div class="col-span-3">
              <label class="text-xs text-gray-600">Unit price</label>
              <input v-model.number="item.unit_price" type="number" min="0" step="0.01" required class="w-full rounded border p-2 text-sm" />
            </div>

            <div class="col-span-1 text-right">
              <button type="button" class="text-sm text-red-600" @click="removeItem(idx)" v-if="form.items.length>1">Remove</button>
            </div>
          </div>

          <div class="mt-2 text-xs text-gray-500">Line subtotal: ₹{{ formatMoney(item.quantity * item.unit_price) }}</div>
        </div>

        <button type="button" class="text-sm text-indigo-600 mb-4" @click="addItem">+ Add item</button>

        <div class="mb-4">
          <label class="block text-sm font-medium">Notes</label>
          <textarea v-model="form.notes" rows="3" class="w-full rounded border p-2 text-sm"></textarea>
        </div>

        <div class="flex items-center justify-between">
          <div class="text-sm">
            Subtotal: <span class="font-medium">₹{{ formatMoney(computedSubtotal) }}</span>
          </div>
          <button type="submit" :disabled="loading" class="bg-indigo-600 text-white px-4 py-2 rounded">
            {{ loading ? 'Creating...' : 'Create Order' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { Inertia } from '@inertiajs/inertia'

const props = defineProps({
  customers: { type: Array, default: () => [] },
})

const form = ref({
  user_id: '',
  items: [
    { service_name: '', quantity: 1, unit_price: 0.0 },
  ],
  notes: '',
})

const loading = ref(false)

function addItem() {
  form.value.items.push({ service_name: '', quantity: 1, unit_price: 0.0 })
}
function removeItem(i) {
  form.value.items.splice(i, 1)
}

const computedSubtotal = computed(() => {
  return form.value.items.reduce((s, it) => s + ((it.quantity || 0) * (it.unit_price || 0)), 0)
})

function formatMoney(v) {
  return Number(v || 0).toFixed(2)
}

function submit() {
  loading.value = true
  Inertia.post(route('admin.orders.store'), form.value, {
    onSuccess: () => { loading.value = false },
    onError: () => { loading.value = false },
  })
}

function backToOrders() {
  Inertia.get(route('admin.orders.index'))
}
</script>
