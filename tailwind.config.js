
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  darkMode: 'class', // Activation du mode sombre basé sur les classes
  theme: {
    extend: {
      colors: {
        // Définition de vos 3 couleurs principales avec leurs variantes
        // 1. Couleur de marque (bleue)
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6', // Couleur principale bleue
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
          950: '#172554',
        },
        // 2. Couleur neutre sombre - Ajustée pour meilleur contraste
        dark: {
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          300: '#cbd5e1',
          400: '#94a3b8',
          500: '#64748b',
          600: '#475569',
          700: '#334155',
          800: '#1e293b', // Fond principal en mode sombre
          900: '#0f172a',
          950: '#020617',
        },
        // 3. Couleur neutre claire - Ajustée pour meilleur contraste
        light: {
          50: '#ffffff', // Plus blanc pour le mode clair
          100: '#f9fafb',
          200: '#f3f4f6',
          300: '#e5e7eb',
          400: '#d1d5db',
          500: '#9ca3af',
          600: '#6b7280',
          700: '#4b5563',
          800: '#374151',
          900: '#1f2937',
          950: '#111827',
        },
      },
      backgroundColor: {
        dark: '#0f172a', // Couleur de fond principal pour le mode sombre
        light: '#ffffff', // Couleur de fond principal pour le mode clair
      },
      textColor: {
        darkMode: {
          primary: '#ffffff', // Texte blanc pour le mode sombre
          secondary: '#94a3b8', // Texte secondaire en mode sombre
        },
        lightMode: {
          primary: '#1f2937', // Texte foncé pour le mode clair
          secondary: '#4b5563', // Texte secondaire en mode clair
        }
      },
      // Autres extensions de thème si nécessaire
      borderRadius: {
        'full': '9999px',
      },
      boxShadow: {
        'custom': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
        'dark': '0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2)',
      },
    },
  },
  plugins: [
    // Plugin pour faciliter l'application du mode sombre
    function({ addBase }) {
      addBase({
        'html.dark': {
          colorScheme: 'dark',
        },
        'body': {
          transitionProperty: 'background-color, color, border-color',
          transitionDuration: '200ms',
          transitionTimingFunction: 'ease-in-out',
        },
        '.dark body': {
          backgroundColor: '#0f172a',
          color: '#f9fafb',
        },
        'body': {
          backgroundColor: '#ffffff',
          color: '#1f2937',
        }
      });
    },
  ],
}