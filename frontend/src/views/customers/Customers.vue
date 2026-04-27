<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const customers = ref([])
const showModal = ref(false)
const editingId = ref(null)
const errors = ref({})
const loading = ref(false)
const isFetching = ref(true)

const currentPage = ref(1)
const lastPage = ref(1)

const defaultForm = {
  name: '',
  email: '',
  phone: '',
  address: ''
}

const form = ref({ ...defaultForm })

const fetchCustomers = async (page = 1) => {
  if (page < 1 || (lastPage.value && page > lastPage.value)) return
  isFetching.value = true
  try {
    const res = await api.get(`/customers?page=${page}`)
    customers.value = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value = res.data.last_page
  } finally {
    isFetching.value = false
  }
}

const openModal = (c = null) => {
  errors.value = {}
  if (c) {
    editingId.value = c.id
    form.value = { ...c }
  } else {
    editingId.value = null
    form.value = { ...defaultForm }
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const submit = async () => {
  loading.value = true
  errors.value = {}
  try {
    if (editingId.value) {
      await api.put(`/customers/${editingId.value}`, form.value)
    } else {
      await api.post('/customers', form.value)
    }
    closeModal()
    await fetchCustomers(currentPage.value)
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

const deleteCustomer = async (id) => {
  const c = customers.value.find(x => x.id === id)
  if (!confirm(`Are you sure you want to delete "${c?.name}"?`)) return
  await api.delete(`/customers/${id}`)
  await fetchCustomers(currentPage.value)
}

// Helper for Avatars
const getInitials = (name) => name?.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || '?'

onMounted(() => fetchCustomers())
</script>

<template>
  <div class="max-w-6xl mx-auto p-4 sm:p-8 text-gray-900">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
      <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">Customers</h1>
        <p class="text-gray-500 mt-1">Keep track of your client relationships and contact details.</p>
      </div>

      <button 
        @click="openModal()" 
        class="inline-flex items-center justify-center px-5 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-sm active:scale-95"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        Add Customer
      </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-100">
              <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Client</th>
              <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Contact info</th>
              <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider text-right">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-50">
            <tr v-if="isFetching">
              <td colspan="3" class="px-6 py-20 text-center">
                <div class="flex flex-col items-center">
                  <div class="w-10 h-10 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin"></div>
                  <p class="mt-4 text-gray-400 font-medium">Refreshing list...</p>
                </div>
              </td>
            </tr>

            <tr v-else-if="customers.length === 0">
              <td colspan="3" class="px-6 py-20 text-center text-gray-400">
                No customers found.
              </td>
            </tr>

            <tr v-for="c in customers" :key="c.id" class="hover:bg-indigo-50/30 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                    {{ getInitials(c.name) }}
                  </div>
                  <div>
                    <div class="font-bold text-gray-900 group-hover:text-indigo-700 transition-colors">{{ c.name }}</div>
                    <div class="text-xs text-gray-500">{{ c.address || 'No address' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-700 font-medium">{{ c.email }}</div>
                <div class="text-xs text-gray-400">{{ c.phone }}</div>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-4">
                  <button @click="openModal(c)" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">Edit</button>
                  <button @click="deleteCustomer(c.id)" class="text-sm font-bold text-red-500 hover:text-red-700">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
        <span class="text-sm text-gray-500">
          Page <span class="font-bold text-gray-900">{{ currentPage }}</span> of {{ lastPage }}
        </span>
        
        <div class="flex gap-2">
          <button 
            @click="fetchCustomers(currentPage - 1)" 
            :disabled="currentPage === 1"
            class="px-4 py-2 text-sm font-medium bg-white border border-gray-200 rounded-lg shadow-xs hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >
            Previous
          </button>
          <button 
            @click="fetchCustomers(currentPage + 1)" 
            :disabled="currentPage === lastPage"
            class="px-4 py-2 text-sm font-medium bg-white border border-gray-200 rounded-lg shadow-xs hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200">
          <div class="p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
              {{ editingId ? 'Edit Profile' : 'New Customer' }}
            </h2>

            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Full Name</label>
                <input v-model="form.name" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all placeholder-gray-400" placeholder="John Doe" />
                <p v-if="errors.name" class="text-xs text-red-500 mt-1 ml-1">{{ errors.name[0] }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Email</label>
                  <input v-model="form.email" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all placeholder-gray-400" placeholder="john@example.com" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Phone</label>
                  <input v-model="form.phone" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all placeholder-gray-400" placeholder="+1 234..." />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Work Address</label>
                <input v-model="form.address" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all placeholder-gray-400" placeholder="Street, City, Country" />
              </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
              <button @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
              <button 
                @click="submit" 
                :disabled="loading" 
                class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 disabled:opacity-50 transition-all"
              >
                {{ loading ? 'Saving...' : 'Save Profile' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>