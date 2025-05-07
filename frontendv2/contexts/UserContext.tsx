import React, { createContext, useContext, useState, ReactNode } from 'react';
import uuid from 'react-native-uuid';
import { useAuth } from './AuthContext';
import { useErrorContext } from './ErrorContext';
import { apiClient } from '@/services/apiClient';

// Define the types
type UserContextType = {
  loading: boolean;
  error: string | null;
  updating: boolean;
  updateFirstName: (firstName: string) => Promise<boolean>;
  updateLastName: (lastName: string) => Promise<boolean>;
  updateLanguagePreference: (language: 'en' | 'fr') => Promise<boolean>;
  deleteAccount: () => Promise<boolean>;
};

// Create the context
const UserContext = createContext<UserContextType | undefined>(undefined);

// Custom hook for using the user context
export function useUser() {
  const context = useContext(UserContext);
  if (context === undefined) {
    throw new Error('useUser must be used within a UserProvider');
  }
  return context;
}

// User Provider component
export function UserProvider({ children }: { children: ReactNode }) {
  const [loading, setLoading] = useState(false);
  const [updating, setUpdating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [pendingRequests, setPendingRequests] = useState<Record<string, boolean>>({});
  
  const { user, logout, refreshUserData } = useAuth(); // Get refreshUserData from AuthContext
  const { setError: setGlobalError } = useErrorContext();
  
  // Update first name
  const updateFirstName = async (firstName: string): Promise<boolean> => {
    if (!user) {
      setGlobalError('You must be logged in to perform this action');
      return false;
    }
    
    const requestId = uuid.v4();
    console.log(`Updating firstname with request ID: ${requestId}`);
    
    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));
    
    try {
      // Send the request ID in the headers
      const response = await apiClient.postWithRequestId(
        '/users/firstname', 
        { firstname: firstName },
        requestId
      );
      
      console.log(`Request sent successfully with ID: ${requestId}`);
      return true;
    } catch (err: any) {
      console.error('Failed to update firstname:', err);
      setGlobalError(err.message || 'Failed to update first name');
      
      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });
      
      return false;
    }
  };
  
  // Update last name
  const updateLastName = async (lastName: string): Promise<boolean> => {
    if (!user) {
      setGlobalError('You must be logged in to perform this action');
      return false;
    }
    
    const requestId = uuid.v4();
    console.log(`Updating lastname with request ID: ${requestId}`);
    
    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));
    
    try {
      // Send the request ID in the headers
      const response = await apiClient.postWithRequestId(
        '/users/lastname', 
        { lastname: lastName },
        requestId
      );
      
      console.log(`Request sent successfully with ID: ${requestId}`);
      return true;
    } catch (err: any) {
      console.error('Failed to update lastname:', err);
      setGlobalError(err.message || 'Failed to update last name');
      
      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });
      
      return false;
    }
  };
  
  // Update language preference
  const updateLanguagePreference = async (language: 'en' | 'fr'): Promise<boolean> => {
    if (!user) {
      setGlobalError('You must be logged in to perform this action');
      return false;
    }
    
    const requestId = uuid.v4();
    console.log(`Updating language preference with request ID: ${requestId}`);
    
    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));
    
    try {
      // Send the request ID in the headers
      const response = await apiClient.postWithRequestId(
        '/users/language-preference', 
        { languagePreference: language },
        requestId
      );
      
      console.log(`Request sent successfully with ID: ${requestId}`);
      return true;
    } catch (err: any) {
      console.error('Failed to update language preference:', err);
      setGlobalError(err.message || 'Failed to update language preference');
      
      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });
      
      return false;
    }
  };
  
  // Delete account
  const deleteAccount = async (): Promise<boolean> => {
    if (!user) {
      setGlobalError('You must be logged in to perform this action');
      return false;
    }
    
    const requestId = uuid.v4();
    console.log(`Deleting account with request ID: ${requestId}`);
    
    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));
    
    try {
      await apiClient.delete('/users/delete', { 
        headers: { 'request-id': requestId }
      });
      
      console.log(`Delete request sent successfully with ID: ${requestId}`);
      // The logout will be handled by the socket event
      return true;
    } catch (err: any) {
      console.error('Failed to delete account:', err);
      setGlobalError(err.message || 'Failed to delete account');
      
      setPendingRequests(prev => {
        const updated = { ...prev };
        delete updated[requestId];
        return updated;
      });
      
      return false;
    }
  };
  
  // Create context value
  const value = {
    loading,
    updating,
    error,
    updateFirstName,
    updateLastName,
    updateLanguagePreference,
    deleteAccount
  };
  
  return (
    <UserContext.Provider value={value}>
      {children}
    </UserContext.Provider>
  );
}