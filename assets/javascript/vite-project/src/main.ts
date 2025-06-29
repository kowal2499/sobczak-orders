import { createApp } from 'vue'
import Tester from "./components/Tester.vue"

const app = createApp({
    components: {
        Tester
    }
})

app.mount('#app')
console.log('test')