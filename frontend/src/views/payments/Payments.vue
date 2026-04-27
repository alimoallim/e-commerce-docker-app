<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../../services/api'

/* --------------------------
   State
-------------------------- */
const payments = ref([])
const orders = ref([])

const showModal = ref(false)
const loading = ref(false)
const fetching = ref(false)

const form = ref({
  order_id: null,
  amount: null,
  method: 'cash',
  status: 'completed'
})

const toast = ref({ show: false, type: 'success', message: '' })
let toastTimer = null

/* --------------------------
   Helpers
-------------------------- */
const formatCurrency = (val) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0)

const formatDate = (dateString) => {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const methodMeta = (method) => {
  const map = {
    cash: { bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-100', icon: 'M12 6v6l4 2' },
    card: { bg: 'bg-blue-50', text: 'text-blue-700', ring: 'ring-blue-100', icon: 'M3 10h18M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z' },
    bank: { bg: 'bg-purple-50', text: 'text-purple-700', ring: 'ring-purple-100', icon: 'M4 8h16M6 8v10m12-10v10M9 12h6' }
  }
  return map[method] || map.cash
}

const statusMeta = (status) => {
  const map = {
    completed: { label: 'Completed', text: 'text-emerald-700', bg: 'bg-emerald-50', ring: 'ring-emerald-100' },
    pending: { label: 'Pending', text: 'text-amber-700', bg: 'bg-amber-50', ring: 'ring-amber-100' },
    failed: { label: 'Failed', text: 'text-rose-700', bg: 'bg-rose-50', ring: 'ring-rose-100' }
  }
  return map[status] || map.completed
}

const showToast = (type, message) => {
  toast.value = { show: true, type, message }
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => (toast.value.show = false), 2500)
}

/* --------------------------
   Data
-------------------------- */
const fetchData = async () => {
  fetching.value = true
  try {
    const [p, o] = await Promise.all([api.get('/payments'), api.get('/orders')])
    payments.value = p.data.data || p.data
    orders.value = o.data.data || o.data
  } catch (err) {
    console.error('Fetch error:', err)
    showToast('error', 'Failed to fetch payments.')
  } finally {
    fetching.value = false
  }
}

const selectedOrder = computed(() =>
  orders.value.find((o) => o.id == form.value.order_id)
)

const remaining = computed(() => Number(selectedOrder.value?.remaining_amount ?? 0))

watch(selectedOrder, (order) => {
  if (order) {
    // Smart default: fill amount with remaining (or keep user value if already entered)
    if (!form.value.amount || Number(form.value.amount) <= 0) {
      form.value.amount = remaining.value
    }
  }
})

const isInvalid = computed(() => {
  const amount = Number(form.value.amount)
  return !form.value.order_id || !Number.isFinite(amount) || amount <= 0 || amount > remaining.value
})

/* --------------------------
   Actions
-------------------------- */
const closeModal = () => {
  showModal.value = false
  form.value = { order_id: null, amount: null, method: 'cash', status: 'completed' }
}

const submit = async () => {
  if (isInvalid.value) return
  loading.value = true
  try {
    await api.post('/payments', {
      order_id: Number(form.value.order_id),
      amount: Number(form.value.amount),
      method: form.value.method,
      status: form.value.status
    })
    await fetchData()
    closeModal()
    showToast('success', 'Payment recorded successfully.')
  } catch (err) {
    console.error(err)
    showToast('error', err?.response?.data?.message || 'Error processing payment')
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<template>
  <div class="min-h-screen bg-slate-50/70 p-4 md:p-8 font-sans text-slate-900">
    <div class="max-w-6xl mx-auto space-y-8">
      <!-- Header -->
      <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
          <div class="flex items-center gap-3">
            <div
              class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-400 shadow-sm flex items-center justify-center"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 .9-4 2s1.79 2 4 2 4 .9 4 2-1.79 2-4 2m0-10V6m0 12v-2" />
              </svg>
            </div>
            <div>
              <h1 class="text-3xl font-extrabold tracking-tight">Payments</h1>
              <p class="text-slate-500 mt-1">A cleaner view of transactions across your orders.</p>
            </div>
          </div>
        </div>

        <button
          @click="showModal = true"
          class="relative inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-all shadow-lg shadow-indigo-200 active:scale-95"
        >
          <span class="absolute inset-0 rounded-xl bg-gradient-to-br from-white/18 to-transparent pointer-events-none"></span>
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 relative" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Payment
        </button>
      </header>

      <!-- Table Card -->
      <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Top bar (innovative: quick insights placeholder) -->
        <div class="px-6 py-4 border-b border-slate-200/60 bg-gradient-to-r from-slate-50 to-white">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-indigo-50 ring-1 ring-indigo-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2-2 2 2 4-4" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19c4.418 0 8-1.79 8-4V6c0-2.21-3.582-4-8-4S4 3.79 4 6v9c0 2.21 3.582 4 8 4z" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-800">Transaction History</p>
                <p class="text-xs text-slate-500">Latest payments are displayed first.</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <div v-if="fetching" class="flex items-center gap-2 text-sm text-slate-600">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-indigo-600 animate-pulse"></span>
                Loading…
              </div>
              <div v-else class="text-xs text-slate-500">
                {{ payments?.length || 0 }} payment(s)
              </div>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/60 border-b border-slate-200">
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Order</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Method</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Paid At</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="p in payments"
                :key="p.id"
                class="group hover:bg-slate-50/60 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 ring-1 ring-indigo-100 flex items-center justify-center">
                      <span class="text-indigo-700 font-bold text-sm">#</span>
                    </div>
                    <span class="font-mono text-sm font-semibold text-indigo-700">
                      {{ p.order?.id ? `#${p.order.id}` : '—' }}
                    </span>
                  </div>
                </td>

                <td class="px-6 py-4">
                  <p class="font-semibold text-slate-800">
                    {{ p.order?.customer?.name || '—' }}
                  </p>
                  <p class="text-xs text-slate-500">
                    {{ p.order?.customer?.email || '' }}
                  </p>
                </td>

                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-900">
                      {{ formatCurrency(p.amount) }}
                    </span>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-slate-50 ring-1 ring-slate-200 text-slate-600">
                      USD
                    </span>
                  </div>
                </td>

                <td class="px-6 py-4">
                  <span
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold ring-1 uppercase tracking-tight"
                    :class="[
                      methodMeta(p.method).bg,
                      methodMeta(p.method).text,
                      methodMeta(p.method).ring
                    ]"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="methodMeta(p.method).icon" />
                    </svg>
                    {{ p.method || '—' }}
                  </span>
                </td>

                <td class="px-6 py-4">
                  <span
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold ring-1"
                    :class="[statusMeta(p.status).bg, statusMeta(p.status).text, statusMeta(p.status).ring]"
                  >
                    <span class="w-2 h-2 rounded-full bg-current opacity-80"></span>
                    {{ (statusMeta(p.status).label || p.status) }}
                  </span>
                </td>

                <td class="px-6 py-4 text-sm text-slate-600">
                  {{ formatDate(p.paid_at) }}
                </td>
              </tr>

              <tr v-if="payments.length === 0 && !fetching">
                <td colspan="6" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center">
                    <div class="p-4 bg-slate-50 rounded-full mb-4 ring-1 ring-slate-200">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </div>
                    <p class="text-slate-500 font-semibold">No payments recorded yet</p>
                    <p class="text-xs text-slate-400 mt-1">Create the first payment to start tracking receipts.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal"></div>

        <div
          class="relative bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200 border border-slate-200"
        >
          <!-- Modal Header -->
          <div class="px-7 pt-7 pb-4 bg-gradient-to-r from-indigo-600 to-indigo-500">
            <div class="flex items-start justify-between gap-4">
              <div class="space-y-1">
                <h2 class="text-2xl font-extrabold text-white">Record Payment</h2>
                <p class="text-indigo-100 text-sm">Fast entry with live balance validation.</p>
              </div>

              <button @click="closeModal" class="text-white/90 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="px-7 pb-7 pt-5 space-y-6">
            <!-- Target Order -->
            <div class="space-y-2">
              <label class="text-sm font-bold text-slate-800">Target Order</label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">⌁</span>
                <select
                  v-model="form.order_id"
                  class="w-full bg-slate-50 border border-slate-200 p-3 pl-10 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none"
                >
                  <option :value="null">Choose an order…</option>
                  <option v-for="o in orders" :key="o.id" :value="o.id">
                    Order #{{ o.id }} — {{ o.customer?.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Live Summary -->
            <Transition name="slide-up">
              <div v-if="selectedOrder" class="rounded-2xl p-5 bg-gradient-to-br from-indigo-50 to-white ring-1 ring-indigo-100">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div class="p-3 rounded-xl bg-white ring-1 ring-slate-200/70">
                    <p class="text-xs uppercase font-bold tracking-wider text-slate-500">Total Amount</p>
                    <p class="mt-1 font-mono font-bold text-slate-900">{{ formatCurrency(selectedOrder.total) }}</p>
                  </div>
                  <div class="p-3 rounded-xl bg-white ring-1 ring-slate-200/70">
                    <p class="text-xs uppercase font-bold tracking-wider text-slate-500">Amount Paid</p>
                    <p class="mt-1 font-mono font-bold text-slate-900">{{ formatCurrency(selectedOrder.paid_amount) }}</p>
                  </div>
                  <div class="p-3 rounded-xl bg-indigo-600 text-white ring-1 ring-indigo-600 shadow-sm">
                    <p class="text-xs uppercase font-bold tracking-wider text-indigo-100">Remaining Balance</p>
                    <p class="mt-1 font-mono font-extrabold text-2xl">{{ formatCurrency(remaining) }}</p>
                  </div>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs text-slate-600">
                  <span class="inline-block w-2 h-2 rounded-full bg-indigo-600"></span>
                  Amount entered will be validated against remaining balance.
                </div>
              </div>
            </Transition>

            <!-- Amount -->
            <div class="space-y-2">
              <label class="text-sm font-bold text-slate-800">Payment Amount</label>

              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">$</span>
                <input
                  type="number"
                  v-model.number="form.amount"
                  class="w-full bg-slate-50 border border-slate-200 p-3 pl-10 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none"
                  placeholder="0.00"
                  step="0.01"
                  min="0"
                />
              </div>

              <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                  Max: <span class="font-semibold text-slate-700">{{ formatCurrency(remaining) }}</span>
                </p>

                <p
                  v-if="form.amount !== null && Number(form.amount) > remaining"
                  class="text-xs text-rose-600 font-semibold flex items-center gap-1.5"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                  Exceeds remaining balance
                </p>
              </div>
            </div>

            <!-- Payment History (kept) -->
            <PaymentHistory :payments="selectedOrder?.payments" />

            <!-- Method -->
            <div class="space-y-2">
              <label class="text-sm font-bold text-slate-800">Payment Method</label>

              <div class="grid grid-cols-3 gap-2 bg-slate-50 p-1 rounded-2xl ring-1 ring-slate-200">
                <button
                  v-for="m in ['cash', 'card', 'bank']"
                  :key="m"
                  @click="form.method = m"
                  :class="[
                    form.method === m
                      ? 'bg-white text-indigo-700 ring-1 ring-indigo-200 shadow-sm'
                      : 'text-slate-600 hover:text-slate-800'
                  ]"
                  class="py-2.5 text-sm font-bold rounded-xl transition-all relative overflow-hidden"
                >
                  <span class="relative z-10 capitalize">{{ m }}</span>
                </button>
              </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center gap-3 pt-2">
              <button
                @click="closeModal"
                class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 rounded-xl transition-colors ring-1 ring-slate-200"
              >
                Cancel
              </button>

              <button
                @click="submit"
                :disabled="loading || isInvalid"
                class="flex-[2] bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white font-extrabold py-3 rounded-xl transition-all shadow-lg shadow-indigo-200 relative overflow-hidden"
              >
                <span
                  class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/15 to-white/0 pointer-events-none"
                  :class="{ 'animate-[shimmer_0.9s_ease-in-out_infinite]': !loading && !isInvalid }"
                ></span>

                <span v-if="!loading" class="relative">
                  Record Transaction
                </span>

                <span v-else class="relative inline-flex items-center justify-center gap-2">
                  <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Processing…
                </span>
              </button>
            </div>

            <p v-if="isInvalid" class="text-xs text-rose-600 font-semibold">
              Please select an order and enter a valid amount within remaining balance.
            </p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Toast -->
    <transition name="toast">
      <div
        v-if="toast.show"
        class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[60] px-4 py-3 rounded-2xl ring-1 shadow-lg"
        :class="toast.type === 'success'
          ? 'bg-emerald-600/95 text-white ring-emerald-200/30'
          : 'bg-rose-600/95 text-white ring-rose-200/30'"
      >
        <div class="flex items-center gap-3">
          <span
            class="w-2.5 h-2.5 rounded-full"
            :class="toast.type === 'success' ? 'bg-white' : 'bg-white'"
          ></span>
          <p class="text-sm font-semibold">{{ toast.message }}</p>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active {
  transition: all 0.26s ease-out;
}
.slide-up-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(10px);
}

/* Custom scrollbar for table */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}

/* Shimmer (optional shimmer animation) */
@keyframes shimmer {
  0% {
    transform: translateX(-60%);
    opacity: 0.2;
  }
  40% {
    opacity: 1;
  }
  100% {
    transform: translateX(60%);
    opacity: 0.2;
  }
}
</style>