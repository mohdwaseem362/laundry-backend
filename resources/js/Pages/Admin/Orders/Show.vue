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

          <div class="relative" ref="actionsRoot">
            <button @click.stop="toggleActions" class="rounded-md bg-indigo-600 px-3 py-1 text-sm text-white">Actions ▾</button>
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
        <!-- Top details (unchanged) -->
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

        <!-- Items table (unchanged) -->
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

            <!-- messages list -->
            <div class="mt-3 space-y-3 max-h-64 overflow-auto" ref="messagesBox">
              <div v-if="messages.length === 0" class="text-sm text-gray-400">No messages yet</div>

              <div v-for="m in messages" :key="m._temp_id ?? m.id" class="rounded-md border p-2 bg-white">
                <div class="flex items-start justify-between">
                  <div class="text-xs text-gray-500">
                    {{ formatDateTime(m.created_at) }} • <strong>{{ m.from || m.user?.name || 'System' }}</strong>
                    <span v-if="m.sending" class="ml-2 text-xs text-gray-400">• Sending…</span>
                  </div>
                  <div v-if="m.attachment && m.attachment.uploadProgress !== undefined" class="text-xs text-gray-400">
                    <span v-if="m.attachment.uploadProgress < 100">{{ m.attachment.uploadProgress }}%</span>
                    <span v-else>Uploaded</span>
                  </div>
                </div>

                <div class="mt-1 text-sm break-words">{{ m.message || m.content }}</div>

                <!-- Attachment rendering -->
                <div v-if="m.attachment && m.attachment.url" class="mt-2">
                  <!-- image preview for common image types -->
                  <img v-if="isImage(m.attachment.mime)" :src="m.attachment.url" class="max-h-40 rounded-md border" alt="attachment" />

                  <!-- non-image: show file link -->
                  <div v-else class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none"><path d="M12 2v10l3-3 0 0 5 0v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h6z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <a :href="m.attachment.url" target="_blank" rel="noopener noreferrer" class="text-sm text-indigo-600 hover:underline">
                      {{ m.attachment.filename || 'Attachment' }}
                    </a>
                    <span class="text-xs text-gray-400">({{ formatBytes(m.attachment.size) }})</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- message form -->
            <form @submit.prevent="sendMessage" class="mt-3 flex flex-col gap-2">
              <textarea
                v-model="messageText"
                rows="3"
                class="w-full rounded-md border px-2 py-2 text-sm"
                placeholder="Write a message to the customer or agent..."
                :disabled="sending"
              ></textarea>

              <!-- file input + preview -->
              <div class="flex items-center gap-2">
                <input
                  ref="fileInput"
                  type="file"
                  @change="handleFile"
                  :accept="acceptedTypes"
                  class="text-xs text-gray-500"
                  :disabled="sending"
                />
                <div v-if="attachedFile" class="text-xs text-gray-600">
                  <div class="flex items-center gap-2">
                    <span>{{ attachedFile.name }}</span>
                    <span class="text-gray-400">({{ formatBytes(attachedFile.size) }})</span>
                    <button type="button" @click="removeFile" :disabled="sending" class="ml-2 text-red-500 text-xs hover:underline">Remove</button>
                  </div>
                </div>

                <div class="ml-auto flex items-center gap-2">
                  <button
                    :disabled="sending || (!messageText && !attachedFile)"
                    type="submit"
                    class="ml-auto rounded-md bg-indigo-600 px-3 py-1 text-sm text-white disabled:bg-gray-400"
                  >
                    <span v-if="!sending">Send</span>
                    <span v-else>Sending…</span>
                  </button>
                </div>
              </div>

              <!-- global upload progress bar -->
              <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-1">
                <div class="h-2 w-full rounded-full bg-gray-200">
                  <div :style="{ width: uploadProgress + '%' }" class="h-2 rounded-full bg-indigo-600"></div>
                </div>
                <div class="text-xs text-gray-500 mt-1">{{ uploadProgress }}%</div>
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
import axios from 'axios'
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

// props
const props = defineProps({
  order: { type: Object, required: true, default: () => ({}) },
  messages: { type: Array, default: () => [] }
})

// state
const showActions = ref(false)
const sending = ref(false)
const messageText = ref('')
const attachedFile = ref(null)
const fileInput = ref(null)
const messages = ref([...props.messages]) // reactive local copy
const debugOpen = ref(false)
const uploadProgress = ref(0)
const actionsRoot = ref(null)
const messagesBox = ref(null)
const indexRoute = '/admin/orders'

// accepted file types (adjust as needed)
const acceptedTypes = '.png,.jpg,.jpeg,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt'

