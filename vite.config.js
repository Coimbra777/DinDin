import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue2'

export default defineConfig({
  // Evita EACCES quando node_modules/.vite foi criado como root (ex.: Docker)
  cacheDir: '.vite-cache',
  resolve: {
    alias: {
      '@': '/public/',
      'vue': process.env.NODE_ENV == 'production' ? 'vue/dist/vue.min.js' : 'vue/dist/vue.js'
    },
  },
  server: {
    host: true,
    hmr: {
      host: 'localhost',
    },
  },
  plugins: [
    laravel({
      input: [
        'resources/assets/sass/app.scss',
        'resources/assets/js/cms/app.js',
        'resources/assets/js/cms/auth-app.js',
        'resources/assets/js/app.js',
        'resources/assets/sass/website/app.scss',
        'resources/assets/js/front/app.js',
        'resources/assets/js/finance/finance-app.js',
        'resources/assets/js/admin/admin-app.js',
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          includeAbsolute: false
        }
      }
    }),
  ],
})
