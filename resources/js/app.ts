import './bootstrap'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import AdminLayout from '@/components/layouts/AdminLayout.vue'
import DefaultLayout from '@/components/layouts/DefaultLayout.vue'
import EmptyLayout from '@/components/layouts/EmptyLayout.vue'
import TenantLayout from '@/components/layouts/TenantLayout.vue'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.component('admin-layout', AdminLayout)
app.component('default-layout', DefaultLayout)
app.component('empty-layout', EmptyLayout)
app.component('tenant-layout', TenantLayout)

app.mount('#app')
