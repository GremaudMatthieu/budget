import React, { createContext, useContext, useState, useEffect } from 'react';
import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { apiClient } from '../services/apiClient';

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
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => Promise<void>;
  signup: (userData: any) => Promise<boolean>;
  updateProfile: (field: 'firstname' | 'lastname', value: string) => Promise<boolean>;
  changePassword: (oldPassword: string, newPassword: string) => Promise<boolean>;
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
        
        if (token) {
          apiClient.setAuthToken(token);
          const userData = await apiClient.get('/user');
          setUser(userData);
        }
      } catch (error) {
        console.error('Failed to load user:', error);
        // If there's an error, remove the token
        await removeData('token');
        apiClient.removeAuthToken();
      } finally {
        setLoading(false);
      }
    };

    loadUser();
  }, []);

  // Login function
  const login = async (email: string, password: string): Promise<boolean> => {
    try {
      setLoading(true);
      const response = await apiClient.post('/login', { email, password });
      
      if (response.token) {
        await storeData('token', response.token);
        apiClient.setAuthToken(response.token);
        
        // Get user data
        const userData = await apiClient.get('/user');
        setUser(userData);
        return true;
      }
      return false;
    } catch (error) {
      console.error('Login error:', error);
      return false;
    } finally {
      setLoading(false);
    }
  };

  // Logout function
  const logout = async (): Promise<void> => {
    try {
      setLoading(true);
      await apiClient.post('/logout');
      await removeData('token');
      apiClient.removeAuthToken();
      setUser(null);
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Signup function
  const signup = async (userData: any): Promise<boolean> => {
    try {
      setLoading(true);
      const response = await apiClient.post('/register', userData);
      return true;
    } catch (error) {
      console.error('Signup error:', error);
      return false;
    } finally {
      setLoading(false);
    }
  };

  // Update profile function
  const updateProfile = async (field: 'firstname' | 'lastname', value: string): Promise<boolean> => {
    try {
      setLoading(true);
      await apiClient.put(`/user/${field}`, { [field]: value });
      
      // Update local user state
      setUser(prev => prev ? { ...prev, [field]: value } : null);
      return true;
    } catch (error) {
      console.error(`Update ${field} error:`, error);
      return false;
    } finally {
      setLoading(false);
    }
  };

  // Change password function
  const changePassword = async (oldPassword: string, newPassword: string): Promise<boolean> => {
    try {
      setLoading(true);
      await apiClient.put('/user/password', { oldPassword, newPassword });
      return true;
    } catch (error) {
      console.error('Change password error:', error);
      return false;
    } finally {
      setLoading(false);
    }
  };

  // Create auth value object
  const value = {
    user,
    loading,
    isAuthenticated: !!user,
    login,
    logout,
    signup,
    updateProfile,
    changePassword
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}