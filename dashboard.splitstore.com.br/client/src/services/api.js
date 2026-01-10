// src/services/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  }
});

// Interceptor para adicionar token em todas as requisições
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para tratar erros
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = 'https://auth.splitstore.com.br/login';
    }
    return Promise.reject(error);
  }
);

export const plansAPI = {
  getPlans: () => api.get('/plans'),
  getPlan: (id) => api.get(`/plans/${id}`),
};

export const checkoutAPI = {
  createCheckout: (data) => api.post('/checkout', data),
  validateCoupon: (code) => api.post('/checkout/validate-coupon', { code }),
};

export const storeAPI = {
  checkSlug: (slug) => api.get(`/store/check-slug/${slug}`),
  getMyStore: () => api.get('/store/my-store'),
};

export default api;