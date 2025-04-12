'use client'

import { createContext, useContext, useState, useEffect, ReactNode } from 'react'
import { authService } from './services/auth'
import { api } from './infrastructure/api'

interface User {
  id: string
  email: string
  name: string
  firstname: string
  lastname: string
  pending: boolean
}

interface AppState {
  user: User | null
  isAuthenticated: boolean
  loading: boolean
  envelopesData: {
    envelopes: [];
    totalItems: number;
  }
}

interface AppContextType {
  state: AppState
  setState: React.Dispatch<React.SetStateAction<AppState>>
  login: (email: string, password: string) => Promise<boolean>
  logout: () => void
}

const AppContext = createContext<AppContextType | undefined>(undefined)

export function Providers({ children }: { children: ReactNode }) {
  const [state, setState] = useState<AppState>({
    user: null,
    isAuthenticated: false,
    loading: true,
  })

  useEffect(() => {
    const initializeAuth = async () => {
      // Check for direct OAuth authentication first
      const directAuth = localStorage.getItem('directAuth');
      const forceAuth = window.sessionStorage.getItem('forceAuth');
      const userEmail = window.sessionStorage.getItem('userEmail');
      
      if (directAuth === 'true' && forceAuth === 'true' && userEmail) {
        console.log('Providers: Detected direct OAuth authentication');
        try {
          // Try to get user data from API
          try {
            const userData = await api.queries.getCurrentUser();
            setState(prevState => ({
              ...prevState,
              user: userData,
              isAuthenticated: true,
              loading: false,
            }));
          } catch (error) {
            console.log('Providers: Could not fetch user data, using basic data');
            // If API fetch fails, use the stored email for basic authentication
            setState(prevState => ({
              ...prevState,
              user: {
                id: userEmail,
                email: userEmail,
                name: userEmail,
                firstname: '',
                lastname: '',
                pending: false,
              },
              isAuthenticated: true,
              loading: false,
            }));
          }
          
          // Clear the force auth flag but keep directAuth
          window.sessionStorage.removeItem('forceAuth');
          return;
        } catch (error) {
          console.error('Providers: Error handling OAuth authentication', error);
        }
      }

      // Normal authentication flow
      if (authService.isAuthenticated()) {
        try {
          const userData = await api.queries.getCurrentUser()
          setState(prevState => ({
            ...prevState,
            user: userData,
            isAuthenticated: true,
            loading: false,
          }))
        } catch (error) {
          console.error('Providers: Error fetching user data for standard auth', error);
          authService.logout()
          setState(prevState => ({
            ...prevState,
            loading: false,
          }))
        }
      } else {
        setState(prevState => ({
          ...prevState,
          loading: false,
        }))
      }
    }

    initializeAuth()
  }, [])

  const login = async (email: string, password: string): Promise<boolean> => {
    try {
      const success = await authService.login(email, password)
      if (success) {
        const userData = await api.queries.getCurrentUser()
        setState(prevState => ({
          ...prevState,
          user: userData,
          isAuthenticated: true,
        }))
        return true
      }
      return false
    } catch (error) {
      return false
    }
  }

  const logout = () => {
    // Clear all authentication data
    localStorage.removeItem('directAuth');
    window.sessionStorage.removeItem('userEmail');
    window.sessionStorage.removeItem('forceAuth');
    
    authService.logout()
    setState(prevState => ({
      ...prevState,
      user: null,
      isAuthenticated: false,
    }))
  }

  return (
    <AppContext.Provider value={{ state, setState, login, logout }}>
      {children}
    </AppContext.Provider>
  )
}

export function useAppContext() {
  const context = useContext(AppContext)
  if (context === undefined) {
    throw new Error('useAppContext must be used within a Providers')
  }
  return context
}
