import React, { useState } from 'react';
import { TouchableOpacity, Text, ActivityIndicator, View, Platform } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { useAuth } from '@/contexts/AuthContext';
import { Ionicons } from '@expo/vector-icons';

WebBrowser.maybeCompleteAuthSession();

export default function GoogleSignInButton() {
  const [isLoading, setIsLoading] = useState(false);
  const { loginWithGoogle } = useAuth();
  const API_URL = process.env.API_URL || 'http://127.0.0.1:8000/api';

  const handleGoogleSignIn = async () => {
    setIsLoading(true);
    try {
      if (Platform.OS === 'web') {
        window.location.href = `${API_URL}/connect/google?platform=web`;
      } else {
        // For mobile, use the custom URL scheme
        const result = await WebBrowser.openAuthSessionAsync(
          `${API_URL}/connect/google?platform=mobile`,
          'budgetapp://oauth/google/callback'
        );

        if (result.type === 'success') {
          const url = new URL(result.url);
          const params = new URLSearchParams(url.search);
          
          const email = params.get('email');
          const token = params.get('token');
          const refreshToken = params.get('refresh_token');
          
          if (email && token) {
            await loginWithGoogle(email, token, refreshToken || undefined);
          }
        }
      }
    } catch (error) {
      console.error('Google Sign-in error:', error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <TouchableOpacity
      className="bg-white border border-surface-border py-4 px-6 rounded-xl shadow-sm items-center justify-center"
      onPress={handleGoogleSignIn}
      disabled={isLoading}
      style={isLoading ? { opacity: 0.8 } : {}}
    >
      {isLoading ? (
        <View className="flex-row items-center">
          <ActivityIndicator color="#0284c7" size="small" />
          <Text className="text-primary-600 font-medium ml-3">Connecting...</Text>
        </View>
      ) : (
        <View className="flex-row items-center justify-center">
          <View className="w-6 h-6 mr-3 items-center justify-center">
            <View className="flex-row">
              <View className="w-2 h-2 bg-[#4285F4] rounded-sm" />
              <View className="w-2 h-2 bg-[#EA4335] rounded-sm" />
            </View>
            <View className="flex-row">
              <View className="w-2 h-2 bg-[#FBBC05] rounded-sm" />
              <View className="w-2 h-2 bg-[#34A853] rounded-sm" />
            </View>
          </View>
          <Text className="text-text-primary font-semibold text-base">Continue with Google</Text>
        </View>
      )}
    </TouchableOpacity>
  );
}
