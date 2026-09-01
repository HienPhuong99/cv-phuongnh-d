/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./templates/**/*.html",
    "./assets/**/*.js"
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        brand: {
          bg: '#0E0B08',
          surface: '#191309',
          surface2: '#241B0F',
          surface3: '#2E2214',
          border: '#33271A',
          borderLight: '#4D3B26',
          text: '#F4ECDF',
          muted: '#B3A488',
          subtle: '#7D705C',
          dim: '#7D705C',
          gold: '#E3A93B',
          goldHover: '#F0BB55',
          terracotta: '#D2603A',
          terracottaHover: '#E3734D',
          bronze: '#A9762B',
        }
      },
      fontFamily: {
        heading: ['"Space Grotesk"', 'sans-serif'],
        sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace'],
      },
      boxShadow: {
        'gold-glow': '0 0 25px rgba(227, 169, 59, 0.18)',
        'gold-glow-lg': '0 0 35px rgba(227, 169, 59, 0.3)',
        'terracotta-glow': '0 0 25px rgba(210, 96, 58, 0.18)',
      }
    }
  },
  plugins: []
}
