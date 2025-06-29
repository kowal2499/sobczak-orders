import {defineConfig} from 'vite'
import symfonyPlugin from "vite-plugin-symfony";
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        vue(),
        symfonyPlugin(),
    ],

    // włącza kompilowanie szablonu w runtime, czyli zwróci istniejącą treść
    // komponentu w którym osadzono aplikację i wyrenderuje znane komponenty vue
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js'
        }
    },

    build: {
        outDir: '../../../public',



        rollupOptions: {
            input: {
                app: './src/main.ts',
            }
        },
    },
})
