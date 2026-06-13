import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'primary': '#0078d7',      // Sky Blue
                'primary-light': '#75b6e9', // Light Blue
                'accent': '#0078d7',       // Sky Blue
            },
        },
        // Override all rounded corners to be sharp
        borderRadius: {
            'none': '0',
            'sm': '0',
            DEFAULT: '0',
            'md': '0',
            'lg': '0',
            'xl': '0',
            '2xl': '0',
            '3xl': '0',
            'full': '0',
        },
    },

    plugins: [forms],
};
