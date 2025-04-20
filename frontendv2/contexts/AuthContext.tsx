import React, { createContext, useContext, useState, useEffect } from 'react';
import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { apiClient } from '@/services/apiClient';
import axios from 'axios';

const API_URL = process.env.API_URL || 'http://127.0.0.1:8000/api';

// Define the user type
type User = {
  uuid: string;
  email: string;
  firstname: string;
  lastname: string;
  pending?: boolean;
};

// Define the context type
type AuthContextType = {
  user: User | null;
  loading: boolean;
  isAuthenticated: boolean;
  logout: () => Promise<void>;
  changePassword: (oldPassword: string, newPassword: string) => Promise<boolean>;
  loginWithGoogle: (email: string, token: string, refreshToken?: string) => Promise<boolean>;
  refreshUserData: () => Promise<void>; // Add this new method
};

// Create the context
const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Custom hook for using the auth context
export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

// Secure storage helper function (works on web and native)
const storeData = async (key: string, value: string) => {
  try {
    if (Platform.OS === 'web') {
      await AsyncStorage.setItem(key, value);
    } else {
      await SecureStore.setItemAsync(key, value);
    }
  } catch (error) {
    console.error('Error storing data:', error);
  }
};

const getData = async (key: string) => {
  try {
    if (Platform.OS === 'web') {
      return await AsyncStorage.getItem(key);
    } else {
      return await SecureStore.getItemAsync(key);
    }
  } catch (error) {
    console.error('Error getting data:', error);
    return null;
  }
};

const removeData = async (key: string) => {
  try {
    if (Platform.OS === 'web') {
      await AsyncStorage.removeItem(key);
    } else {
      await SecureStore.deleteItemAsync(key);
    }
  } catch (error) {
    console.error('Error removing data:', error);
  }
};

// Auth Provider component
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // Load user data on app start
  useEffect(() => {
    const loadUser = async () => {
      try {
        const token = await getData('token');
        console.log('Found stored token on app load:', !!token);
        
        if (token) {
          apiClient.setAuthToken(token);
          
          try {
            // User and userData API endpoints should now be handled by the apiClient
            // which uses the centralized API_URL config
            const userData = await apiClient.get('/users/me');
            console.log('Successfully loaded user data on app start');
            setUser(userData);
          } catch (userDataError) {
            console.error('Error fetching user data with /users/me:', userDataError);
            
            // Fall back to /user endpoint
            try {
              const userData = await apiClient.get('/users/me');
              console.log('Successfully loaded user data using fallback endpoint');
              setUser(userData);
            } catch (fallbackError) {
              console.error('Fallback user data fetch also failed:', fallbackError);
              throw fallbackError;
            }
          }
        }
      } catch (error) {
        console.error('Failed to load user:', error);
        await removeData('token');
        apiClient.removeAuthToken();
      } finally {
        setLoading(false);
      }
    };

    loadUser();
  }, []);

  // Google OAuth Login function
  const loginWithGoogle = async (email: string, token: string, refreshToken?: string): Promise<boolean> => {
    try {
      setLoading(true);
      console.log('Starting Google login with email:', email);
      
      // Store the tokens
      await storeData('token', token);
      if (refreshToken) {
        await storeData('refreshToken', refreshToken);
      }
      
      // Set the auth token in the API client
      apiClient.setAuthToken(token);
      
      try {
        console.log('Fetching user data from:', `${API_URL}/users/me`);
        // Use axios directly with our API_URL instead of apiClient
        const response = await axios.get(`${API_URL}/users/me`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          }
        });
        
        console.log('Successfully fetched user data');
        setUser(response.data);
        return true;
      } catch (error) {
        console.error('Failed to fetch user data after Google login:', error);
        await removeData('token');
        if (refreshToken) {
          await removeData('refreshToken');
        }
        apiClient.removeAuthToken();
        return false;
      }
    } catch (error) {
      console.error('Google login error:', error);
      return false;
    } finally {
      setLoading(false);
    }
  };

  // Logout function
  const logout = async (): Promise<void> => {
    try {
      setLoading(true);
      await apiClient.post('users/logout');
      await removeData('token');
      await removeData('refreshToken');
      apiClient.removeAuthToken();
      setUser(null);
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Refresh user data function
  const refreshUserData = async (): Promise<void> => {
    try {
      console.log('Refreshing user data from API');
      const userData = await apiClient.get('/users/me');
      console.log('Successfully refreshed user data');
      setUser(userData);
    } catch (error) {
      console.error('Failed to refresh user data:', error);
    }
  };

  // Create auth value object
  const value = {
    user,
    loading,
    isAuthenticated: !!user,
    logout,
    loginWithGoogle,
    refreshUserData
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
