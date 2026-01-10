import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Obsidian / Cyberpunk Theme
                'obsidian-bg': '#050505',
                'obsidian-surface': '#0F0F12',
                'obsidian-glass': 'rgba(15, 15, 18, 0.6)',
                'obsidian-border': '#27272A',

                // Accents
                'neon-cyan': '#00F0FF',
                'neon-purple': '#7000FF',
                'neon-green': '#00FF94',
                'neon-pink': '#FF0055',
            },
            boxShadow: {
                'neon-cyan': '0 0 10px rgba(0, 240, 255, 0.5)',
                'neon-purple': '0 0 10px rgba(112, 0, 255, 0.5)',
            },
            animation: {
                'pulse-fast': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            }
        },
    },

    plugins: [forms],
};
