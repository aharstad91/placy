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
        'campaign': ['campaign', 'Raleway', 'sans-serif'],
        'campaign-serif': ['campaign-serif', 'Raleway', 'serif'],
        'raleway': ['Raleway', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
