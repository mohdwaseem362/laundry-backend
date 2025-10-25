<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-800">Order {{ order.order_number }}</h2>
          <p class="text-sm text-gray-500">#{{ order.id }} — <span :class="statusClass(order.status)">{{ order.status }}</span></p>
        </div>

        <div class="flex items-center gap-3">
          <a :href="indexRoute" class="text-sm text-indigo-600 hover:underline">← Back to orders</a>
          <div class="inline-flex items-center rounded-md bg-gray-50 px-3 py-2 text-sm">
            <span class="mr-2 text-xs text-gray-500">Total</span>
            <strong>${{ order.final_amount || order.total || 0 }}</strong>
          </div>

          <div class="relative">
            <button @click="toggleActions" class="rounded-md bg-indigo-600 px-3 py-1 text-sm text-white">Actions ▾</button>
            <div v-if="showActions" class="absolute right-0 mt-2 w-48 rounded-md border bg-white shadow-lg z-20">
              <button @click="changeStatus('processing')" class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-50">Mark Processing</button>
              <button @click="changeStatus('completed')" class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-50">Mark Completed</button>
              <button @click="changeStatus('cancelled')" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-50">Cancel Order</button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        <!-- Top details -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
          <div class="rounded-lg border p-4">
            <h3 class="text-sm font-medium text-gray-600">Customer</h3>
            <div class="mt-2">
              <div class="text-sm font-semibold">{{ order.user?.name ?? '—' }}</div>
              <div class="text-xs text-gray-500">{{ order.user?.email ?? '—' }}</div>
              <div class="text-xs text-gray-400 mt-1">ID: {{ order.user_id }}</div>
            </div>
          </div>

          <div class="rounded-lg border p-4">
            <h3 class="text-sm font-medium text-gray-600">Schedule</h3>
            <div class="mt-2 text-sm">
              <div>Pickup: <strong>{{ formatDate(order.pickup_date || order.pickup_at) }}</strong></div>
              <div>Delivery: <strong>{{ formatDate(order.delivery_date || order.delivery_at) }}</strong></div>
              <div class="mt-1">Status: <strong>{{ order.status }}</strong></div>
            </div>
          </div>

          <div class="rounded-lg border p-4">
            <h3 class="text-sm font-medium text-gray-600">Amounts</h3>
            <div class="mt-2 text-sm">
              <div>Subtotal: <strong>${{ order.total_amount || order.subtotal || 0 }}</strong></div>
              <div>Tax: <strong>${{ order.tax || 0 }}</strong></div>
              <div>Discount: <strong>${{ order.discount || 0 }}</strong></div>
              <div class="mt-1 border-t pt-1">Total: <strong class="text-lg">${{ order.final_amount || order.total || 0 }}</strong></div>
            </div>
          </div>
        </div>

        <!-- Items table -->
        <div class="rounded-lg border bg-white p-4">
          <h3 class="mb-3 text-sm font-medium text-gray-700">Items</h3>
          <div v-if="order.items && order.items.length">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-xs text-gray-500">
                  <th class="px-2 py-2">Service</th>
                  <th class="px-2 py-2">Qty</th>
                  <th class="px-2 py-2">Unit Price</th>
                  <th class="px-2 py-2">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="it in order.items" :key="it.id" class="border-t">
                  <td class="px-2 py-2">{{ it.service?.name || it.service_name || 'Service' }}</td>
                  <td class="px-2 py-2">{{ it.quantity }}</td>
                  <td class="px-2 py-2">${{ it.unit_price }}</td>
                  <td class="px-2 py-2">${{ it.total_price || it.subtotal }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="border-t font-medium">
                  <td colspan="3" class="px-2 py-2 text-right">Total:</td>
                  <td class="px-2 py-2">${{ order.final_amount || order.total || 0 }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div v-else class="text-sm text-gray-500">No items for this order</div>
        </div>

        <!-- Notes and messages -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div class="rounded-lg border p-4">
            <h3 class="text-sm font-medium text-gray-700">Notes</h3>
            <div class="mt-2 text-sm text-gray-600">
              <p v-if="order.special_instructions || order.notes">{{ order.special_instructions || order.notes }}</p>
              <p v-else class="text-gray-400">No notes</p>
            </div>
          </div>

          <div class="rounded-lg border p-4">
            <h3 class="text-sm font-medium text-gray-700">Messages</h3>
            <div class="mt-3 space-y-3 max-h-48 overflow-auto">
              <div v-for="m in messages" :key="m.id" class="rounded-md border p-2">
                <div class="text-xs text-gray-500">{{ formatDateTime(m.created_at) }} • <strong>{{ m.from || m.user?.name || 'System' }}</strong></div>
                <div class="mt-1 text-sm">{{ m.message || m.content }}</div>
              </div>
              <div v-if="messages.length === 0" class="text-sm text-gray-400">No messages yet</div>
            </div>

            <form @submit.prevent="sendMessage" class="mt-3 flex flex-col gap-2">
              <textarea
                v-model="messageText"
                rows="3"
                class="w-full rounded-md border px-2 py-2 text-sm"
                placeholder="Write a message to the customer or agent..."
                :disabled="sending"
              ></textarea>

              <div class="flex items-center gap-2">
                <input
                  ref="fileInput"
                  type="file"
                  @change="handleFile"
                  class="text-xs text-gray-500"
                  :disabled="sending"
                />
                <button
                  :disabled="sending || (!messageText && !attachedFile)"
                  type="submit"
                  class="ml-auto rounded-md bg-indigo-600 px-3 py-1 text-sm text-white disabled:bg-gray-400"
                >
                  <span v-if="!sending">Send</span>
                  <span v-else>Sending…</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- raw JSON debug (toggle) -->
        <div class="text-sm text-gray-400">
          <button @click="debugOpen = !debugOpen" class="text-xs text-indigo-600 hover:underline">
            {{ debugOpen ? 'Hide' : 'Show' }} debug payload
          </button>

          <pre v-if="debugOpen" class="mt-2 max-h-48 overflow-auto rounded-md bg-gray-800 p-3 text-xs text-white">{{ JSON.stringify(order, null, 2) }}</pre>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

// Use defineProps for better TypeScript support and clarity
const props = defineProps({
  order: {
    type: Object,
    required: true,
    default: () => ({})
  },
  messages: {
    type: Array,
    default: () => []
  }
})

// local UI state
const showActions = ref(false)
const sending = ref(false)
const messageText = ref('')
const attachedFile = ref(null)
const fileInput = ref(null) // Proper ref for file input
const messages = ref([...props.messages])
const debugOpen = ref(false)

// Close actions dropdown when clicking outside
function handleClickOutside(event) {
  if (!event.target.closest('.relative')) {
    showActions.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Route helpers
const indexRoute = '/admin/orders'

function toggleActions() {
  showActions.value = !showActions.value
}

// Status update using Inertia
function changeStatus(status) {
  if (!confirm(`Change order status to "${status}"?`)) return

  showActions.value = false

  router.patch(route('admin.orders.update', props.order.id), {
    status: status
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Status will be updated via the reactive props
    },
    onError: (errors) => {
      alert('Failed to update status: ' + (errors.status || 'Unknown error'))
    }
  })
}

function handleFile(event) {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB')
      event.target.value = ''
      return
    }
    attachedFile.value = file
  }
}

// Message sending
async function sendMessage() {
  if (!messageText.value.trim() && !attachedFile.value) {
    alert('Please enter a message or attach a file')
    return
  }

  sending.value = true

  try {
    const formData = new FormData()
    formData.append('message', messageText.value.trim())
    if (attachedFile.value) {
      formData.append('attachment', attachedFile.value)
    }

    const response = await fetch(route('admin.orders.message', props.order.id), {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: formData
    })

    if (response.ok) {
      const result = await response.json()

      // Add new message to the list
      if (result.message) {
        messages.value.unshift(result.message)
      }

      // Reset form
      messageText.value = ''
      attachedFile.value = null
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    } else {
      throw new Error('Failed to send message')
    }
  } catch (error) {
    console.error('Message send error:', error)
    alert('Failed to send message. Please try again.')
  } finally {
    sending.value = false
  }
}

// UI helpers
function statusClass(status) {
  const statusMap = {
    pending: 'text-yellow-600',
    confirmed: 'text-blue-600',
    processing: 'text-indigo-600',
    ready: 'text-purple-600',
    completed: 'text-green-600',
    cancelled: 'text-red-600'
  }
  return statusMap[status] || 'text-gray-600'
}

function formatDate(dateString) {
  if (!dateString) return '—'
  try {
    return new Date(dateString).toLocaleDateString()
  } catch {
    return '—'
  }
}

function formatDateTime(dateString) {
  if (!dateString) return '—'
  try {
    return new Date(dateString).toLocaleString()
  } catch {
    return '—'
  }
}
</script>

<style scoped>
/* Custom styles if needed */
</style>
