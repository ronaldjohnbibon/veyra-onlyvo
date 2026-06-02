import './bootstrap'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import AdminLayout from '@/admin/layouts/AdminLayout.vue'
import DefaultLayout from '@/shared/layouts/DefaultLayout.vue'
import EmptyLayout from '@/shared/layouts/EmptyLayout.vue'
import TenantLayout from '@/tenant/layouts/TenantLayout.vue'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.component('admin-layout', AdminLayout)
app.component('default-layout', DefaultLayout)
app.component('empty-layout', EmptyLayout)
app.component('tenant-layout', TenantLayout)

app.mount('#app')
