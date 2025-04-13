import React, { createContext, useContext, useState, useEffect } from 'react';
import { io, Socket } from 'socket.io-client';
import { useAuth } from './AuthContext';
import Constants from 'expo-constants';

// Define the context type
type SocketContextType = {
  socket: Socket | null;
  connected: boolean;
};

// Create the context
const SocketContext = createContext<SocketContextType | undefined>(undefined);

// Custom hook for using the socket context
export function useSocket() {
  const context = useContext(SocketContext);
  if (context === undefined) {
    throw new Error('useSocket must be used within a SocketProvider');
  }
  return context;
}

// Socket Provider component
export function SocketProvider({ children }: { children: React.ReactNode }) {
  const [socket, setSocket] = useState<Socket | null>(null);
  const [connected, setConnected] = useState(false);
  const { user } = useAuth();
  
  useEffect(() => {
    let socketInstance: Socket | null = null;
    
    // Only connect to socket if user is authenticated
    if (user) {
      // Get the websocket URL from the environment or use the default one
      const wsUrl = process.env.EXPO_PUBLIC_WS_URL || 'http://localhost:3001';
      
      // Create socket instance with auth
      socketInstance = io(wsUrl, {
        auth: {
          token: user.uuid, // Use user ID as token for simplicity
        },
        reconnectionAttempts: 5,
        reconnectionDelay: 1000,
      });
      
      // Set up event handlers
      socketInstance.on('connect', () => {
        console.log('WebSocket connected');
        setConnected(true);
      });
      
      socketInstance.on('disconnect', () => {
        console.log('WebSocket disconnected');
        setConnected(false);
      });
      
      socketInstance.on('connect_error', (error) => {
        console.error('WebSocket connection error:', error);
        setConnected(false);
      });
      
      // Store the socket instance
      setSocket(socketInstance);
    }
    
    // Clean up function
    return () => {
      if (socketInstance) {
        socketInstance.disconnect();
        setSocket(null);
        setConnected(false);
      }
    };
  }, [user]); // Reconnect if user changes
  
  // Create context value
  const value = {
    socket,
    connected
  };
  
  return (
    <SocketContext.Provider value={value}>
      {children}
    </SocketContext.Provider>
  );
}