// helper: close dropdown on outside click
function handleClickOutside(event) {
  if (!actionsRoot.value) return
  if (!actionsRoot.value.contains(event.target)) {
    showActions.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  // auto scroll to bottom of messages
  nextTick(() => scrollMessagesToBottom())
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// UI helpers
function toggleActions() { showActions.value = !showActions.value }

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
  try { return new Date(dateString).toLocaleDateString() } catch { return '—' }
}
function formatDateTime(dateString) {
  if (!dateString) return '—'
  try { return new Date(dateString).toLocaleString() } catch { return '—' }
}

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const sizes = ['B','KB','MB','GB','TB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return `${(bytes / Math.pow(1024, i)).toFixed( i ? 1 : 0 )} ${sizes[i]}`
}

function isImage(mime) {
  return !!mime && mime.startsWith('image/')
}

// remove selected file
function removeFile() {
  attachedFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

// handle file change
function handleFile(event) {
  const file = event.target.files && event.target.files[0]
  if (!file) {
    attachedFile.value = null
    return
  }

  // client-side validation: 5MB max
  const maxSize = 5 * 1024 * 1024
  if (file.size > maxSize) {
    alert('File size must be less than 5MB')
    event.target.value = ''
    return
  }

  // optional: restrict file types on client
  attachedFile.value = file
}

// optimistic id generator for local messages
function tempId() {
  return 't-' + Date.now() + '-' + Math.floor(Math.random() * 1000)
}

// scroll messages container to bottom
function scrollMessagesToBottom() {
  if (!messagesBox.value) return
  messagesBox.value.scrollTop = messagesBox.value.scrollHeight
}

// send message with attachment using axios so we can track progress (clean async/await)
async function sendMessage() {
  if (!messageText.value.trim() && !attachedFile.value) {
    alert('Please enter a message or attach a file')
    return
  }

  sending.value = true
  uploadProgress.value = 0

  // create optimistic message entry locally
  const tempMessage = {
    _temp_id: tempId(),
    message: messageText.value.trim(),
    created_at: new Date().toISOString(),
    from: 'Admin',
    sending: true,
    attachment: attachedFile.value ? {
      filename: attachedFile.value.name,
      size: attachedFile.value.size,
      mime: attachedFile.value.type,
      uploadProgress: 0
    } : null
  }

  messages.value.unshift(tempMessage)
  nextTick(() => scrollMessagesToBottom())

  const formData = new FormData()
  formData.append('message', messageText.value.trim())
  if (attachedFile.value) formData.append('attachment', attachedFile.value)

  try {
    const response = await axios.post(route('admin.orders.message', props.order.id), formData, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      onUploadProgress: (progressEvent) => {
        if (!progressEvent.lengthComputable) return
        const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        uploadProgress.value = percent
        if (tempMessage.attachment) tempMessage.attachment.uploadProgress = percent
      }
    })

    // expected payload: { message: { ... } }
    if (response && response.data && response.data.message) {
      // remove temp message and insert server message at top
      const idx = messages.value.findIndex(m => m._temp_id === tempMessage._temp_id)
      if (idx !== -1) messages.value.splice(idx, 1)
      messages.value.unshift(response.data.message)
    } else {
      // fallback: just mark optimistic as failed
      const idx = messages.value.findIndex(m => m._temp_id === tempMessage._temp_id)
      if (idx !== -1) {
        messages.value[idx].sending = false
        messages.value[idx].failed = true
      }
      alert('Unexpected server response. Check the network tab.')
    }
  } catch (err) {
    console.error('Message send failed', err)
    // mark optimistic message as failed
    const idx = messages.value.findIndex(m => m._temp_id === tempMessage._temp_id)
    if (idx !== -1) {
      messages.value[idx].sending = false
      messages.value[idx].failed = true
    }

    // try to surface validation errors if available
    const errData = err.response && err.response.data
    if (errData && errData.errors) {
      const messagesArr = Object.values(errData.errors).flat()
      alert(messagesArr.join('\n'))
    } else {
      alert('Failed to send message. Please try again.')
    }
  } finally {
    sending.value = false
    uploadProgress.value = 0
    messageText.value = ''
    attachedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
    nextTick(() => scrollMessagesToBottom())
  }
}

// change status (keeps your existing flow)
function changeStatus(status) {
  if (!confirm(`Change order status to "${status}"?`)) return
  showActions.value = false

  router.patch(route('admin.orders.update', props.order.id), { status }, {
    preserveScroll: true,
    onSuccess: () => { /* optimistic update may be done on server props refresh */ },
    onError: (errors) => { alert('Failed to update status: ' + (errors.status || 'Unknown error')) }
  })
}
</script>

<style scoped>
/* small visual tweak if you want zebra-ish rows on large screens */
@media (min-width: 640px) {
  tbody tr:nth-child(odd) { background-color: #ffffff; }
  tbody tr:nth-child(even) { background-color: #fbfbfb; }
}
</style>
