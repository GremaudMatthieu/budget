import Cookies from 'js-cookie'

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api'

export const authService = {
  setToken: (token: string) => {
    console.log('Auth service: Setting JWT token in cookie');
    // Set cookie with proper attributes for security and cross-page consistency
    Cookies.set('jwtToken', token, {
      expires: 7,  // Token expires in 7 days
      path: '/',   // Available across all paths
      sameSite: 'strict',
      secure: process.env.NODE_ENV === 'production'
    });
    console.log('Auth service: JWT token set successfully:', !!token);
  },

  getToken: () => {
    const token = Cookies.get('jwtToken');
    // console.log('Auth service: Retrieved token from cookies:', !!token);
    return token;
  },

  removeToken: () => {
    console.log('Auth service: Removing JWT token');
    Cookies.remove('jwtToken', {
      path: '/'  // Important: must match the path used when setting
    });
  },

  isAuthenticated: () => {
    const isAuth = !!Cookies.get('jwtToken');
    // console.log('Auth service: Checking if authenticated:', isAuth);
    return isAuth;
  },

  login: async (email: string, password: string) => {
    try {
      console.log('Auth service: Attempting login via credentials');
      const response = await fetch(`${API_URL}/login_check`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      })

      if (!response.ok) {
        const errorResponse = await response.json();
        const errorMessage = errorResponse.error
        throw new Error(errorMessage)
      }

      const data = await response.json()
      if (data.token) {
        authService.setToken(data.token)
        if (data.refresh_token) {
          console.log('Auth service: Setting refresh token in localStorage');
          localStorage.setItem('refreshToken', data.refresh_token);
        }
        return true
      }
      throw new Error('Login failed')
    } catch (error) {
      console.error('Auth service: Login error', error);
      throw error
    }
  },

  logout: async () => {
    try {
      console.log('Auth service: Logging out');
      const refreshToken = localStorage.getItem('refreshToken')
      if (refreshToken && authService.getToken()) {
        try {
          const response = await fetch(`${API_URL}/users/logout`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${authService.getToken()}`
            },
            body: JSON.stringify({ refreshToken: refreshToken })
          })
    
          if (!response.ok) {
            console.warn('Auth service: Server-side logout failed, proceeding with client-side logout');
          }
        } catch (error) {
          console.warn('Auth service: Error during logout API call:', error);
          // Continue with client-side logout even if API fails
        }
      }

      // Always clean up local tokens regardless of API success
      authService.removeToken();
      localStorage.removeItem('refreshToken');
      console.log('Auth service: Logout completed');
    } catch (error) {
      console.error('Auth service: Error during logout:', error);
      // Still clear tokens on error
      authService.removeToken();
      localStorage.removeItem('refreshToken');
      throw error;
    }
  },

  refreshToken: async () => {
    try {
      console.log('Auth service: Attempting to refresh token');
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
        const errorResponse = await response.json();
        const errorMessage = errorResponse.error
        throw new Error(errorMessage)
      }

      const data = await response.json()
      if (data.token) {
        authService.setToken(data.token)
        if (data.refresh_token) {
          console.log('Auth service: Updating refresh token after refresh');
          localStorage.setItem('refreshToken', data.refresh_token)
        }
        return true
      }
      throw new Error('Token refresh failed')
    } catch (error) {
      console.error('Auth service: Token refresh error:', error);
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
        try {
          const refreshed = await authService.refreshToken()
          if (refreshed) {
            // Retry the API call with the new token
            return await apiCall()
          }
        } catch (refreshError) {
          console.error('Auth service: Failed to refresh token:', refreshError);
          // If refresh failed, logout the user
          await authService.logout()
          throw new Error('Session expired. Please login again.')
        }
      }
      throw error
    }
  }
}
