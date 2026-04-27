import { createRouter, createWebHistory } from 'vue-router'

// Layout
import MainLayout from '../layouts/MainLayout.vue'

// Views
import Products from '../views/products/Products.vue'
import Customers from '../views/customers/Customers.vue'
import Orders from '../views/orders/Orders.vue'
import Payments from '../views/payments/Payments.vue'
import Dashboard from '../views/dashboard/Dashboard.vue'




const routes = [
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', redirect: '/dashboard' },   // ✅ change default

      { path: '/dashboard', component: Dashboard },  // ✅ ADD THIS

      { path: '/products', component: Products },
      { path: '/customers', component: Customers },
      { path: '/orders', component: Orders },
      { path: '/payments', component: Payments },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
