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
                sans: ['Inter', 'DM Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                dgcpt: {
                    blue: '#0A2A66',
                    green: '#00A86B',
                    yellow: '#F4D000',
                    ink: '#050816',
                    cyan: '#00D1FF',
                },
            },
        },
    },

    plugins: [forms],
};
