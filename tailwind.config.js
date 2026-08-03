import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

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
