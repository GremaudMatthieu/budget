import React from 'react';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '@/contexts/AuthContext';
import { View, ActivityIndicator, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Slot } from 'expo-router';

/**
 * This is the layout file for the tab navigation.
 * It defines the tabs that appear at the bottom of the screen.
 */
export default function TabsLayout() {
  const { user, loading } = useAuth();

  // Show loading indicator while checking authentication
  if (loading) {
    return (
      <View className="flex-1 justify-center items-center bg-white">
        <ActivityIndicator size="large" color="#0c6cf2" />
      </View>
    );
  }

  // If not logged in, don't show tabs (auth flow will redirect)
  if (!user) {
    return null;
  }

  if (Platform.OS === 'web') {
    // On web, just render the children (Slot) and let BottomTabWeb handle navigation
    return <Slot />;
  }

  return (
    <SafeAreaView className="flex-1" edges={['bottom']}>
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: '#0c6cf2', // primary-600
        tabBarInactiveTintColor: '#64748b', // text-secondary
        tabBarLabelStyle: {
          fontSize: 12,
          fontWeight: '500',
        },
        tabBarStyle: {
          borderTopWidth: 1,
          borderTopColor: '#e2e8f0', // surface-border
          height: 60,
            paddingBottom: 0, // SafeAreaView will handle the safe area
          paddingTop: 8,
        },
        headerStyle: {
          backgroundColor: '#ffffff',
        },
        headerShadowVisible: false,
        headerTitleStyle: {
          color: '#0f172a', // text-primary
          fontWeight: '600',
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Dashboard',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="home-outline" size={size} color={color} />
          ),
          headerShown: false,
          headerTitle: 'Dashboard',
        }}
      />

      <Tabs.Screen
        name="envelopes"
        options={{
          title: 'Envelopes',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="wallet-outline" size={size} color={color} />
          ),
          headerShown: false,
          headerTitle: 'My Envelopes',
        }}
      />

      <Tabs.Screen
        name="budget-plans"
        options={{
          title: 'Budget Plans',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="pie-chart-outline" size={size} color={color} />
          ),
          headerShown: false,
          headerTitle: 'Budget Plans',
        }}
      />

      <Tabs.Screen
        name="budget-plans/create"
        options={{
          href: null,
        }}
      />

      <Tabs.Screen
        name="budget-plans/[uuid]"
        options={{
          href: null,
          headerShown: false,

        }}
      />

      <Tabs.Screen
        name="envelopes/[uuid]"
        options={{
          href: null,
          headerShown: false,

        }}
      />

      <Tabs.Screen
        name="profile/account-settings"
        options={{
          href: null,
          headerShown: false,
          }}
        />

        <Tabs.Screen
          name="profile/about"
          options={{
            href: null,
            headerShown: false,
        }}
      />

        <Tabs.Screen
          name="profile/support"
          options={{
            href: null,
            headerShown: false,
          }}
        />

      <Tabs.Screen
        name="profile"
        options={{
          title: 'Profile',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="person-outline" size={size} color={color} />
          ),
          headerShown: false,
          headerTitle: 'My Profile',
        }}
      />
    </Tabs>
    </SafeAreaView>
  );
}