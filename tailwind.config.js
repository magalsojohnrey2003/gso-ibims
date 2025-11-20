import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
  // Use class-based dark mode so toggling `html.classList.add('dark')` works with Tailwind utilities
  darkMode: 'class',

  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
  ],

  theme: {
    extend: {
      // gov purple available as a tailwind color utility
      colors: {
        'gov-purple': '#A855F7',
        'nav-purple': '#7D46D3',
        'sidebar-purple': '#4C1D95',
        'sidebar-purple-hover': '#5B21B6',
      },
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
    },
  },

  plugins: [forms],
};
