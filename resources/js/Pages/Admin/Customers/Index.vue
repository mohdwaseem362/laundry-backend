<template>
  <AdminLayout>
    <template #header>
      <div class="w-full">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
          <h2 class="text-2xl font-semibold text-gray-800">Customers</h2>

          <div class="flex flex-wrap items-center gap-3">
            <input
              v-model="q"
              placeholder="Search name, email or phone..."
              class="w-64 sm:w-80 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-300"
            />

            <select
              v-model="perPage"
              class="rounded-md border border-gray-200 bg-white px-2 py-2 text-sm shadow-sm focus:outline-none"
            >
              <option value="10">10 / page</option>
              <option value="15">15 / page</option>
              <option value="25">25 / page</option>
            </select>

            <button
              type="button"
              @click="visitCreate"
              class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-3 py-2 rounded-md shadow-sm transition"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" stroke="currentColor" fill="none">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              New Customer
            </button>
          </div>
        </div>
      </div>
    </template>

    <div class="mt-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
          <div class="w-full overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>

              <tbody class="bg-white divide-y divide-gray-100">
                <!-- real rows -->
                <tr v-for="c in (customers.data || [])" :key="c.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ c.id }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ c.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ c.email }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ c.primary_phone?.phone ?? '—' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(c.created_at) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="#" @click.prevent="visitEdit(c.id)" class="text-indigo-600 mr-3 hover:underline">Edit</a>
                    <button v-if="!c.deleted_at" @click="confirmDelete(c.id)" class="text-red-600 hover:text-red-800">Delete</button>
                    <button v-else @click="restore(c.id)" class="text-green-600 hover:text-green-800">Restore</button>
                  </td>
                </tr>

                <!-- single clean empty state (when there are no rows) -->
                <tr v-if="!(customers.data && customers.data.length)">
                  <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                    No customers yet. Click <span class="font-semibold">New Customer</span> to create one.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- footer / pagination -->
          <div class="px-6 py-4 bg-gray-50 flex items-center justify-between">
            <div class="text-sm text-gray-600">
              Showing <span class="font-medium">{{ customers.from ?? 0 }}</span>
              - <span class="font-medium">{{ customers.to ?? 0 }}</span>
              of <span class="font-medium">{{ customers.total ?? 0 }}</span>
            </div>

            <nav class="inline-flex items-center gap-2">
              <button @click="go(customers.prev_page_url)" :disabled="!customers.prev_page_url" class="px-3 py-1 border rounded text-sm bg-white hover:bg-gray-50 disabled:opacity-50">Prev</button>

              <template v-for="link in customers.links" :key="link.label">
                <button v-if="link.url" v-html="link.label" @click="go(link.url)" :class="['px-3 py-1 border rounded text-sm', link.active ? 'bg-indigo-600 text-white' : 'bg-white']" />
                <span v-else class="px-3 py-1 text-sm text-gray-400" v-html="link.label"></span>
              </template>

              <button @click="go(customers.next_page_url)" :disabled="!customers.next_page_url" class="px-3 py-1 border rounded text-sm bg-white hover:bg-gray-50 disabled:opacity-50">Next</button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, watch } from 'vue'
import { Inertia } from '@inertiajs/inertia'

const props = defineProps({
  customers: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) }
})

const customers = props.customers
const filters = props.filters

const q = ref(filters.q ?? '')
const perPage = ref(Number(filters.per_page ?? (customers.per_page ?? 15)))

function routeEdit(id) {
  try { return route('admin.customers.edit', id) } catch { return `/admin/customers/${id}/edit` }
}

function visitEdit(id) {
  // try Ziggy first (if available), else fallback to a safe URL
  try {
    return Inertia.get(route('admin.customers.edit', id))
  } catch (e) {
    return Inertia.get(`/admin/customers/${id}/edit`)
  }
}

function visitCreate() {
  try { Inertia.get(route('admin.customers.create')) } catch { Inertia.get('/admin/customers/create') }
}

function go(url) {
  if (!url) return
  Inertia.visit(url, { preserveState: true, replace: true })
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString()
}

function confirmDelete(id) {
  if (!confirm('Delete this customer? This is a soft delete.')) return
  Inertia.delete(route('admin.customers.destroy', id), { onSuccess: () => { Inertia.reload({ preserveState: true }) } })
}

function restore(id) {
  Inertia.post(route('admin.customers.restore', id), {}, { onSuccess: () => Inertia.reload({ preserveState: true }) })
}

// watch filters
watch(q, (val) => {
  if (!val || val.length >= 2) {
    Inertia.get(route('admin.customers.index'), { q: val || undefined, per_page: perPage.value }, { replace: true, preserveState: true })
  }
})
watch(perPage, () => {
  Inertia.get(route('admin.customers.index'), { q: q.value || undefined, per_page: perPage.value }, { replace: true, preserveState: true })
})
</script>

<style scoped>
table { border-collapse: separate; border-spacing: 0; }
</style>
