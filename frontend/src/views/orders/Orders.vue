<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'

// State
const orders = ref([])
const customers = ref([])
const products = ref([])
const showModal = ref(false)
const loading = ref(false)
const isFetching = ref(true)

const selectedCustomer = ref('')
const items = ref([])
//debug


// Fetch data

const fetchData = async () => {
  isFetching.value = true

  try {
    const [o, c, p] = await Promise.all([
      api.get('/orders'),
      api.get('/customers'),
      api.get('/products')
    ])

    orders.value = o.data.data || o.data
    customers.value = c.data.data || c.data   // ✅ FIX
    products.value = p.data.data || p.data     // ✅ SAFE

  } finally {
    isFetching.value = false
  }
}

// Order Logic
const openModal = () => {
  selectedCustomer.value = ''
  items.value = [{ product_id: '', quantity: 1 }]
  showModal.value = true
}

const addItem = () => {
  items.value.push({ product_id: '', quantity: 1 })
}

const removeItem = (index) => {
  if (items.value.length > 1) items.value.splice(index, 1)
}

const getProduct = (id) => products.value.find(p => p.id === id)

const isDuplicate = (id, index) => {
  if (!id) return false
  return items.value.some((item, i) => item.product_id === id && i !== index)
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val)
}
//validation
const isInvalid = computed(() => {
  if (!selectedCustomer.value) return true
  if (items.value.length === 0) return true

  return items.value.some((item, index) => {
    if (!item.product_id) return true
    if (item.quantity <= 0) return true
    if (isDuplicate(item.product_id, index)) return true

    const product = getProduct(item.product_id)
    if (!product) return true

    // stock validation
    if (item.quantity > product.quantity) return true

    return false
  })
})

// Totals
const total = computed(() => {
  return items.value.reduce((sum, item) => {
    const product = getProduct(item.product_id)
    return sum + (product ? product.price * item.quantity : 0)
  }, 0)
})

const submit = async () => {
  if (!selectedCustomer.value || items.value.some(i => !i.product_id)) {
    alert('Please select a customer and products for all lines.')
    return
  }

  loading.value = true
  try {
    await api.post('/orders', {
      customer_id: selectedCustomer.value,
      items: items.value
    })
    showModal.value = false
    await fetchData()
  } catch (err) {
    alert(err.response?.data?.message || 'Error creating order')
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<template>
  <div class="max-w-6xl mx-auto p-4 sm:p-8">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4">
      <div>
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Sales Orders</h1>
        <p class="text-gray-500 mt-1">Review transaction history and generate new invoices.</p>
      </div>

      <button
        @click="openModal"
        class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 active:scale-95"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        New Order
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Total Orders</p>
        <p class="text-2xl font-bold text-gray-900">{{ orders.length }}</p>
      </div>
      <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Active Customers</p>
        <p class="text-2xl font-bold text-gray-900">{{ customers.length }}</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50/80 border-b border-gray-200">
            <tr>
              <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Order ID</th>
              <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Customer</th>
              <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Amount</th>
              <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Date</th>
              <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Status</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr v-if="isFetching">
              <td colspan="5" class="px-6 py-12 text-center text-gray-400">Loading orders...</td>
            </tr>
            <tr v-for="o in orders" :key="o.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 font-mono text-sm text-blue-600 font-bold">#ORD-{{ o.id }}</td>
              <td class="px-6 py-4">
                <div class="font-bold text-gray-900">{{ o.customer?.name }}</div>
                <div class="text-xs text-gray-400">{{ o.customer?.email }}</div>
              </td>
              <td class="px-6 py-4 font-semibold text-gray-900">{{ formatCurrency(o.total) }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(o.created_at).toLocaleDateString() }}</td>
              <td class="px-6 py-4 text-right">
                <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700">Completed</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Transition name="modal">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-md" @click="showModal = false"></div>
        
        <div class="relative bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
          
          <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Create New Sales Order</h2>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>

          <div class="p-8 overflow-y-auto space-y-8">
            <div>
              <label class="block text-xs font-black text-gray-400 uppercase mb-2 ml-1">Bill To Customer</label>
              <select 
                v-model="selectedCustomer" 
                class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all appearance-none cursor-pointer"
              >
                <option value="">Select a customer...</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <div>
              <div class="grid grid-cols-12 gap-4 mb-2 px-2 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                <div class="col-span-5">Product Details</div>
                <div class="col-span-2 text-center">Qty</div>
                <div class="col-span-2 text-right">Price</div>
                <div class="col-span-2 text-right">Subtotal</div>
                <div class="col-span-1"></div>
              </div>

              <div class="space-y-3">
                <div v-for="(item, i) in items" :key="i" 
                  class="grid grid-cols-12 gap-4 items-center bg-gray-50 p-2 rounded-xl border border-transparent transition-all"
                  :class="{'border-red-200 bg-red-50/30': isDuplicate(item.product_id, i)}"
                >
                  <div class="col-span-5">
                    <select v-model="item.product_id" class="w-full bg-transparent font-bold text-sm outline-none focus:text-blue-600 cursor-pointer">
                      <option value="">Select Product</option>
                      <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <p v-if="isDuplicate(item.product_id, i)" class="text-[10px] text-red-500 font-bold mt-1 uppercase">Duplicate Item</p>
                  </div>

                  <div class="col-span-2">
                    <input type="number" v-model="item.quantity" min="1" class="w-full bg-white border border-gray-200 rounded-lg py-1 px-2 text-center text-sm font-bold shadow-sm" />
                  </div>

                  <div class="col-span-2 text-right text-sm text-gray-500 font-medium">
                    {{ formatCurrency(getProduct(item.product_id)?.price || 0) }}
                  </div>

                  <div class="col-span-2 text-right text-sm font-black text-gray-900">
                    {{ formatCurrency((getProduct(item.product_id)?.price || 0) * item.quantity) }}
                  </div>

                  <div class="col-span-1 text-right">
                    <button @click="removeItem(i)" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
                    </button>
                  </div>
                </div>
              </div>

              <button @click="addItem" class="mt-4 flex items-center text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors ml-1">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                Add Line Item
              </button>
            </div>
          </div>

          <div class="p-8 bg-gray-900 text-white flex flex-col sm:flex-row justify-between items-center gap-6">
            <div>
              <p class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Total Order Amount</p>
              <h3 class="text-4xl font-black">{{ formatCurrency(total) }}</h3>
            </div>

            <div class="flex gap-4 w-full sm:w-auto">
              <button @click="showModal = false" class="flex-1 sm:flex-none px-6 py-3 font-bold text-gray-400 hover:text-white transition-colors">Cancel</button>
              <button
  @click="submit"
  :disabled="loading || isInvalid"
  class="flex-1 sm:flex-none px-8 py-3 bg-blue-500 text-white font-black rounded-xl
         hover:bg-blue-400 transition-all
         disabled:opacity-50 disabled:cursor-not-allowed"
>
  {{ loading ? 'Processing...' : 'Confirm Order' }}
</button>
            </div>
          </div>

        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: translateY(20px) scale(0.98); }
</style>