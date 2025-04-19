import React from 'react';
import { Stack } from 'expo-router';
import * as Linking from 'expo-linking';

export default function AuthLayout() {
  // Configure deep linking
  const linking = {
    prefixes: [Linking.createURL('/')],
    config: {
      screens: {
        signin: 'signin',
        signup: 'signup',
        index: '',
      },
    },
  };

  return (
    <Stack
      screenOptions={{
        headerShown: false,
        animation: 'fade',
      }}
      linking={linking}
    />
  );
}