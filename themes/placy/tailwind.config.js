/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './template-parts/**/*.php',
    './blocks/**/*.php',
    './inc/**/*.php',
    './js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        'overvik-green': '#78908E',
        'overvik-light': '#D1E5E6',
        'overvik-dark': '#1a1a1a',
      },
      fontFamily: {
        'sans': ['Figtree', 'sans-serif'],
        'campaign': ['campaign', 'Figtree', 'sans-serif'],
        'campaign-serif': ['campaign-serif', 'Figtree', 'serif'],
        'figtree': ['Figtree', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
}
