<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Edit Country</h2>
                    <p class="text-sm text-gray-500 mt-1">Update country details and mapping.</p>
                </div>

                <div class="flex items-center gap-3">
                    <inertia-link :href="route('admin.countries.index')"
                        class="text-sm px-3 py-2 border rounded hover:bg-gray-50">Back to list</inertia-link>
                </div>
            </div>
        </template>

        <div class="mt-6 max-w-3xl mx-auto bg-white rounded shadow-sm border overflow-hidden p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input v-model="form.name" type="text" class="mt-1 block w-full border rounded px-3 py-2" />
                    <p v-if="errors.name" class="text-red-600 text-sm mt-1">{{ errors.name }}</p>
                </div>

                <!-- ISO2 / ISO3 -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ISO2</label>
                        <input v-model="form.iso2" type="text" maxlength="2"
                            class="mt-1 block w-full border rounded px-3 py-2 uppercase" />
                        <p v-if="errors.iso2" class="text-red-600 text-sm mt-1">{{ errors.iso2 }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">ISO3</label>
                        <input v-model="form.iso3" type="text" maxlength="3"
                            class="mt-1 block w-full border rounded px-3 py-2 uppercase" />
                        <p v-if="errors.iso3" class="text-red-600 text-sm mt-1">{{ errors.iso3 }}</p>
                    </div>
                </div>

                <!-- Currency -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Currency</label>

                    <!-- If controller provided a currencies list, show select -->
                    <select v-if="currencies && currencies.length" v-model="form.currency_id"
                        class="mt-1 block w-full border rounded px-3 py-2">
                        <option :value="null">— None —</option>
                        <option v-for="cur in currencies" :key="cur.id" :value="cur.id">
                            {{ cur.code }} — {{ cur.name }}
                        </option>
                    </select>

                    <!-- Otherwise show read-only current currency info -->
                    <div v-else class="mt-1">
                        <input type="text"
                            :value="country.currency?.code ? (country.currency.code + ' — ' + (country.currency.name ?? '')) : '—'"
                            readonly class="block w-full border rounded px-3 py-2 bg-gray-50" />
                        <p class="text-xs text-gray-500 mt-1">No currency list provided — edit will keep current mapping
                            unless
                            you pass currencies to the page.</p>
                    </div>

                    <p v-if="errors.currency_id" class="text-red-600 text-sm mt-1">{{ errors.currency_id }}</p>
                </div>

                <!-- Timezone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Timezone</label>
                    <input v-model="form.timezone" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                        placeholder="Asia/Kolkata" />
                    <p v-if="errors.timezone" class="text-red-600 text-sm mt-1">{{ errors.timezone }}</p>
                </div>

                <!-- Active -->
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.active" class="h-4 w-4" />
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="processing"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-60">
                        <span v-if="processing">Saving…</span>
                        <span v-else>Save</span>
                    </button>

                    <inertia-link :href="route('admin.countries.index')"
                        class="inline-flex items-center px-4 py-2 border rounded text-sm">Cancel</inertia-link>
                </div>

            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/inertia-vue3'
import { Inertia } from '@inertiajs/inertia'

const props = defineProps({
    country: { type: Object, required: true },
    currencies: { type: Array, default: () => [] },
})

const country = props.country
const currencies = props.currencies

// Initialize the form with existing country values
const form = useForm({
    name: country.name ?? '',
    iso2: country.iso2 ?? '',
    iso3: country.iso3 ?? '',
    currency_id: country.currency_id ?? null,
    timezone: country.timezone ?? '',
    active: country.active ?? true,
})

const errors = computed(() => form.errors || {})
const processing = computed(() => form.processing)

// submit handler
async function submit() {
    // route helper fallback if route() not defined
    const url = (() => {
        try { return route('admin.countries.update', country.id) } catch { return `/admin/countries/${country.id}` }
    })()

    await form.put(url, {
        preserveState: true,
        onSuccess: (page) => {
            // On success Inertia will redirect — you may show a toast here if you have one.
        },
        onError: (errs) => {
            // errors are automatically populated in form.errors
            console.debug('Validation errors', errs)
        }
    })
}
</script>

<style scoped>
/* small niceties only */
</style>
