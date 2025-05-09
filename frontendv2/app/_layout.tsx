// Import polyfill for crypto.getRandomValues() needed for UUID generation
import "@/utils/cryptoPolyfill";

import { Slot, useRouter, useSegments } from "expo-router";
import './globals.css';
import { ErrorProvider } from "@/contexts/ErrorContext";
import { AuthProvider, useAuth } from "@/contexts/AuthContext";
import { EnvelopeProvider } from "@/contexts/EnvelopeContext";
import { BudgetProvider } from "@/contexts/BudgetContext";
import { UserProvider } from "@/contexts/UserContext";
import { LanguageProvider } from "@/contexts/LanguageContext";
import { StatusBar } from 'expo-status-bar';
import { View, ActivityIndicator, Platform } from 'react-native';
import { useEffect } from "react";
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { StyleSheet } from 'react-native';
import { RootSiblingParent } from 'react-native-root-siblings';

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

// Web-only components
import HeaderWeb from '@/components/web/HeaderWeb';
import BottomTabWeb from '@/components/web/BottomTabWeb';
import WebContainer from '@/components/web/WebContainer';
import FooterWeb from '@/components/web/FooterWeb';

export default function RootLayout() {
  if (Platform.OS === 'web') {
    return (
      <RootSiblingParent>
        <GestureHandlerRootView className="flex-1">
          <ErrorProvider>
            <AuthProvider>
              <LanguageProvider>
                <AuthProtection>
                  <UserProvider>
                    <EnvelopeProvider>
                      <BudgetProvider>
                        <HeaderWeb />
                        <WebContainer>
                          <Slot />
                        </WebContainer>
                        <FooterWeb />
                      </BudgetProvider>
                    </EnvelopeProvider>
                  </UserProvider>
                </AuthProtection>
              </LanguageProvider>
            </AuthProvider>
          </ErrorProvider>
        </GestureHandlerRootView>
      </RootSiblingParent>
    );
  }
  return (
    <RootSiblingParent>
    <GestureHandlerRootView className="flex-1">
    <ErrorProvider>
      <AuthProvider>
        <LanguageProvider>
          <AuthProtection>
              <UserProvider>
                <EnvelopeProvider>
                  <BudgetProvider>
                    <View className="flex-1 bg-background-light">
                      <StatusBar style="dark" />
                      <Slot />
                    </View>
                  </BudgetProvider>
                </EnvelopeProvider>
              </UserProvider>
          </AuthProtection>
        </LanguageProvider>
      </AuthProvider>
    </ErrorProvider>
    </GestureHandlerRootView>
    </RootSiblingParent>
  );
}