import { Slot, useRouter, useSegments } from "expo-router";
import './globals.css';
import { ErrorProvider } from "@/contexts/ErrorContext";
import { AuthProvider, useAuth } from "@/contexts/AuthContext";
import { SocketProvider } from "@/contexts/SocketContext";
import { EnvelopeProvider } from "@/contexts/EnvelopeContext";
import { BudgetProvider } from "@/contexts/BudgetContext";
import { StatusBar } from 'expo-status-bar';
import { View, ActivityIndicator } from 'react-native';
import { useEffect } from "react";

// Auth protection component
function AuthProtection({ children }: { children: React.ReactNode }) {
  const segments = useSegments();
  const router = useRouter();
  const { user, loading } = useAuth();

  useEffect(() => {
    if (loading) return;

    const inAuthGroup = segments[0] === "(auth)";
    const inTabsGroup = segments[0] === "(tabs)";

    if (!user) {
      // If user is not signed in and not on an auth screen, redirect to auth
      if (!inAuthGroup) {
        router.replace("/(auth)");
      }
    } else {
      // If user is signed in and not on a tabs screen, redirect to tabs
      if (!inTabsGroup) {
        router.replace("/(tabs)");
      }
    }
  }, [user, loading, segments]);

  if (loading) {
    return (
      <View className="flex-1 justify-center items-center bg-background-light">
        <ActivityIndicator size="large" color="#0c6cf2" />
        <StatusBar style="auto" />
      </View>
    );
  }

  return children;
}

export default function RootLayout() {
  return (
    <ErrorProvider>
      <AuthProvider>
        <AuthProtection>
          <SocketProvider>
            <EnvelopeProvider>
              <BudgetProvider>
                <View className="flex-1 bg-background-light">
                  <StatusBar style="auto" />
                  <Slot />
                </View>
              </BudgetProvider>
            </EnvelopeProvider>
          </SocketProvider>
        </AuthProtection>
      </AuthProvider>
    </ErrorProvider>
  );
}
