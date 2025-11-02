<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h2 class="text-2xl font-semibold text-gray-800">Countries</h2>

        <div class="flex items-center gap-3">
          <input v-model="q" placeholder="Search by name or ISO" class="rounded border px-3 py-2 text-sm" />
          <select v-model="perPage" class="rounded border px-2 py-1 text-sm">
            <option value="10">10 / page</option>
            <option value="25">25 / page</option>
            <option value="50">50 / page</option>
          </select>

          <button :disabled="syncing" @click="sync" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded text-sm">
            <span v-if="syncing">Syncing…</span>
            <span v-else>Sync Countries</span>
          </button>
        </div>
      </div>
    </template>

    <div class="mt-6 max-w-7xl mx-auto bg-white rounded shadow-sm border overflow-hidden">
      <div class="w-full overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ISO2</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ISO3</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Currency</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timezone</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Active</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-100">
            <tr v-for="c in countries.data" :key="c.id">
              <td class="px-6 py-3 text-sm text-gray-700">{{ c.id }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ c.name }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ c.iso2 }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ c.iso3 }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ c.currency?.code ?? '—' }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ c.timezone ?? '—' }}</td>
              <td class="px-6 py-3 text-right text-sm">
                <span v-if="c.active" class="text-green-600">Yes</span>
                <span v-else class="text-red-600">No</span>
              </td>
              <td class="px-6 py-3 text-right text-sm">
                <a :href="editUrl(c.id)" class="text-indigo-600 hover:underline mr-3">Edit</a>
              </td>
            </tr>

            <tr v-if="!(countries.data && countries.data.length)">
              <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">No countries found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-6 py-4 bg-gray-50 flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing <strong>{{ countries.from ?? 0 }}</strong> - <strong>{{ countries.to ?? 0 }}</strong> of <strong>{{ countries.total ?? 0 }}</strong>
        </div>

        <nav class="inline-flex items-center gap-1">
          <button @click="go(countries.prev_page_url)" :disabled="!countries.prev_page_url" class="px-2 py-1 border rounded">Prev</button>
          <template v-for="link in countries.links" :key="link.label">
            <button v-if="link.url" v-html="link.label" @click="go(link.url)" class="px-2 py-1 border rounded"></button>
            <span v-else class="px-2 py-1 text-sm text-gray-400" v-html="link.label"></span>
          </template>
          <button @click="go(countries.next_page_url)" :disabled="!countries.next_page_url" class="px-2 py-1 border rounded">Next</button>
        </nav>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, watch } from 'vue'
import { Inertia } from '@inertiajs/inertia'

const props = defineProps({
  countries: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) }
})

const countries = props.countries
const filters = props.filters

const q = ref(filters.q ?? '')
const perPage = ref(Number(filters.per_page ?? (countries.per_page ?? 25)))
const syncing = ref(false)

function editUrl(id) {
  try { return route('admin.countries.edit', id) } catch { return `/admin/countries/${id}/edit` }
}

function go(url) {
  if (!url) return
  Inertia.visit(url, { preserveState: true })
}

watch(q, (v) => {
  if (!v || v.length >= 2) {
    Inertia.get(route('admin.countries.index'), { q: v || undefined, per_page: perPage.value }, { preserveState: true, replace: true })
  }
})

watch(perPage, () => {
  Inertia.get(route('admin.countries.index'), { q: q.value || undefined, per_page: perPage.value }, { preserveState: true, replace: true })
})

async function sync() {
  if (syncing.value) return
  syncing.value = true
  try {
    await Inertia.post(route('admin.countries.sync'), {}, { preserveState: true })
    // server redirects back with flash — reload to show new data
    Inertia.reload({ preserveState: true })
  } catch (e) {
    console.error(e)
    alert('Sync failed. Check logs.')
  } finally {
    syncing.value = false
  }
}
</script>

<style scoped>
/* small visual niceties */
</style>
