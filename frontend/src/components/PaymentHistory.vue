<script setup>
const props = defineProps({
  payments: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <div class="space-y-4 mt-4">

    <h3 class="font-semibold text-gray-700">Payment History</h3>

    <!-- Empty state -->
    <div v-if="payments.length === 0"
         class="text-gray-400 text-sm">
      No payments yet
    </div>

    <!-- Timeline -->
    <div v-for="(p, index) in payments"
         :key="p.id"
         class="flex gap-3 items-start">

      <!-- Timeline indicator -->
      <div class="flex flex-col items-center">
        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
        <div v-if="index !== payments.length - 1"
             class="w-px h-10 bg-gray-300"></div>
      </div>

      <!-- Content -->
      <div class="bg-gray-50 p-3 rounded w-full shadow-sm">

        <div class="flex justify-between">
          <span class="font-semibold text-green-600">
            + ${{ Number(p.amount).toFixed(2) }}
          </span>

          <span class="text-xs text-gray-400">
            {{ new Date(p.paid_at).toLocaleString() }}
          </span>
        </div>

        <div class="text-sm text-gray-600 mt-1">
          Method: {{ p.method }}
        </div>

        <div class="text-xs text-gray-400">
          Status: {{ p.status }}
        </div>

      </div>

    </div>

  </div>
</template>
