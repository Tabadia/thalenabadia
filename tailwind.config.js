/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public_html/**/*.{html,js}",
  ],
  theme: {
    extend: {
      colors: {
        'body-bg': '#18181b',
        'body-dark': '#212529',
        'primary': '#f8f9fa',
    },
    fontFamily: {
        'montserrat': ['Montserrat', 'sans-serif'],
      },
      animation: {
        'bounce': 'bounce 2s infinite',
      },
      keyframes: {
      bounce: {
          '0%, 100%, 20%, 50%, 80%': { transform: 'translateY(-20px)' },
          '40%': { transform: 'translateY(-30px)' },
          '60%': { transform: 'translateY(-15px)' },
        }
      },
    },
  },
  plugins: [],
}
