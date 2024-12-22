'use client'

import { createContext, useContext, useState, useEffect, ReactNode } from 'react'
import { authService } from './services/auth'
import { api } from './infrastructure/api'

interface User {
  id: string
  email: string
  name: string
}

interface AppState {
  user: User | null
  isAuthenticated: boolean
  loading: boolean
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
