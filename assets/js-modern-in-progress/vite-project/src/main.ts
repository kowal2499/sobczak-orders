import { createApp } from 'vue'
import Tester from "./components/Tester.vue"
import '../../../css/app.scss'
import 'bootstrap/dist/css/bootstrap.css'
const app = createApp({
    components: {
        tester: Tester
    },
})

app.mount('#app')
