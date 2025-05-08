import React, { useEffect, useState } from 'react';
import { View, Text, ActivityIndicator } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useAuth } from '@/contexts/AuthContext';

export default function GoogleCallbackScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const { loginWithGoogle } = useAuth();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const handleCallback = async () => {
      try {
        const token = params.token as string;
        const refreshToken = params.refresh_token as string;
        const email = params.email as string;

        if (!token || !email) {
          setError('Authentication failed: Missing required parameters');
          return;
        }

        const success = await loginWithGoogle(email, token, refreshToken);
        
        if (success) {
          // Let the natural navigation flow handle the redirect
          router.back();
        } else {
          setError('Failed to authenticate. Please try again.');
        }
      } catch (err) {
        console.error('OAuth callback error:', err);
        setError('An error occurred during authentication. Please try again.');
      }
    };

    handleCallback();
  }, [params, loginWithGoogle, router]);

  if (error) {
    return (
      <View className="flex-1 bg-background-light items-center justify-center p-5">
        <StatusBar style="auto" />
        <View className="bg-white rounded-xl p-6 items-center justify-center w-full max-w-lg shadow-md">
          <Text className="text-2xl font-bold text-danger-600 mb-3">Authentication Error</Text>
          <Text className="text-base text-text-secondary text-center mb-4">{error}</Text>
          <Text 
            className="text-primary-600 text-base font-semibold underline"
            onPress={() => router.replace('/signin')}
          >
            Return to Sign In
          </Text>
        </View>
      </View>
    );
  }

  return (
    <View className="flex-1 bg-background-light items-center justify-center p-5">
      <StatusBar style="auto" />
      <ActivityIndicator size="large" color="#4a6fa5" />
      <Text className="mt-5 text-base text-text-secondary">Completing authentication...</Text>
    </View>
  );
}