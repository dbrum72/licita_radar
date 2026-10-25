import axios from 'axios'
import { getToken, clearToken } from '@/services/token'

const http = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api',
    headers: { Accept: 'application/json' },
    timeout: 15_000,
})

http.interceptors.request.use((config) => {
    const token = getToken()
    if (token) config.headers.set('Authorization', 'Bearer ' + token)
    return config
})

http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) clearToken()
        return Promise.reject(error)
    },
)

export default http
