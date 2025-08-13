import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./resources/**/*.json"
    ],
    theme: {
        extend: {
            colors: {
                accent: "#D6FF00",
                secondary: "#ff00ff",
            }
        },
    },
    plugins: [],
}

