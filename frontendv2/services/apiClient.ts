import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';
import { Platform } from 'react-native';
import uuid from 'react-native-uuid';
import * as SecureStore from 'expo-secure-store';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';

class ApiClient {
  private client: AxiosInstance;
  private token: string | null = null;
  private isRefreshing = false;
  private refreshPromise: Promise<string | null> | null = null;
  private refreshQueue: Array<(token: string | null) => void> = [];

  constructor() {
    // Use the correct API URL
    const API_URL = process.env.EXPO_PUBLIC_API_URL;
    console.log('ApiClient initialized with URL:', API_URL);
    
    this.client = axios.create({
      baseURL: API_URL,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // Add request interceptor for authentication
    this.client.interceptors.request.use((config) => {
      if (this.token) {
        config.headers.Authorization = `Bearer ${this.token}`;
      }
      return config;
    });

    // Add response interceptor for error handling and refresh logic
    this.client.interceptors.response.use(
      (response) => response.data,
      async (error) => {
        if (!error.response) {
          console.error('Network error:', error.message);
          return Promise.reject(new Error('Network error. Please check your connection.'));
        }
        const { status, config } = error.response;
        if (status === 401 && !config._retry) {
          config._retry = true;
          try {
            const newToken = await this.handleTokenRefresh();
            if (newToken) {
              this.setAuthToken(newToken);
              config.headers.Authorization = `Bearer ${newToken}`;
              return this.client(config);
            } else {
              this.handleLogout();
              return Promise.reject(error);
            }
          } catch (refreshError) {
            this.handleLogout();
            return Promise.reject(refreshError);
          }
        }
        // Handle API errors
        const { data } = error.response;

        switch (status) {
          case 401:
            // Unauthorized - handle token expiration
            console.error('Authentication error:', data.message || 'Unauthorized');
            break;
          case 403:
            // Forbidden
            console.error('Permission denied:', data.message || 'Forbidden');
            break;
          case 422:
            // Validation errors
            console.error('Validation errors:', data.errors || data.message);
            break;
          default:
            console.error(`API error (${status}):`, data.message || 'Unknown error');
        }

        return Promise.reject(error.response.data);
      }
    );
  }

  // Set auth token
  setAuthToken(token: string): void {
    this.token = token;
  }

  // Remove auth token
  removeAuthToken(): void {
    this.token = null;
  }

  // GET request
  async get<T = any>(url: string, config?: AxiosRequestConfig): Promise<T> {
    return this.client.get(url, config);
  }

  // POST request
  async post<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
    // Add request ID header if not already present in config
    if (config?.headers?.['request-id']) {
      return this.client.post(url, data, config);
    }
    
    const requestConfig = { 
      ...config,
      headers: {
        ...config?.headers,
        'request-id': uuid.v4(),
      }
    };
    
    return this.client.post(url, data, requestConfig);
  }

  // POST request with specific request ID
  async postWithRequestId(url: string, data?: any, requestId?: string, config?: AxiosRequestConfig): Promise<{ requestId: string; data: any }> {
    const effectiveRequestId = requestId || uuid.v4();
    
    const requestConfig = { 
      ...config,
      headers: {
        ...config?.headers,
        'request-id': effectiveRequestId,
      }
    };
    
    return {
      requestId: effectiveRequestId,
      data: await this.client.post(url, data, requestConfig)
    };
  }

  // PUT request
  async put<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
    return this.client.put(url, data, config);
  }

  // DELETE request
  async delete<T = any>(url: string, config?: AxiosRequestConfig): Promise<T> {
    return this.client.delete(url, config);
  }

  // PATCH request
  async patch<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
    return this.client.patch(url, data, config);
  }

  // --- Token Refresh Logic ---
  private async handleTokenRefresh(): Promise<string | null> {
    if (this.isRefreshing) {
      // Queue requests while refreshing
      return new Promise((resolve) => {
        this.refreshQueue.push(resolve);
      });
    }
    this.isRefreshing = true;
    try {
      const refreshToken = await SecureStore.getItemAsync('refresh_token');
      if (!refreshToken) throw new Error('No refresh token');
      const response = await axios.post(`${process.env.EXPO_PUBLIC_API_URL}/token/refresh`, { refresh_token: refreshToken });
      const newToken = response.data?.token || response.data?.access_token;
      if (newToken) {
        this.setAuthToken(newToken);
        // Optionally update refresh token if provided
        if (response.data?.refresh_token) {
          await SecureStore.setItemAsync('refresh_token', response.data.refresh_token);
        }
      }
      // Resolve all queued requests
      this.refreshQueue.forEach((cb) => cb(newToken));
      this.refreshQueue = [];
      return newToken;
    } catch (err) {
      this.refreshQueue.forEach((cb) => cb(null));
      this.refreshQueue = [];
      return null;
    } finally {
      this.isRefreshing = false;
    }
  }

  private handleLogout() {
    this.removeAuthToken();
    if (Platform.OS === 'web') {
      AsyncStorage.removeItem('refresh_token');
    } else {
      SecureStore.deleteItemAsync('refresh_token');
    }
    // Redirect to login page
    router.replace('/signin');
  }
}

// Export a singleton instance
export const apiClient = new ApiClient();