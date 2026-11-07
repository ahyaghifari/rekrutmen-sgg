import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Urutan baris dibangun dinamis di partials/repeater-rows.blade.php (order-N / md:order-N), tidak terbaca scanner.
    safelist: [{ pattern: /^order-(?:[1-9]|1[0-2])$/, variants: ['md'] }],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
                    DEFAULT: '#042c6c',
                    dark:    '#032356',
                    light:   '#0b47a1',
                },
                'sgg-green': {
                    DEFAULT: '#047C54',
                    dark:    '#024732',
                    hover:   '#036444',
                },
                magenta: '#B9308C',
            },
        },
    },

    plugins: [forms],
};
