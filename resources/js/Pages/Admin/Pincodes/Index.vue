<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <div>
          <h2 class="text-2xl font-semibold text-gray-800">Pincodes</h2>
          <p class="text-sm text-gray-500 mt-1">Manage pincodes and assign them to zones.</p>
        </div>

        <div class="flex items-center gap-3">
          <input v-model="q" placeholder="Search by pincode / city / state" class="rounded border px-3 py-2 text-sm" />
          <select v-model="perPage" class="rounded border px-2 py-1 text-sm">
            <option value="10">10 / page</option>
            <option value="25">25 / page</option>
            <option value="50">50 / page</option>
          </select>

          <button @click="openCreate" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm">New Pincode</button>
        </div>
      </div>
    </template>

    <div class="mt-6 max-w-7xl mx-auto bg-white rounded shadow-sm border overflow-hidden">
      <div class="w-full overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pincode</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">City</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">State</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Country</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Active</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-100">
            <tr v-for="p in pincodes.data" :key="p.id">
              <td class="px-6 py-3 text-sm text-gray-700">{{ p.id }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ p.pincode }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ p.city ?? '—' }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ p.state ?? '—' }}</td>
              <td class="px-6 py-3 text-sm text-gray-700">{{ p.country?.name ?? '—' }}</td>
              <td class="px-6 py-3 text-right text-sm">
                <span v-if="p.active" class="text-green-600">Yes</span>
                <span v-else class="text-red-600">No</span>
              </td>
              <td class="px-6 py-3 text-right text-sm">
                <button @click="openAssign(p)" class="text-indigo-600 hover:underline mr-3 text-sm">Assign Zone</button>
                <inertia-link :href="editUrl(p.id)" class="text-indigo-600 hover:underline mr-3 text-sm">Edit</inertia-link>
                <button @click="toggleActive(p)" class="text-sm">
                  <span v-if="p.active" class="text-red-600">Deactivate</span>
                  <span v-else class="text-green-600">Activate</span>
                </button>
              </td>
            </tr>

            <tr v-if="!(pincodes.data && pincodes.data.length)">
              <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">No pincodes found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-6 py-4 bg-gray-50 flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing <strong>{{ pincodes.from ?? 0 }}</strong> - <strong>{{ pincodes.to ?? 0 }}</strong> of <strong>{{ pincodes.total ?? 0 }}</strong>
        </div>

        <nav class="inline-flex items-center gap-1">
          <button @click="go(pincodes.prev_page_url)" :disabled="!pincodes.prev_page_url" class="px-2 py-1 border rounded">Prev</button>
          <template v-for="link in pincodes.links" :key="link.label">
            <button v-if="link.url" v-html="link.label" @click="go(link.url)" class="px-2 py-1 border rounded"></button>
            <span v-else class="px-2 py-1 text-sm text-gray-400" v-html="link.label"></span>
          </template>
          <button @click="go(pincodes.next_page_url)" :disabled="!pincodes.next_page_url" class="px-2 py-1 border rounded">Next</button>
        </nav>
      </div>
    </div>

    <!-- Create modal -->
    <div v-if="showCreate" class="fixed inset-0 z-40 flex items-start justify-center p-6">
      <div class="absolute inset-0 bg-black/40" @click="closeCreate"></div>
      <div class="bg-white rounded shadow-lg w-full max-w-lg z-50 p-6">
        <h3 class="text-lg font-semibold">Create Pincode</h3>
        <form @submit.prevent="createPincode" class="mt-4 space-y-4">
          <div>
            <label class="text-sm block">Pincode</label>
            <input v-model="createForm.pincode" class="mt-1 block w-full border rounded px-3 py-2" />
            <p v-if="createForm.errors.pincode" class="text-red-600 text-sm mt-1">{{ createForm.errors.pincode }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-sm block">City</label>
              <input v-model="createForm.city" class="mt-1 block w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="text-sm block">State</label>
              <input v-model="createForm.state" class="mt-1 block w-full border rounded px-3 py-2" />
            </div>
          </div>

          <div>
            <label class="text-sm block">Country</label>
            <select v-model="createForm.country_id" class="mt-1 block w-full border rounded px-3 py-2">
              <option :value="null">— Select country —</option>
              <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name }} ({{ c.iso2 }})</option>
            </select>
          </div>

          <div class="flex items-center gap-3 justify-end">
            <button type="button" @click="closeCreate" class="px-3 py-2 border rounded">Cancel</button>
            <button :disabled="createForm.processing" type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded">
              <span v-if="createForm.processing">Saving…</span><span v-else>Save</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign modal -->
    <div v-if="showAssign" class="fixed inset-0 z-40 flex items-start justify-center p-6">
      <div class="absolute inset-0 bg-black/40" @click="closeAssign"></div>
      <div class="bg-white rounded shadow-lg w-full max-w-lg z-50 p-6">
        <h3 class="text-lg font-semibold">Assign Pincode <span class="text-sm text-gray-500">({{ assigning?.pincode }})</span></h3>

        <form @submit.prevent="assignToZone" class="mt-4 space-y-4">
          <div>
            <label class="text-sm block">Select Zone</label>
            <select v-model="assignForm.zone_id" class="mt-1 block w-full border rounded px-3 py-2">
              <option :value="null">— Select zone —</option>
              <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }} ({{ z.code ?? '—' }})</option>
            </select>
            <p v-if="assignForm.errors.zone_id" class="text-red-600 text-sm mt-1">{{ assignForm.errors.zone_id }}</p>
          </div>

          <div class="flex items-center gap-3 justify-end">
            <button type="button" @click="closeAssign" class="px-3 py-2 border rounded">Cancel</button>
            <button :disabled="assignForm.processing" type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded">
              <span v-if="assignForm.processing">Assigning…</span><span v-else>Assign</span>
            </button>
          </div>
        </form>
      </div>
    </div>

  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/inertia-vue3'
