import React, { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react';
import { v4 as uuidv4 } from 'uuid';
import { useSocket } from './SocketContext';
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
  
  const { socket, connected } = useSocket();
  const { user, logout, refreshUserData } = useAuth(); // Get refreshUserData from AuthContext
  const { setError: setGlobalError } = useErrorContext();
  
  // Clear pending requests when socket disconnects
  useEffect(() => {
    if (!connected && Object.keys(pendingRequests).length > 0) {
      console.log('Socket disconnected, clearing pending requests');
      setPendingRequests({});
    }
  }, [connected, pendingRequests]);
  
  // Setup WebSocket event listeners for user-related events
  useEffect(() => {
    if (!socket || !user) return;
    
    console.log('Setting up WebSocket event listeners for user updates');
    
    // Define a function to handle event processing
    const processUserEvent = async (event: any) => {
      console.log(`Processing user event: ${event.type || 'unknown'}`, event);
      
      // Make sure we're only processing events for the current user
      if (event.userId === user.uuid || event.aggregateId === user.uuid) {
        if (event.requestId && pendingRequests[event.requestId]) {
          console.log(`Clearing pending request: ${event.requestId}`);
          
          // Create a new object without the pending request to ensure state updates
          const newPendingRequests = { ...pendingRequests };
          delete newPendingRequests[event.requestId];
          
          // Force an immediate state update
          setPendingRequests(newPendingRequests);
          
          // Refresh user data in the AuthContext, which will update UI
          await refreshUserData();
        }
      }
    };
    
    // Define event handlers with a shared processing function
    const eventHandlers: Record<string, (event: any) => void> = {
      'UserDeleted': (event) => {
        console.log('User deleted event received:', event);
        processUserEvent(event);
        // Logout the user after processing the event
        logout();
      },
      'UserFirstnameChanged': (event) => {
        console.log('User firstname changed event received:', event);
        processUserEvent(event);
      },
      'UserLastnameChanged': (event) => {
        console.log('User lastname changed event received:', event);
        processUserEvent(event);
      },
      'UserLanguagePreferenceChanged': (event) => {
        console.log('User language preference changed event received:', event);
        processUserEvent(event);
      }
    };
    
    // Register event handlers
    for (const [event, handler] of Object.entries(eventHandlers)) {
      socket.on(event, handler);
    }
    
    // Cleanup function
    return () => {
      console.log('Cleaning up WebSocket event listeners for user updates');
      // Remove all event handlers
      for (const event of Object.keys(eventHandlers)) {
        socket.off(event);
      }
    };
  }, [socket, user, pendingRequests, logout, refreshUserData]);
  
  // Check if there are any pending requests and update the updating state
  useEffect(() => {
    const hasPendingRequests = Object.keys(pendingRequests).length > 0;
    setUpdating(hasPendingRequests);
    
    // Add a safety timeout to reset updating state after 10 seconds
    // in case the server doesn't respond with an event
    if (hasPendingRequests) {
      const timeoutId = setTimeout(() => {
        if (Object.keys(pendingRequests).length > 0) {
          console.log('Safety timeout: clearing pending requests after 10 seconds');
          setPendingRequests({});
          setUpdating(false);
        }
      }, 10000);
      
      return () => clearTimeout(timeoutId);
    }
  }, [pendingRequests]);
  
  // Update first name
  const updateFirstName = async (firstName: string): Promise<boolean> => {
    if (!user) {
      setGlobalError('You must be logged in to perform this action');
      return false;
    }
    
    const requestId = uuidv4();
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
    
    const requestId = uuidv4();
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
    
    const requestId = uuidv4();
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
    
    const requestId = uuidv4();
    console.log(`Deleting account with request ID: ${requestId}`);
    
    setPendingRequests(prev => ({
      ...prev,
      [requestId]: true
    }));
    
    try {
      // Since we don't have a deleteWithRequestId method, we'll use the headers config option
      await apiClient.delete('/users/account', { 
        headers: { 'request-id': requestId } // Changed from 'Request-Id' to 'request-id'
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