// src/main.jsx
import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

// Inicializar Particles.js quando disponível
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

// Verificar autenticação via URL ou localStorage
const checkAuth = () => {
  // 1. Verificar se veio token na URL (redirecionamento do auth)
  const urlParams = new URLSearchParams(window.location.search);
  const urlToken = urlParams.get('token');
  const urlUser = urlParams.get('user');
  
  if (urlToken && urlUser) {
    // Salvar token e user do URL
    localStorage.setItem('auth_token', urlToken);
    localStorage.setItem('user', urlUser);
    
    // Limpar URL sem recarregar a página
    window.history.replaceState({}, document.title, window.location.pathname);
    return true;
  }
  
  // 2. Verificar se já tem token no localStorage
  const storedToken = localStorage.getItem('auth_token');
  const storedUser = localStorage.getItem('user');
  
  if (storedToken && storedUser) {
    return true;
  }
  
  // 3. Se não tem token, redirecionar para auth
  window.location.href = 'https://auth.splitstore.com.br/login';
  return false;
};

if (checkAuth()) {
  ReactDOM.createRoot(document.getElementById('root')).render(
    <React.StrictMode>
      <App />
    </React.StrictMode>,
  )
  
  // Inicializar particles após render
  setTimeout(initParticles, 100);
}