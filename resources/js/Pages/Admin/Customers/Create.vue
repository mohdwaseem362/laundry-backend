<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">New Customer</h2>
        <button @click="back" class="text-sm text-gray-600 hover:underline">Back</button>
      </div>
    </template>

    <div class="mt-6 max-w-3xl mx-auto">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <form @submit.prevent="submit" novalidate>
          <div class="grid grid-cols-1 gap-4">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
              <input v-model="form.name" type="text" class="w-full rounded-md border px-3 py-2 focus:ring-2 focus:ring-indigo-100" />
              <div v-if="errors.name" class="text-red-600 text-sm mt-1">{{ errors.name }}</div>
            </div>

            <!-- Email -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input v-model="form.email" type="email" class="w-full rounded-md border px-3 py-2 focus:ring-2 focus:ring-indigo-100" />
              <div v-if="errors.email" class="text-red-600 text-sm mt-1">{{ errors.email }}</div>
            </div>

            <!-- Phones (dynamic) -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700">Phone numbers</label>
                <button type="button" @click="addPhone" class="text-sm text-indigo-600 hover:underline">+ Add phone</button>
              </div>

              <div class="space-y-2">
                <div v-for="(p, idx) in form.phones" :key="p.tempId" class="flex gap-2 items-center">
                  <input v-model="p.phone" placeholder="Phone" class="flex-1 rounded-md border px-3 py-2" />
                  <input v-model="p.type" placeholder="Type (mobile/home)" class="w-36 rounded-md border px-3 py-2" />
                  <label class="inline-flex items-center text-sm text-gray-600">
                    <input type="radio" :value="idx" v-model="primaryIndex" class="mr-2" />
                    Primary
                  </label>
                  <button type="button" @click="removePhone(idx)" class="text-sm text-red-600 hover:underline">Remove</button>
                </div>

                <div v-if="errors.phones" class="text-red-600 text-sm mt-1">{{ errors.phones }}</div>
              </div>
            </div>

            <!-- Address (single block for now) -->
            <div class="grid grid-cols-1 gap-2">
              <label class="text-sm font-medium text-gray-700">Address (optional)</label>
              <input v-model="form.address.line1" placeholder="Line 1" class="rounded-md border px-3 py-2" />
              <input v-model="form.address.line2" placeholder="Line 2 / Landmark" class="rounded-md border px-3 py-2" />
              <div class="grid grid-cols-3 gap-2">
                <input v-model="form.address.city" placeholder="City" class="rounded-md border px-3 py-2" />
                <input v-model="form.address.state" placeholder="State" class="rounded-md border px-3 py-2" />
                <input v-model="form.address.pincode" placeholder="Pincode" class="rounded-md border px-3 py-2" />
              </div>
            </div>

            <!-- Passwords -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input v-model="form.password" type="password" class="w-full rounded-md border px-3 py-2" />
                <div v-if="errors.password" class="text-red-600 text-sm mt-1">{{ errors.password }}</div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                <input v-model="form.password_confirmation" type="password" class="w-full rounded-md border px-3 py-2" />
              </div>
            </div>

            <!-- Server error -->
            <div v-if="serverError" class="text-red-600 text-sm">{{ serverError }}</div>

            <!-- Actions -->
            <div class="flex items-center justify-between mt-4">
              <div class="text-sm text-gray-500">Fields marked required must be filled.</div>
              <div class="flex items-center gap-3">
                <button type="button" @click="back" class="px-3 py-2 rounded border text-sm">Cancel</button>
                <button :disabled="loading" type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm">
                  <span v-if="loading" class="loader-border w-4 h-4"></span>
                  {{ loading ? 'Creating...' : 'Create Customer' }}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { reactive, ref, watch } from 'vue'
import { Inertia } from '@inertiajs/inertia'

// form state
const form = reactive({
  name: '',
  email: '',
  phones: [
    { tempId: Date.now(), phone: '', type: 'mobile', is_primary: false }
  ],
  address: { line1: '', line2: '', city: '', state: '', pincode: '' },
  password: '',
  password_confirmation: '',
})

// UI state
const loading = ref(false)
const serverError = ref('')
const errors = reactive({})
const primaryIndex = ref(0)

// keep primary flag in sync with primaryIndex
watch(primaryIndex, (v) => {
  form.phones.forEach((p, idx) => p.is_primary = idx === v)
})

// helpers
function addPhone() {
  form.phones.push({ tempId: Date.now() + Math.random(), phone: '', type: 'mobile', is_primary: false })
}

function removePhone(idx) {
  // if removing primary, set first as primary
  const removedPrimary = form.phones[idx]?.is_primary
  form.phones.splice(idx, 1)
  if (form.phones.length && removedPrimary) {
    primaryIndex.value = 0
    form.phones[0].is_primary = true
  }
}

function routeOrFallback(name, params = {}) {
  try {
    return route(name, params)
  } catch (e) {
    // fallback to typical path for customers.store
    if (name === 'admin.customers.store') return '/admin/customers'
    return '/'
  }
}

function back() {
  try {
    Inertia.get(route('admin.customers.index'))
  } catch {
    Inertia.get('/admin/customers')
  }
}

function resetErrors() {
  Object.keys(errors).forEach(k => delete errors[k])
  serverError.value = ''
}

// submit
function submit() {
  resetErrors()
  loading.value = true

  // prepare payload
  const payload = {
    name: form.name,
    email: form.email,
    phones: form.phones.map(p => ({ phone: p.phone, type: p.type, is_primary: !!p.is_primary })),
    address: form.address,
    password: form.password,
    password_confirmation: form.password_confirmation,
  }

  Inertia.post(routeOrFallback('admin.customers.store'), payload, {
    preserveState: false,
    onSuccess: () => {
      loading.value = false
      // redirect to index (server usually redirects). If not, call back()
    },
    onError: (errs) => {
      loading.value = false
      // errs usually is an object of field => messages
      if (errs && typeof errs === 'object') {
        // map simple errors to our errors object
        Object.keys(errs).forEach(k => {
          // flatten array responses (first message)
          errors[k] = Array.isArray(errs[k]) ? errs[k][0] : String(errs[k])
        })
        // show top-level server message if provided
        serverError.value = errors.message || ''
      } else {
        serverError.value = 'Failed to create customer. Check fields.'
      }
    },
    onFinish: () => { loading.value = false }
  })
}
</script>

<style scoped>
/* tiny loader */
.loader-border {
  border: 2px solid transparent;
  border-top-color: rgba(255,255,255,0.9);
  border-left-color: rgba(255,255,255,0.9);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
