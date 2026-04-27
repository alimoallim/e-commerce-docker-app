<script setup>
import { ref, onMounted, computed } from 'vue'
import {
  DollarSign,
  ShoppingCart,
  Users,
  AlertCircle,
  RefreshCw,
  TrendingUp,
  ShieldCheck,
  CreditCard,
  AlertTriangle,
  CheckCircle2,
  Loader2
} from 'lucide-vue-next'
import api from '../../services/api'

const data = ref(null)
const loading = ref(false)

const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(Number(val ?? 0))
}

const fetchDashboard = async () => {
  loading.value = true
  try {
    const res = await api.get('/dashboard')
    data.value = res.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboard)

// Status badge styling
const getStatusStyles = (status) => {
  const styles = {
    Paid: 'bg-emerald-50 text-emerald-700 border-emerald-100',
    Partial: 'bg-amber-50 text-amber-700 border-amber-100',
    Unpaid: 'bg-rose-50 text-rose-700 border-rose-100'
  }
  return styles[status] || 'bg-slate-50 text-slate-700 border-slate-100'
}

const initials = (name = '') => {
  const parts = String(name).trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '--'
  return parts.slice(0, 2).map(p => p[0]?.toUpperCase()).join('')
}

const revenueProgress = computed(() => {
  // Optional “professional” extra metric if the API provides it; fallback to a safe default.
  const paid = Number(data.value?.orders?.paid ?? 0)
  const total = paid + Number(data.value?.orders?.partial ?? 0) + Number(data.value?.orders?.unpaid ?? 0)
  const pct = total > 0 ? Math.round((paid / total) * 100) : 0
  return { pct }
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <!-- Page header -->
    <div class="px-4 sm:px-6 lg:px-8 pt-8 pb-6">
      <div class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-white border border-slate-200 px-3 py-1 shadow-sm">
              <ShieldCheck class="w-4 h-4 text-blue-600" />
              <span class="text-xs font-bold text-blue-700 tracking-wide">EXECUTIVE OVERVIEW</span>
            </div>
            <h1 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">
              Executive Overview
            </h1>
            <p class="mt-1 text-sm text-slate-500">
              Real-time performance metrics, fulfillment health, and recent activity.
            </p>
          </div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="fetchDashboard"
              class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition active:scale-[0.99]"
              :disabled="loading"
            >
              <RefreshCw
                v-if="loading"
                class="w-4 h-4 text-slate-500 animate-spin"
              />
              <RefreshCw v-else class="w-4 h-4 text-slate-500" />
              <span>{{ loading ? 'Refreshing...' : 'Refresh Data' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main -->
    <div class="px-4 sm:px-6 lg:px-8 pb-12">
      <div class="mx-auto max-w-7xl">
        <!-- Skeleton / loading -->
        <div v-if="!data && loading" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
              <div class="h-3 w-28 bg-slate-200 rounded animate-pulse" />
              <div class="mt-4 h-8 w-32 bg-slate-200 rounded animate-pulse" />
              <div class="mt-6 h-10 w-full bg-slate-100 rounded animate-pulse" />
            </div>
          </div>
          <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
              <div class="h-4 w-40 bg-slate-200 rounded animate-pulse" />
              <div class="mt-5 space-y-3">
                <div v-for="j in 5" :key="j" class="h-14 bg-slate-100 rounded animate-pulse" />
              </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
              <div class="h-4 w-44 bg-slate-200 rounded animate-pulse" />
              <div class="mt-5 space-y-4">
                <div v-for="j in 3" :key="j" class="h-10 bg-slate-100 rounded animate-pulse" />
              </div>
            </div>
          </div>
        </div>

        <template v-else-if="data">
          <!-- KPI row -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div
              v-for="kpi in [
                { label: 'Total Revenue', val: formatCurrency(data.metrics.revenue), icon: DollarSign, color: 'text-emerald-700', bg: 'bg-emerald-50', border: 'border-emerald-100' },
                { label: 'Active Orders', val: data.metrics.orders, icon: ShoppingCart, color: 'text-blue-700', bg: 'bg-blue-50', border: 'border-blue-100' },
                { label: 'Total Customers', val: data.metrics.customers, icon: Users, color: 'text-indigo-700', bg: 'bg-indigo-50', border: 'border-indigo-100' },
                { label: 'Outstanding', val: formatCurrency(data.metrics.outstanding), icon: AlertCircle, color: 'text-rose-700', bg: 'bg-rose-50', border: 'border-rose-100' },
              ]"
              :key="kpi.label"
              class="relative overflow-hidden bg-white p-6 rounded-2xl border shadow-sm hover:shadow-md transition-shadow"
              :class="[kpi.border]"
            >
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-xs font-extrabold uppercase tracking-widest text-slate-500">
                    {{ kpi.label }}
                  </p>
                  <h3 class="mt-2 text-2xl font-black tracking-tight">
                    {{ kpi.val }}
                  </h3>
                </div>

                <div :class="['p-2 rounded-xl border', kpi.bg, kpi.border]">
                  <component :is="kpi.icon" class="w-5 h-5" :class="kpi.color" />
                </div>
              </div>

              <div class="mt-5">
                <div class="h-1.5 w-3/4 rounded-full bg-slate-100 overflow-hidden">
                  <div
                    class="h-full rounded-full"
                    :class="kpi.bg.replace('bg-', 'bg-')"
                    style="width: 65%; background: #3b82f6;"
                  />
                </div>
              </div>
              <!-- subtle corner glow -->
              <div class="pointer-events-none absolute -right-16 -top-16 w-40 h-40 rounded-full blur-2xl opacity-30"
                   :class="kpi.bg" />
            </div>
          </div>

          <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Left: Transactions -->
            <div class="xl:col-span-2 space-y-8">
              <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
                      <TrendingUp class="w-5 h-5 text-blue-700" />
                    </div>
                    <div>
                      <h2 class="font-extrabold text-slate-800">Recent Transactions</h2>
                      <p class="text-xs text-slate-500 mt-0.5">Latest verified orders and customers</p>
                    </div>
                  </div>

                  <button class="text-xs font-extrabold text-blue-700 hover:underline">
                    View All
                  </button>
                </div>

                <div class="divide-y divide-slate-100">
                  <div
                    v-for="o in data.orders.recent"
                    :key="o.id"
                    class="p-4 hover:bg-slate-50 transition-colors flex justify-between items-center"
                  >
                    <div class="flex items-center gap-4 min-w-0">
                      <div
                        class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-extrabold text-slate-700 text-xs border border-slate-200 flex-shrink-0"
                      >
                        {{ initials(o.customer?.name) }}
                      </div>

                      <div class="min-w-0">
                        <p class="text-sm font-extrabold text-slate-900 truncate">
                          {{ o.customer?.name }}
                        </p>
                        <p class="text-xs text-slate-400 font-medium">
                          Order #{{ o.id }}
                        </p>
                      </div>
                    </div>

                    <div class="text-right flex flex-col items-end gap-0.5">
                      <p class="text-sm font-extrabold text-slate-900">
                        {{ formatCurrency(o.total) }}
                      </p>
                      <div class="inline-flex items-center gap-1">
                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wide">
                          Verified
                        </p>
                      </div>
                    </div>
                  </div>

                  <div v-if="!data.orders?.recent?.length" class="p-6 text-sm text-slate-500">
                    No recent transactions available.
                  </div>
                </div>
              </section>
            </div>

            <!-- Right: Fulfillment -->
            <div class="space-y-8">
              <section class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <h2 class="font-extrabold text-slate-800">Fulfillment Status</h2>
                    <p class="text-xs text-slate-500 mt-1">Paid vs Partial vs Unpaid breakdown</p>
                  </div>

                  <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                    <ShieldCheck class="w-5 h-5 text-emerald-700" />
                  </div>
                </div>

                <!-- Optional progress -->
                <div class="mt-6 p-4 rounded-xl border border-slate-100 bg-slate-50">
                  <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-extrabold text-slate-600 uppercase tracking-widest">
                      Payment Health
                    </p>
                    <p class="text-xs font-extrabold text-slate-700">
                      {{ revenueProgress.pct }}% Paid
                    </p>
                  </div>
                  <div class="h-2.5 bg-slate-200 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-emerald-600 rounded-full"
                      :style="{ width: `${revenueProgress.pct}%` }"
                    />
                  </div>
                  <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                    <span>On-track</span>
                    <span class="font-bold text-emerald-700">{{ revenueProgress.pct }}%</span>
                  </div>
                </div>

                <div class="mt-6 space-y-4">
                  <div
                    v-for="status in [
                      { label: 'Paid', val: data.orders.paid, icon: CheckCircle2 },
                      { label: 'Partial', val: data.orders.partial, icon: AlertTriangle },
                      { label: 'Unpaid', val: data.orders.unpaid, icon: AlertCircle },
                    ]"
                    :key="status.label"
                    class="flex justify-between items-center gap-4"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <span
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border bg-white"
                        :class="getStatusStyles(status.label)"
                      >
                        <component :is="status.icon" class="w-4 h-4" />
                      </span>

                      <span
                        :class="[
                          'px-2.5 py-1 rounded-lg text-xs font-extrabold border truncate',
                          getStatusStyles(status.label)
                        ]"
                      >
                        {{ status.label }}
                      </span>
                    </div>

                    <span class="text-sm font-black text-slate-800">
                      {{ status.val }}
                    </span>
                  </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100">
                  <div class="flex items-center justify-between gap-4 mb-4">
                    <h4 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest">
                      Top Payment Methods
                    </h4>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
                      <CreditCard class="w-4 h-4 text-blue-700" />
                    </div>
                  </div>

                  <div class="space-y-3">
                    <div
                      v-for="m in data.payments.by_method"
                      :key="m.method"
                      class="flex items-center justify-between gap-4"
                    >
                      <span class="text-sm font-bold text-slate-700 truncate">
                        {{ m.method }}
                      </span>

                      <div class="flex items-center gap-3">
                        <div class="w-28 h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                          <div
                            class="h-full bg-blue-600 rounded-full"
                            :style="{ width: '65%' }"
                          />
                        </div>
                        <span class="text-xs font-extrabold text-slate-800 whitespace-nowrap">
                          {{ formatCurrency(m.total) }}
                        </span>
                      </div>
                    </div>

                    <div v-if="!data.payments?.by_method?.length" class="text-sm text-slate-500">
                      No payment breakdown available.
                    </div>
                  </div>
                </div>
              </section>
            </div>
          </div>
        </template>

        <!-- If API returned null and not loading -->
        <div v-else class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8 text-slate-600">
          <div class="flex items-start gap-3">
            <Loader2 class="w-5 h-5 text-slate-400 mt-0.5" />
            <div>
              <p class="font-extrabold text-slate-800">Unable to load dashboard.</p>
              <p class="text-sm text-slate-500 mt-1">
                Please try refreshing the data.
              </p>
            </div>
          </div>
          <div class="mt-5">
            <button
              type="button"
              @click="fetchDashboard"
              class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-extrabold hover:bg-blue-700 transition active:scale-[0.99]"
            >
              Refresh Dashboard
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>