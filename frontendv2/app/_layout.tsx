import { Slot, Stack } from 'expo-router';
import { useEffect } from 'react';
import { Platform } from 'react-native';
import { AuthProvider } from '../contexts/AuthContext';
import { SocketProvider } from '../contexts/SocketContext';
import { ErrorProvider } from '../contexts/ErrorContext';

export default function RootLayout() {
  useEffect(() => {
    // Add any root-level initialization here
    console.log(`App running on ${Platform.OS}`);
  }, []);

  return (
    <ErrorProvider>
      <AuthProvider>
        <SocketProvider>
          <Stack screenOptions={{
            headerShown: false,
          }} />
        </SocketProvider>
      </AuthProvider>
    </ErrorProvider>
  );
}