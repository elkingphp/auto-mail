import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1', // Use environment variable or fallback to standard API path
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor to add token
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

// Response interceptor to handle errors
api.interceptors.response.use((response) => {
    return response;
}, (error) => {
    const requestId = error.response?.headers?.['x-request-id'];
    if (requestId) {
        console.error(`[API Error] Request ID: ${requestId}`, error.response?.data);
    }

    if (error.response && error.response.status === 401) {
        localStorage.removeItem('token');
        if (!window.location.pathname.startsWith('/dl/')) {
            window.location.href = '/login';
        }
    }
    return Promise.reject(error);
});

export default api;
