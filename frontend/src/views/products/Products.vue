<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

// State
const products = ref([])
const showModal = ref(false)
const editingId = ref(null)
const errors = ref({})
const loading = ref(false)
const isFetching = ref(true)

const defaultForm = {
  name: '',
  quantity: '',
  price: '',
  description: ''
}

const form = ref({ ...defaultForm })

// Fetch
const fetchProducts = async (page = 1) => {
  isFetching.value = true

  try {
    const res = await api.get(`/products?page=${page}`)

    products.value = res.data.data   // ✅ FIX
    currentPage.value = res.data.current_page
    lastPage.value = res.data.last_page

  } finally {
    isFetching.value = false
  }
}

// Modal Management
const openModal = (product = null) => {
  errors.value = {}
  
  if (product) {
    editingId.value = product.id
    form.value = { ...product }
  } else {
    editingId.value = null
    form.value = { ...defaultForm }
  }
  
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

// Submit
const submit = async () => {
  loading.value = true
  errors.value = {}

  try {
    if (editingId.value) {
      const res = await api.put(`/products/${editingId.value}`, form.value)
      const index = products.value.findIndex(p => p.id === editingId.value)
      if (index !== -1) products.value[index] = res.data
    } else {
      const res = await api.post('/products', form.value)
      products.value.push(res.data)
    }
    closeModal()
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

// Delete
const deleteProduct = async (id) => {
  if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) return
  
  try {
    await api.delete(`/products/${id}`)
    products.value = products.value.filter(p => p.id !== id)
  } catch (error) {
    console.error("Failed to delete product:", error)
  }
}

// Utility for currency formatting
const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

onMounted(fetchProducts)
</script>

<template>
  <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8 text-gray-800">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Products</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your inventory, pricing, and details.</p>
      </div>

      <button
        @click="openModal()"
        class="inline-flex items-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-sm"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        Add Product
      </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left whitespace-nowrap">
          <thead class="bg-gray-50/80 border-b border-gray-200">
            <tr>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Product Name</th>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock Qty</th>
              <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit Price</th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr v-if="isFetching">
              <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                Loading products...
              </td>
            </tr>

            <tr v-else-if="products.length === 0">
              <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                No products found. Click "Add Product" to create one.
              </td>
            </tr>

            <tr 
              v-else 
              v-for="p in products" 
              :key="p.id" 
              class="hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ p.name }}</div>
                <div class="text-sm text-gray-500 truncate max-w-xs">{{ p.description || 'No description' }}</div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                <span :class="{'text-red-600 font-medium': p.quantity <= 5}">{{ p.quantity }}</span>
              </td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">
                {{ formatPrice(p.price) }}
              </td>

              <td class="px-6 py-4 text-right space-x-3 text-sm">
                <button
                  @click="openModal(p)"
                  class="font-medium text-indigo-600 hover:text-indigo-900 transition-colors"
                >
                  Edit
                </button>
                <button
                  @click="deleteProduct(p.id)"
                  class="font-medium text-red-600 hover:text-red-900 transition-colors"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0"
      >
        <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <div class="relative bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden transform transition-all p-6 sm:p-8">
          
          <h2 class="text-xl font-bold text-gray-900 mb-6">
            {{ editingId ? 'Edit Product' : 'Create New Product' }}
          </h2>

          <div class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Product Name</label>
              <input 
                v-model="form.name" 
                placeholder="e.g. Wireless Mouse"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors sm:text-sm" 
              />
              <p v-if="errors.name" class="mt-1.5 text-sm text-red-500">{{ errors.name[0] }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Quantity</label>
                <input 
                  v-model="form.quantity" 
                  type="number" 
                  placeholder="0"
                  class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors sm:text-sm" 
                />
                <p v-if="errors.quantity" class="mt-1.5 text-sm text-red-500">{{ errors.quantity[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Unit Price ($)</label>
                <input 
                  v-model="form.price" 
                  type="number" 
                  step="0.01"
                  placeholder="0.00"
                  class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors sm:text-sm" 
                />
                <p v-if="errors.price" class="mt-1.5 text-sm text-red-500">{{ errors.price[0] }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Description (Optional)</label>
              <textarea 
                v-model="form.description" 
                rows="3"
                placeholder="Briefly describe the product..."
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors sm:text-sm resize-none" 
              ></textarea>
            </div>
          </div>

          <div class="mt-8 flex justify-end gap-3">
            <button
              @click="closeModal"
              class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors"
            >
              Cancel
            </button>

            <button
              @click="submit"
              :disabled="loading"
              class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-70 disabled:cursor-not-allowed min-w-[100px]"
            >
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving
              </span>
              <span v-else>Save Product</span>
            </button>
          </div>

        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* Optional: CSS rules for the Vue <Transition name="modal"> */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.25s ease-out;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95) translateY(10px);
}
</style>