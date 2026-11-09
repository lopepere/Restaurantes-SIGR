import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'SIGR';

// Validación global en toda la plataforma para impedir cantidades o montos negativos en campos numéricos
if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    document.addEventListener(
        'keydown',
        (e) => {
            const target = e.target;
            if (target instanceof HTMLInputElement && target.type === 'number') {
                if (e.key === '-' || e.key === 'Subtract' || e.key === 'e' || e.key === 'E') {
                    e.preventDefault();
                }
            }
        },
        true
    );

    document.addEventListener(
        'paste',
        (e) => {
            const target = e.target;
            if (target instanceof HTMLInputElement && target.type === 'number') {
                const pasted = e.clipboardData?.getData('text') ?? '';
                if (pasted.includes('-') || Number(pasted) < 0) {
                    e.preventDefault();
                    const limpio = pasted.replace(/[^0-9.]/g, '');
                    if (limpio !== '') {
                        target.value = limpio;
                        target.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            }
        },
        true
    );

    const sanitizarNumeroNoNegativo = (e) => {
        const target = e.target;
        if (target instanceof HTMLInputElement && target.type === 'number') {
            if (target.min === '' || Number(target.min) < 0) {
                target.min = '0';
            }
            if (target.value !== '' && (target.value.includes('-') || Number(target.value) < 0)) {
                const minimo = Math.max(0, Number(target.min) || 0);
                target.value = String(minimo);
                target.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    };

    document.addEventListener('input', sanitizarNumeroNoNegativo, true);
    document.addEventListener('change', sanitizarNumeroNoNegativo, true);
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        delay: 50,
        color: '#ea580c',
        includeCSS: true,
        showSpinner: true,
    },
});
