<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold text-gray-800">Orders</h2>

                <div class="flex items-center gap-3">
                    <div class="text-sm text-gray-600 mr-2">Total</div>
                    <div
                        class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                        {{ orders.total ?? 0 }}
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <input v-model="q" type="search" placeholder="Search order # or user..."
                            class="w-full sm:w-72 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-100" />
                        <button @click="clearSearch" v-if="q"
                            class="text-sm text-gray-500 hover:text-gray-700">Clear</button>
                    </div>

                    <div class="flex items-center gap-2">
                        <select v-model="perPage"
                            class="rounded-md border border-gray-200 bg-white px-2 py-1 text-sm shadow-sm">
                            <option value="10">10 / page</option>
                            <option value="15">15 / page</option>
                            <option value="25">25 / page</option>
                        </select>

                        <button
                        @click="newOrder"
                            class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
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
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Order #
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">User
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Total
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Created
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="o in visibleOrders" :key="o.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ o.id }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ o.order_number }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ o.user?.name ?? '—' }}
                                        <div class="text-xs text-gray-400">{{ o.user?.email ?? '' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-800">${{
                                        o.total }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="statusClass(o.status)"
                                            class="inline-flex items-center rounded-full px-3 py-0.5 text-xs font-medium">
                                            {{ o.status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{
                                        formatDate(o.created_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <a :href="routeShow(o.id)"
                                            class="text-indigo-600 hover:text-indigo-800">View</a>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <button @click="openAction(o)"
                                            class="text-gray-600 hover:text-gray-800">Actions</button>
                                    </td>
                                </tr>

                                <tr v-if="visibleOrders.length === 0">
                                    <td class="px-4 py-6 text-center text-sm text-gray-500" colspan="7">No orders found
                                    </td>
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
                            <button :disabled="!orders.prev_page_url" @click="go(orders.prev_page_url)"
                                class="rounded-md px-3 py-1 text-sm font-medium border bg-white hover:bg-gray-50 disabled:opacity-50">
                                Prev
                            </button>

                            <template v-for="link in orders.links" :key="link.label">
                                <button v-if="link.label && link.url" v-html="link.label" @click="go(link.url)"
                                    :class="['px-3 py-1 text-sm font-medium rounded-md border', link.active ? 'bg-indigo-600 text-white' : 'bg-white']" />
                                <span v-else-if="!link.url" class="px-3 py-1 text-sm text-gray-400"
                                    v-html="link.label"></span>
                            </template>

                            <button :disabled="!orders.next_page_url" @click="go(orders.next_page_url)"
                                class="rounded-md px-3 py-1 text-sm font-medium border bg-white hover:bg-gray-50 disabled:opacity-50">
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
import { defineProps, ref, computed, watch } from 'vue'
import { Inertia } from '@inertiajs/inertia'

// expect orders prop from Inertia (paginator) and filters
const props = defineProps({
    orders: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    filters: { type: Object, default: () => ({}) }
})

const orders = props.orders
const filters = props.filters

// local UI state bound to query params
const q = ref(filters.q ?? '')
const perPage = ref(Number(filters.per_page ?? (orders.per_page ?? 15)))
const status = ref(filters.status ?? '')

// visibleOrders comes directly from server response
const visibleOrders = computed(() => orders.data ?? [])

// helper values for showing range (display only — server provides from/to)
const firstItem = computed(() => orders.from ?? (visibleOrders.value.length ? 1 : 0))
const lastItem = computed(() => orders.to ?? visibleOrders.value.length)

// simple debounce helper (no lodash needed)
function debounce(fn, wait = 300) {
    let t = null
    return (...args) => {
        if (t) clearTimeout(t)
        t = setTimeout(() => fn(...args), wait)
    }
}

// navigation helper for pagination links
function go(url) {
    if (!url) return
    Inertia.visit(url, { preserveState: true, replace: true })
}

// build & visit index route with current filters
function visitWithFilters(extra = {}) {
    const params = {
        q: q.value || undefined,
        per_page: perPage.value || undefined,
        status: status.value || undefined,
        ...extra,
    }

    // remove undefined keys for clean urls
    Object.keys(params).forEach(k => params[k] === undefined && delete params[k])

    // use GET so pagination URLs work and withQueryString in controller stays consistent
    try {
        Inertia.get(route('admin.orders.index'), params, { preserveState: true, replace: true })
    } catch (e) {
        // fallback in case Ziggy route() is not available
        const url = '/admin/orders' + (Object.keys(params).length ? '?' + new URLSearchParams(params).toString() : '')
        Inertia.get(url, {}, { preserveState: true, replace: true })
    }
}

// debounced server visit for search term
const debouncedVisit = debounce(() => visitWithFilters(), 400)

// watch search and call server after debounce
watch(q, (newVal, oldVal) => {
    // only search for meaningful terms or allow clearing to trigger
    if (!newVal || newVal.length >= 2) debouncedVisit()
})

// watch perPage and status — immediate apply
watch(perPage, () => visitWithFilters())
watch(status, () => visitWithFilters())

// clear input + trigger server visit to reset filter
function clearSearch() {
    q.value = ''
    visitWithFilters({ q: undefined })
}

// format date for display (UI-only)
function formatDate(s) {
    if (!s) return '—'
    const d = new Date(s)
    return d.toLocaleString()
}

// status pill classes
function statusClass(statusVal) {
    switch ((statusVal || '').toLowerCase()) {
        case 'requested': return 'bg-yellow-100 text-yellow-800'
        case 'processing': return 'bg-blue-100 text-blue-800'
        case 'completed': return 'bg-green-100 text-green-800'
        case 'cancelled': return 'bg-red-100 text-red-800'
        default: return 'bg-gray-100 text-gray-800'
    }
}

// route helper to build show url
function routeShow(id) {
    try { return route('admin.orders.show', id) } catch (e) { return `/admin/orders/${id}` }
}

// New Order — navigate to create page
function newOrder() {
    try {
        Inertia.get(route('admin.orders.create'))
    } catch (e) {
        Inertia.get('/admin/orders/create')
    }
}

// Actions placeholder — replace with modal later.
// For now it accepts:
// - "status:<value>" e.g. status:processing
// - "assign:<agent_id>" e.g. assign:5
// It posts to your existing update route and refreshes the list on success.
function openAction(order) {
    // minimal prompt-driven fallback for quick ops; swap with your UI modal
    const input = prompt('Action (examples):\nstatus:processing\nstatus:completed\nassign:AGENT_ID\ncancel (alias for status:cancelled)\n\nLeave empty to cancel.')
    if (!input) return

    const trimmed = input.trim()
    if (trimmed.toLowerCase() === 'cancel') {
        // convert to status:cancelled
        performUpdate(order.id, { status: 'cancelled' })
        return
    }

    if (trimmed.startsWith('status:')) {
        const s = trimmed.split(':')[1]
        performUpdate(order.id, { status: s })
        return
    }

    if (trimmed.startsWith('assign:')) {
        const a = trimmed.split(':')[1]
        if (!a) { alert('Missing agent id'); return }
        performUpdate(order.id, { agent_id: a })
        return
    }

    alert('Unknown action format.')
}

// helper to POST update and refresh list on success
function performUpdate(orderId, payload) {
    try {
        Inertia.post(route('admin.orders.update', orderId), payload, {
            onSuccess: () => {
                // refresh current list with same filters
                visitWithFilters()
            },
            onError: (err) => {
                // show minimal feedback; replace with toast in UI
                console.error('Update failed', err)
                alert('Action failed. See console for details.')
            }
        })
    } catch (e) {
        // fallback: build url manually
        Inertia.post(`/admin/orders/${orderId}`, payload, {
            onSuccess: () => visitWithFilters()
        })
    }
}
</script>
