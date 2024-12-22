'use client'

import { useState } from 'react'
import { useAppContext } from '../../providers'
import { User, UserState } from './userTypes'
import { api } from '../../infrastructure/api'
import { authService } from '../../services/auth';

export function useUser() {
  const { state, login, logout, setState } = useAppContext()
  const [error, setError] = useState<string | null>(null)

  const signIn = async (email: string, password: string): Promise<boolean> => {
    setError(null)
    try {
      const success = await login(email, password)
      if (!success) {
        setError('Failed to sign in. Please check your credentials and try again.')
      }
      return success
    } catch (err) {
      setError('An error occurred during sign in. Please try again.')
      return false
    }
  }

  const signOut = async () => {
    try {
      await authService.logout();
      logout();
    } catch (error) {
      setError('Failed to sign out. Please try again.');
    }
  }

  const createUser = async (userData: any) => {
    setError(null)
    try {
      await api.commands.createUser(userData)
      return true
    } catch (err) {
      setError('Failed to create user. Please try again.')
      return false
    }
  }

  const hasEnvelopes = async (): Promise<boolean> => {
    try {
      const envelopes = await api.envelopeQueries.listEnvelopes()
      return envelopes.envelopes.length > 0
    } catch (err) {
      return false
    }
  }

  const updateFirstname = async (firstname: string) => {
    setError(null)
    setState(prevState => ({
      ...prevState,
      user: prevState.user ? { ...prevState.user, firstname, pending: true } : null
    }))
    try {
      await api.commands.updateFirstname(firstname)
      await pollForChanges('firstname', firstname)
      return true
    } catch (err) {
      setError('Failed to update firstname')
      setState(prevState => ({
        ...prevState,
        user: prevState.user ? { ...prevState.user, pending: false } : null
      }))
      return false
    }
  }

  const updateLastname = async (lastname: string) => {
    setError(null)
    setState(prevState => ({
      ...prevState,
      user: prevState.user ? { ...prevState.user, lastname, pending: true } : null
    }))
    try {
      await api.commands.updateLastname(lastname)
      await pollForChanges('lastname', lastname)
      return true
    } catch (err) {
      setError('Failed to update lastname')
      setState(prevState => ({
        ...prevState,
        user: prevState.user ? { ...prevState.user, pending: false } : null
      }))
      return false
    }
  }

  const changePassword = async (oldPassword: string, newPassword: string) => {
    setError(null)
    setState(prevState => ({
      ...prevState,
      user: prevState.user ? { ...prevState.user, pending: true } : null
    }))
    try {
      await api.commands.changePassword(oldPassword, newPassword)
      await pollForChanges('password')
      return true
    } catch (err) {
      setError('Failed to change password')
      setState(prevState => ({
        ...prevState,
        user: prevState.user ? { ...prevState.user, pending: false } : null
      }))
      return false
    }
  }

  const pollForChanges = async (field: 'firstname' | 'lastname' | 'password', expectedValue?: string) => {
    const maxRetries = 10
    const retryInterval = 1000 // 1 second

    for (let i = 0; i < maxRetries; i++) {
      await new Promise(resolve => setTimeout(resolve, retryInterval))
      try {
        const updatedUser = await api.queries.getCurrentUser()
        if (field === 'password' || updatedUser[field] === expectedValue) {
          setState(prevState => ({
            ...prevState,
            user: { ...updatedUser, pending: false }
          }))
          return
        }
      } catch (err) {
      }
    }
    setError(`Failed to confirm ${field} update. Please refresh.`)
  }

  return {
    user: state.user,
    isAuthenticated: state.isAuthenticated,
    loading: state.loading,
    error,
    signIn,
    signOut,
    createUser,
    hasEnvelopes,
    updateFirstname,
    updateLastname,
    changePassword,
  }
}
