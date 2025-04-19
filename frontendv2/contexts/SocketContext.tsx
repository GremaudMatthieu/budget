import React, { createContext, useContext, useState, useEffect, useRef } from 'react';
import { io, Socket } from 'socket.io-client';
import { useAuth } from './AuthContext';
import { Platform, AppState, AppStateStatus } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';

// WebSocket configuration
const WS_CONFIG = {
  // Use appropriate URLs for different environments
  urls: {
    development: Platform.OS === 'android' 
      ? 'http://10.0.2.2:3030'
      : 'http://127.0.0.1:3030',
    production: 'https://api.yourdomain.com/socket' // Update with production URL (must be HTTPS)
  },
  reconnection: {
    attempts: 10,
    delay: 1000,
    maxDelay: 10000
  },
  pingInterval: 15000, // 15 seconds
  connectionTimeout: 10000, // 10 seconds
  storageKeys: {
    lastMessage: 'ws_last_message',
    lastConnected: 'ws_last_connected'
  }
};

// Define the context type with additional security and monitoring properties
type SocketContextType = {
  socket: Socket | null;
  connected: boolean;
  connectionError: string | null;
  reconnecting: boolean;
  lastMessage: { type: string; data: any } | null;
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

// Socket Provider component with enhanced security
export function SocketProvider({ children }: { children: React.ReactNode }) {
  const [socket, setSocket] = useState<Socket | null>(null);
  const [connected, setConnected] = useState(false);
  const [connectionError, setConnectionError] = useState<string | null>(null);
  const [reconnecting, setReconnecting] = useState(false);
  const [lastMessage, setLastMessage] = useState<{ type: string; data: any } | null>(null);
  
  const { user, token } = useAuth();
  const socketRef = useRef<Socket | null>(null);
  const reconnectAttemptsRef = useRef(0);
  const pingIntervalRef = useRef<NodeJS.Timeout | null>(null);
  const appStateRef = useRef(AppState.currentState);
  const connectionTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const networkStateRef = useRef<boolean>(true);
  
  // Function to encrypt sensitive data before storage (basic implementation)
  // In a real app, use a proper encryption library
  const secureStore = {
    setItem: async (key: string, value: string) => {
      try {
        // In a real app, encrypt this data before storing
        await AsyncStorage.setItem(key, value);
      } catch (error) {
        console.error(`Error storing secure data: ${error}`);
      }
    },
    getItem: async (key: string) => {
      try {
        const value = await AsyncStorage.getItem(key);
        // In a real app, decrypt the data here
        return value;
      } catch (error) {
        console.error(`Error retrieving secure data: ${error}`);
        return null;
      }
    },
    removeItem: async (key: string) => {
      try {
        await AsyncStorage.removeItem(key);
      } catch (error) {
        console.error(`Error removing secure data: ${error}`);
      }
    }
  };
  
  // Get environment-specific WebSocket URL
  const getWebsocketUrl = () => {
    return __DEV__ 
      ? WS_CONFIG.urls.development 
      : WS_CONFIG.urls.production;
  };
  
  // Monitor network connectivity
  useEffect(() => {
    const unsubscribe = NetInfo.addEventListener(state => {
      const isConnected = state.isConnected === true;
      const wasConnected = networkStateRef.current;
      
      networkStateRef.current = isConnected;
      
      // If network is restored and we have a user, but no socket connection
      if (isConnected && !wasConnected && user && (!socketRef.current || !socketRef.current.connected)) {
        console.log('Network connectivity restored, reconnecting WebSocket');
        connectSocket();
      }
    });
    
    return () => {
      unsubscribe();
    };
  }, [user]);
  
  // Handle app state changes (background/foreground)
  useEffect(() => {
    const handleAppStateChange = async (nextAppState: AppStateStatus) => {
      if (appStateRef.current.match(/inactive|background/) && nextAppState === 'active') {
        // App has come to the foreground
        console.log('App has come to the foreground, checking socket connection');
        
        // Check network connectivity first
        const networkState = await NetInfo.fetch();
        if (networkState.isConnected && user) {
          if (!socketRef.current || !socketRef.current.connected) {
            console.log('Socket disconnected while app was in background, reconnecting...');
            connectSocket();
          }
        }
      } else if (nextAppState.match(/inactive|background/) && appStateRef.current === 'active') {
        // App is going to the background
        console.log('App going to background');
        
        // Store connection state securely
        if (connected) {
          await secureStore.setItem(WS_CONFIG.storageKeys.lastConnected, Date.now().toString());
        }
      }
      
      appStateRef.current = nextAppState;
    };
    
    const subscription = AppState.addEventListener('change', handleAppStateChange);
    
    return () => {
      subscription.remove();
    };
  }, [connected, user]);
  
  // Function to connect socket with error handling and reconnection logic
  const connectSocket = () => {
    if (!user || !user.uuid) {
      console.log('Cannot connect WebSocket: No authenticated user');
      return;
    }
    
    if (!networkStateRef.current) {
      console.log('Cannot connect WebSocket: No network connectivity');
      setConnectionError('No network connection available');
      return;
    }
    
    // Clean up any existing socket
    if (socketRef.current) {
      console.log('Cleaning up existing socket connection');
      socketRef.current.removeAllListeners();
      socketRef.current.disconnect();
    }
    
    // Clear any existing connection timeout
    if (connectionTimeoutRef.current) {
      clearTimeout(connectionTimeoutRef.current);
    }
    
    // Set a connection timeout
    connectionTimeoutRef.current = setTimeout(() => {
      if (socketRef.current && !socketRef.current.connected) {
        console.error('WebSocket connection timeout');
        setConnectionError('Connection timeout');
        
        // Force disconnect and cleanup
        socketRef.current.disconnect();
      }
      connectionTimeoutRef.current = null;
    }, WS_CONFIG.connectionTimeout);
    
    const wsUrl = getWebsocketUrl();
    console.log(`Connecting to WebSocket at: ${wsUrl}`);
    
    try {
      // Create socket with improved security options
      const socketInstance = io(wsUrl, {
        auth: {
          userId: user.uuid,
          token: token // Send JWT token for improved authentication
        },
        reconnectionAttempts: WS_CONFIG.reconnection.attempts,
        reconnectionDelay: WS_CONFIG.reconnection.delay,
        reconnectionDelayMax: WS_CONFIG.reconnection.maxDelay,
        timeout: 20000,
        transports: ['websocket'], // Force WebSocket transport for better security
        forceNew: true,
        secure: !__DEV__, // Use secure connection in production
      });
      
      // Store the socket instance
      socketRef.current = socketInstance;
      setSocket(socketInstance);
      
      // Set up event handlers
      socketInstance.on('connect', () => {
        console.log(`WebSocket connected successfully with ID: ${socketInstance.id}`);
        
        // Clear connection timeout
        if (connectionTimeoutRef.current) {
          clearTimeout(connectionTimeoutRef.current);
          connectionTimeoutRef.current = null;
        }
        
        setConnected(true);
        setConnectionError(null);
        setReconnecting(false);
        reconnectAttemptsRef.current = 0;
        
        // Start ping interval to keep connection alive
        startPingInterval();
      });
      
      socketInstance.on('connected', (data) => {
        console.log('Received connection confirmation:', data);
        
        // Notify any listening components about the connection event
        // This can be used to trigger UI refreshes
        setLastMessage({ type: 'connected', data });
      });
      
      socketInstance.on('disconnect', (reason) => {
        console.log(`WebSocket disconnected: ${reason}`);
        setConnected(false);
        
        // Clear ping interval
        if (pingIntervalRef.current) {
          clearInterval(pingIntervalRef.current);
          pingIntervalRef.current = null;
        }
        
        // Attempt to reconnect depending on the reason
        if (reason === 'io server disconnect' || reason === 'io client disconnect') {
          // Server explicitly closed the connection or we manually disconnected
          console.log('Server terminated connection, not attempting automatic reconnect');
        } else {
          console.log('Unexpected disconnection, will attempt to reconnect automatically');
        }
      });
      
      socketInstance.on('connect_error', (error) => {
        console.error(`WebSocket connection error: ${error.message}`);
        setConnectionError(error.message);
        setConnected(false);
        
        // Clear connection timeout
        if (connectionTimeoutRef.current) {
          clearTimeout(connectionTimeoutRef.current);
          connectionTimeoutRef.current = null;
        }
        
        reconnectAttemptsRef.current++;
        
        if (reconnectAttemptsRef.current > 1) {
          setReconnecting(true);
        }
        
        // If we have too many failed attempts, stop trying to reconnect automatically
        if (reconnectAttemptsRef.current >= WS_CONFIG.reconnection.attempts) {
          console.error('Maximum reconnection attempts reached, giving up');
          socketInstance.disconnect();
          setReconnecting(false);
        }
      });
      
      // Handle dynamic event reception
      socketInstance.onAny((eventName, ...args) => {
        console.log(`Received WebSocket event: ${eventName}`);
        
        // Validate the event structure before processing
        const eventData = args[0];
        if (!eventData || typeof eventData !== 'object') {
          console.warn(`Received invalid event data for ${eventName}`);
          return;
        }
        
        setLastMessage({ type: eventName, data: eventData });
        
        // Store last message securely
        secureStore.setItem(WS_CONFIG.storageKeys.lastMessage, JSON.stringify({
          type: eventName,
          data: eventData,
          timestamp: new Date().toISOString()
        })).catch(err => console.error('Error storing last message:', err));
      });
      
      // Handle errors
      socketInstance.on('error', (error) => {
        console.error('Socket error:', error);
        setConnectionError(typeof error === 'string' ? error : 'Unknown socket error');
      });
      
    } catch (error) {
      console.error('Error creating socket:', error);
      setConnectionError('Failed to create socket connection');
      
      // Clear connection timeout
      if (connectionTimeoutRef.current) {
        clearTimeout(connectionTimeoutRef.current);
        connectionTimeoutRef.current = null;
      }
    }
  };
  
  // Start interval to ping the server and keep connection alive
  const startPingInterval = () => {
    if (pingIntervalRef.current) {
      clearInterval(pingIntervalRef.current);
    }
    
    pingIntervalRef.current = setInterval(() => {
      if (socketRef.current && socketRef.current.connected) {
        socketRef.current.emit('ping');
      }
    }, WS_CONFIG.pingInterval);
  };
  
  // Effect for initializing and cleaning up socket connection
  useEffect(() => {
    // Only connect if we have an authenticated user
    if (user) {
      console.log('Authenticated user detected, initializing WebSocket connection');
      connectSocket();
    } else {
      console.log('No authenticated user, not connecting to WebSocket');
      
      // Disconnect any existing socket
      if (socketRef.current) {
        console.log('Disconnecting existing socket due to no authenticated user');
        socketRef.current.disconnect();
        socketRef.current = null;
        setSocket(null);
        setConnected(false);
      }
    }
    
    // Clean up function
    return () => {
      console.log('SocketProvider unmounting, cleaning up...');
      
      if (pingIntervalRef.current) {
        clearInterval(pingIntervalRef.current);
        pingIntervalRef.current = null;
      }
      
      if (connectionTimeoutRef.current) {
        clearTimeout(connectionTimeoutRef.current);
        connectionTimeoutRef.current = null;
      }
      
      if (socketRef.current) {
        console.log('Disconnecting WebSocket on unmount');
        socketRef.current.disconnect();
        socketRef.current = null;
      }
    };
  }, [user, token]); // Reconnect if user or token changes
  
  // Create context value
  const value = {
    socket,
    connected,
    connectionError,
    reconnecting,
    lastMessage
  };
  
  return (
    <SocketContext.Provider value={value}>
      {children}
    </SocketContext.Provider>
  );
}