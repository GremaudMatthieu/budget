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
    const onNotFoundPage = segments.includes("not-found");

    if (!user) {
      // If user is not signed in and not on an auth screen, redirect to auth
      if (!inAuthGroup && !onNotFoundPage) {
        router.replace("/(auth)");
      }
    } else {
      // If user is signed in and not on a tabs screen or not-found page, redirect to tabs
      if (!inTabsGroup && !onNotFoundPage) {
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
  // Prevent zoom on mobile web
  useEffect(() => {
    if (Platform.OS === 'web') {
      // Add viewport meta tag programmatically as backup
      const viewport = document.querySelector('meta[name="viewport"]');
      if (viewport) {
        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, shrink-to-fit=no');
      }
      
      // Prevent zoom with touch events
      const preventZoom = (e: TouchEvent) => {
        if (e.touches.length > 1) {
          e.preventDefault();
        }
      };
      
      document.addEventListener('touchstart', preventZoom, { passive: false });
      document.addEventListener('touchmove', preventZoom, { passive: false });
      
      return () => {
        document.removeEventListener('touchstart', preventZoom);
        document.removeEventListener('touchmove', preventZoom);
      };
    }
  }, []);

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