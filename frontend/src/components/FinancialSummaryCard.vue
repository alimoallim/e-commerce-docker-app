<script setup>
import { computed } from 'vue'

const props = defineProps({
  order: Object
})

// Safe numeric values
const total = computed(() => Number(props.order?.total ?? 0))
const paid = computed(() => Number(props.order?.paid_amount ?? 0))
const remaining = computed(() => Number(props.order?.remaining_amount ?? 0))

// Progress %
const progress = computed(() => {
  if (!total.value) return 0
  return Math.min((paid.value / total.value) * 100, 100)
})

// Status
const status = computed(() => {
  if (paid.value === 0) return 'unpaid'
  if (paid.value < total.value) return 'partial'
  return 'paid'
})

// Badge color
const statusColor = computed(() => {
  switch (status.value) {
    case 'paid': return 'bg-green-500'
    case 'partial': return 'bg-yellow-500'
    default: return 'bg-red-500'
  }
})
</script>

<template>
  <div v-if="order" class="bg-white p-4 rounded-xl shadow space-y-3">

    <!-- Header -->
    <div class="flex justify-between items-center">
      <h3 class="font-semibold text-gray-700">Financial Summary</h3>

      <span
        class="text-xs text-white px-2 py-1 rounded"
        :class="statusColor"
      >
        {{ status.toUpperCase() }}
      </span>
    </div>

    <!-- Numbers -->
    <div class="grid grid-cols-3 gap-3 text-sm">

      <div>
        <p class="text-gray-400">Total</p>
        <p class="font-bold">${{ total.toFixed(2) }}</p>
      </div>

      <div>
        <p class="text-gray-400">Paid</p>
        <p class="font-bold text-green-600">${{ paid.toFixed(2) }}</p>
      </div>

      <div>
        <p class="text-gray-400">Remaining</p>
        <p class="font-bold text-blue-600">${{ remaining.toFixed(2) }}</p>
      </div>

    </div>

    <!-- Progress Bar -->
    <div class="w-full bg-gray-200 rounded-full h-2">
      <div
        class="h-2 rounded-full transition-all"
        :class="statusColor"
        :style="{ width: progress + '%' }"
      ></div>
    </div>

    <!-- Percentage -->
    <div class="text-right text-xs text-gray-500">
      {{ progress.toFixed(0) }}% paid
    </div>

  </div>
</template>
