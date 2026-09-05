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
            colors: {
                'tik-red': '#F20D18',
                'tik-red-dark': '#B80810',
                'tik-ink': '#111111',
                'tik-box': '#D99A62',
                'tik-gray': '#F5F5F5',
            },
            fontFamily: {
                sans: [...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
