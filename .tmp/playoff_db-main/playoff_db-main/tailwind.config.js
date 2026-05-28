const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                customGreen: '#4da674',
                MutedCyan: '#77ABB6',
                PeachPink: '#3FC495',
                AlmostBlack: '#F8B097',
                SoftBeige: '#3FC495',
                DarkSlateBlue: '#3E3C6E',
                SoftCrimson: '#E95354',
                LightAzure: '#61CBFF',
            },
        },
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
