import {defineConfig} from 'vite'
import symfonyPlugin from "vite-plugin-symfony";
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        vue(),
        symfonyPlugin(),
    ],
    build: {
        outDir: '../../../public/build',

        rollupOptions: {
            input: {
                app: './src/main.ts',
            }

        },
    },
})
