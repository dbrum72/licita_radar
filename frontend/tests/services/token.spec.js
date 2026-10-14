import { afterEach, expect, it, vi } from 'vitest'
import { clearToken, getToken, setToken } from '@/services/token'

afterEach(() => {
    clearToken()
    vi.unstubAllGlobals()
})

it('persists in tab session storage and removes the token on logout', () => {
    const data = new Map()
    vi.stubGlobal('sessionStorage', {
        getItem: (key) => data.get(key),
        setItem: (key, value) => data.set(key, value),
        removeItem: (key) => data.delete(key),
    })
    setToken('jwt-value')
    expect(getToken()).toBe('jwt-value')
    expect(data.size).toBe(1)
    clearToken()
    expect(getToken()).toBe('')
    expect(data.size).toBe(0)
})