import { Inertia } from '@inertiajs/inertia'

const props = defineProps({
  pincodes: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  countries: { type: Array, default: () => [] }, // optional list from controller
  zones: { type: Array, default: () => [] },     // optional list of zones
})

const pincodes = props.pincodes
const filters = props.filters
const countries = props.countries
const zones = props.zones

const q = ref(filters.q ?? '')
const perPage = ref(Number(filters.per_page ?? (pincodes.per_page ?? 25)))

// create modal state
const showCreate = ref(false)
const showAssign = ref(false)
const assigning = ref(null)

// forms
const createForm = useForm({
  pincode: '',
  city: '',
  state: '',
  country_id: null,
})

const assignForm = useForm({
  zone_id: null,
})

// helpers
function editUrl(id) {
  try { return route('admin.pincodes.edit', id) } catch { return `/admin/pincodes/${id}/edit` }
}

function go(url) {
  if (!url) return
  Inertia.visit(url, { preserveState: true })
}

// debounce helper
function debounce(fn, delay = 350) {
  let t = null
  return (...args) => {
    if (t) clearTimeout(t)
    t = setTimeout(() => fn(...args), delay)
  }
}

const doSearch = debounce((v) => {
  if (!v || v.length >= 2) {
    Inertia.get(route('admin.pincodes.index'), { q: v || undefined, per_page: perPage.value }, { preserveState: true, replace: true })
  }
}, 300)

// watchers
watch(q, (v) => doSearch(v))
watch(perPage, () => Inertia.get(route('admin.pincodes.index'), { q: q.value || undefined, per_page: perPage.value }, { preserveState: true, replace: true }))

// create modal functions
function openCreate() { createForm.reset(); showCreate.value = true }
function closeCreate() { showCreate.value = false }

// create submit
async function createPincode() {
  await createForm.post(route('admin.pincodes.store'), {
    onSuccess: () => {
      closeCreate()
      Inertia.reload({ preserveState: true })
    },
    onError: () => {}
  })
}

// assign modal
function openAssign(p) {
  assigning.value = p
  assignForm.reset()
  showAssign.value = true
}
function closeAssign() { showAssign.value = false; assigning.value = null }

// assign submit
async function assignToZone() {
  if (!assigning.value) return
  await assignForm.post(route('admin.zones.attach_pincode', assigning.value.id), {
    onSuccess: () => {
      closeAssign()
      Inertia.reload({ preserveState: true })
    },
    onError: () => {}
  })
}

// toggle active (simple request)
async function toggleActive(p) {
  try {
    await Inertia.post(route('admin.pincodes.update', p.id), { active: !p.active }, { preserveState: true })
    Inertia.reload({ preserveState: true })
  } catch (e) {
    console.error(e)
  }
}
</script>

<style scoped>
/* small modal niceties */
</style>
