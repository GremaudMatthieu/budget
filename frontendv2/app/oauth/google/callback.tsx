import React, { useEffect, useState } from 'react';
import { View, Text, ActivityIndicator, StyleSheet } from 'react-native';
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
      <View style={styles.container}>
        <StatusBar style="auto" />
        <View style={styles.errorContainer}>
          <Text style={styles.errorTitle}>Authentication Error</Text>
          <Text style={styles.errorText}>{error}</Text>
          <Text 
            style={styles.returnLink}
            onPress={() => router.replace('/signin')}
          >
            Return to Sign In
          </Text>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar style="auto" />
      <ActivityIndicator size="large" color="#4a6fa5" />
      <Text style={styles.loadingText}>Completing authentication...</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f7fa',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20,
  },
  loadingText: {
    marginTop: 20,
    fontSize: 16,
    color: '#333',
  },
  errorContainer: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 20,
    alignItems: 'center',
    justifyContent: 'center',
    maxWidth: 400,
    width: '100%',
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  errorTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#dc2626',
    marginBottom: 12,
  },
  errorText: {
    fontSize: 16,
    color: '#4b5563',
    textAlign: 'center',
    marginBottom: 16,
  },
  returnLink: {
    color: '#4a6fa5',
    fontSize: 16,
    fontWeight: '500',
    textDecorationLine: 'underline',
  },
});