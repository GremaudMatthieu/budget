import Cookies from 'js-cookie'

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api'

export const authService = {
  setToken: (token: string) => {
    Cookies.set('jwtToken', token, { expires: 7 }) // Token expires in 7 days
  },

  getToken: () => {
    return Cookies.get('jwtToken')
  },

  removeToken: () => {
    Cookies.remove('jwtToken')
  },

  isAuthenticated: () => {
    return !!Cookies.get('jwtToken')
  },

  login: async (email: string, password: string) => {
    try {
      const response = await fetch(`${API_URL}/login_check`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      })

      if (!response.ok) {
        throw new Error('Login failed')
      }

      const data = await response.json()
      if (data.token) {
        authService.setToken(data.token)
        if (data.refresh_token) {
          localStorage.setItem('refreshToken', data.refresh_token);
        }
        return true
      }
      throw new Error('Login failed')
    } catch (error) {
      throw error
    }
  },

  logout: async () => {
    try {
      const refreshToken = localStorage.getItem('refreshToken')
      if (!refreshToken) {
        throw new Error('No refresh token found')
      }

      const response = await fetch(`${API_URL}/users/logout`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authService.getToken()}`
        },
        body: JSON.stringify({ refreshToken: refreshToken })
      })

      if (!response.ok) {
        throw new Error('Logout failed')
      }

      authService.removeToken()
      localStorage.removeItem('refreshToken')
    } catch (error) {
      throw error
    }
  },

  refreshToken: async () => {
    try {
      const refreshToken = localStorage.getItem('refreshToken')
      if (!refreshToken) {
        throw new Error('No refresh token found')
      }

      const response = await fetch(`${API_URL}/token/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ refresh_token: refreshToken })
      })

      if (!response.ok) {
        throw new Error('Token refresh failed')
      }

      const data = await response.json()
      if (data.token) {
        authService.setToken(data.token)
        if (data.refresh_token) {
          localStorage.setItem('refreshToken', data.refresh_token)
        }
        return true
      }
      throw new Error('Token refresh failed')
    } catch (error) {
      throw error
    }
  },

  // Helper function to handle token refresh
  withTokenRefresh: async (apiCall: () => Promise<any>) => {
    try {
      return await apiCall()
    } catch (error) {
      if (error instanceof Error && error.message.includes('401')) {
        // Token might be expired, try to refresh
        const refreshed = await authService.refreshToken()
        if (refreshed) {
          // Retry the API call with the new token
          return await apiCall()
        } else {
          // If refresh failed, logout the user
          await authService.logout()
          throw new Error('Session expired. Please login again.')
        }
      }
      throw error
    }
  }
}
