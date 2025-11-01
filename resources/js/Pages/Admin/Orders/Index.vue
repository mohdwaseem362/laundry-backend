<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Orders</h2>

        <div class="flex items-center gap-3">
          <div class="text-sm text-gray-600 mr-2">Total</div>
          <div class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
            {{ orders.total ?? 0 }}
          </div>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <input
              v-model="q"
              type="search"
              placeholder="Search order # or user..."
              class="w-full sm:w-72 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-100"
            />
            <button @click="clearSearch" v-if="q" class="text-sm text-gray-500 hover:text-gray-700">Clear</button>
          </div>

          <div class="flex items-center gap-2">
            <select v-model="perPage" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-sm shadow-sm">
              <option value="10">10 / page</option>
              <option value="15">15 / page</option>
              <option value="25">25 / page</option>
            </select>

            <button class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
              New Order
            </button>
          </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
          <div class="w-full overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Order #</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">User</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Total</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Created</th>
                  <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-for="o in visibleOrders" :key="o.id" class="hover:bg-gray-50">
                  <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ o.id }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ o.order_number }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">
                    {{ o.user?.name ?? '—' }}
                    <div class="text-xs text-gray-400">{{ o.user?.email ?? '' }}</div>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-800">${{ o.total }}</td>
                  <td class="px-4 py-3">
                    <span :class="statusClass(o.status)" class="inline-flex items-center rounded-full px-3 py-0.5 text-xs font-medium">
                      {{ o.status }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ formatDate(o.created_at) }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                    <a :href="routeShow(o.id)" class="text-indigo-600 hover:text-indigo-800">View</a>
                    <span class="mx-2 text-gray-300">|</span>
                    <button @click="openAction(o)" class="text-gray-600 hover:text-gray-800">Actions</button>
                  </td>
                </tr>

                <tr v-if="visibleOrders.length === 0">
                  <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="7">No orders found</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- pagination -->
          <div class="flex items-center justify-between gap-4 bg-gray-50 px-4 py-3 sm:px-6">
            <div class="text-sm text-gray-600">
              Showing
              <span class="font-medium">{{ firstItem }} - {{ lastItem }}</span>
              of <span class="font-medium">{{ orders.total ?? 0 }}</span> results
            </div>

            <nav class="flex items-center gap-1" aria-label="Pagination">
              <button
                :disabled="!orders.prev_page_url"
                @click="go(orders.prev_page_url)"
                class="rounded-md px-3 py-1 text-sm font-medium border bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Prev
              </button>

              <template v-for="link in orders.links" :key="link.label">
                <button
                  v-if="link.label && link.url"
                  v-html="link.label"
                  @click="go(link.url)"
                  :class="['px-3 py-1 text-sm font-medium rounded-md border', link.active ? 'bg-indigo-600 text-white' : 'bg-white']"
                />
                <span v-else-if="!link.url" class="px-3 py-1 text-sm text-gray-400" v-html="link.label"></span>
              </template>

              <button
                :disabled="!orders.next_page_url"
                @click="go(orders.next_page_url)"
                class="rounded-md px-3 py-1 text-sm font-medium border bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Next
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { defineProps, ref, computed } from 'vue'
import { Inertia } from '@inertiajs/inertia'

// expect orders prop from Inertia (paginator)
const props = defineProps({
  orders: { type: Object, default: () => ({ data: [], links: [], total: 0 }) }
})

const orders = props.orders

// local UI state
const q = ref('')
const perPage = ref(Number(orders.per_page ?? 15))
// filtered list (client-side quick search)
const visibleOrders = computed(() => {
  const term = q.value.trim().toLowerCase()
  if (!term) return orders.data ?? []
  return (orders.data ?? []).filter(o =>
    (o.order_number || '').toLowerCase().includes(term) ||
    (o.user?.name || '').toLowerCase().includes(term) ||
    (o.user?.email || '').toLowerCase().includes(term)
  )
})

// helper values for showing range
const firstItem = computed(() => orders.from ?? (visibleOrders.value.length ? 1 : 0))
const lastItem = computed(() => orders.to ?? visibleOrders.value.length)

// navigation helper
function go(url) {
  if (!url) return
  Inertia.visit(url, { preserveState: true })
}

function clearSearch() { q.value = '' }

function formatDate(s) {
  if (!s) return '—'
  const d = new Date(s)
  return d.toLocaleString()
}

function statusClass(status) {
  switch ((status || '').toLowerCase()) {
    case 'requested': return 'bg-yellow-100 text-yellow-800'
    case 'processing': return 'bg-blue-100 text-blue-800'
    case 'completed': return 'bg-green-100 text-green-800'
    case 'cancelled': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

function openAction(order) {
  // placeholder - wire up a modal or dropdown as needed
  // console.log('actions for', order.id)
}

function routeShow(id) {
  // depends on Ziggy route availability
  try {
    return route('admin.orders.show', id)
  } catch (e) {
    return `/admin/orders/${id}`
  }
}
</script>

<style scoped>
/* small visual tweak if you want zebra-ish rows on large screens */
@media (min-width: 640px) {
  tbody tr:nth-child(odd) { background-color: #ffffff; }
  tbody tr:nth-child(even) { background-color: #fbfbfb; }
}
</style>
