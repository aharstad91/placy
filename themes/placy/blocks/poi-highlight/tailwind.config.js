/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./template.php'],
  theme: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
}
