/** @type {import('tailwindcss').Config} */
module.exports = {
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
