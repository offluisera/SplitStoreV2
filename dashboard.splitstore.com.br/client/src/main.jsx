// dashboard.splitstore.com.br/client/src/main.jsx
import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

// Inicializar Particles.js
const initParticles = () => {
  if (window.particlesJS) {
    window.particlesJS("particles-js", {
      particles: {
        number: { value: 60, density: { enable: true, value_area: 800 } },
        color: { value: "#ef4444" },
        shape: { type: "circle" },
        opacity: {
          value: 0.1,
          random: true,
          anim: { enable: true, speed: 1, opacity_min: 0.05, sync: false }
        },
        size: {
          value: 3,
          random: true,
          anim: { enable: true, speed: 2, size_min: 0.5, sync: false }
        },
        line_linked: {
          enable: true,
          distance: 150,
          color: "#ef4444",
          opacity: 0.05,
          width: 1
        },
        move: {
          enable: true,
          speed: 1,
          direction: "none",
          random: true,
          out_mode: "out"
        }
      },
      interactivity: {
        detect_on: "canvas",
        events: {
          onhover: { enable: true, mode: "grab" },
          resize: true
        },
        modes: {
          grab: { distance: 140, line_linked: { opacity: 0.2 } }
        }
      },
      retina_detect: true
    });
  }
};

console.log('⚛️ React Main.jsx executando...');
console.log('💾 Token atual:', localStorage.getItem('auth_token') ? 'EXISTE ✅' : 'NÃO EXISTE ❌');

// Renderizar App sempre (a verificação já foi feita no index.html)
ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
)

console.log('✅ App renderizado!');

// Inicializar particles
setTimeout(() => {
  initParticles();
  console.log('🎨 Particles inicializados!');
}, 100);