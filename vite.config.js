import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
  
                
                'resources/js/app.js',
  
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            // Указываем явные пути к зависимостям, если нужно
            '~bootstrap': 'node_modules/bootstrap',
        }
    }
});
