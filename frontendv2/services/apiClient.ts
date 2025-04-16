import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';
import { Platform } from 'react-native';

class ApiClient {
  private client: AxiosInstance;
  private token: string | null = null;

  constructor() {
    // Use the correct API URL
    const API_URL = 'http://127.0.0.1:8000/api';
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

    // Add response interceptor for error handling
    this.client.interceptors.response.use(
      (response) => response.data,
      (error) => {
        // Handle network errors
        if (!error.response) {
          console.error('Network error:', error.message);
          return Promise.reject(new Error('Network error. Please check your connection.'));
        }

        // Handle API errors
        const { status, data } = error.response;

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
    return this.client.post(url, data, config);
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
}

// Export a singleton instance
export const apiClient = new ApiClient();