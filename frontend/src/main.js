import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import router from './router'

import i18n from './i18n'
import { usePreferencesStore } from './stores/preferences'

const pinia = createPinia()
const app = createApp(App)

import { install as VueMonacoEditorPlugin } from '@guolao/vue-monaco-editor'

app.use(pinia)
app.use(i18n)

// Initialize preferences
const preferences = usePreferencesStore()
preferences.init()

app.use(router)
app.use(VueMonacoEditorPlugin, {
    paths: {
        vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.43.0/min/vs'
    }
})
app.mount('#app')
