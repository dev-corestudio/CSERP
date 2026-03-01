import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { useMetadataStore } from './stores/metadata'
import { useAuthStore } from './stores/auth'
import './assets/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

const authStore = useAuthStore()

// TypeScript wymaga obsłużenia Promise
authStore.initialize().then(async () => {
  const metadataStore = useMetadataStore()
  await metadataStore.fetchMetadata()

  app.use(router)
  app.use(vuetify)
  app.mount('#app')
})