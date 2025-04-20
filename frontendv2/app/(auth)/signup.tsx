import { useState } from 'react';
import { View, Text, TouchableOpacity, KeyboardAvoidingView, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useTranslation } from '../../utils/useTranslation';
import GoogleSignInButton from '../../components/GoogleSignInButton';
import { Ionicons } from '@expo/vector-icons';
import LanguageSwitcher from '@/components/LanguageSwitcher';

export default function SignupScreen() {
  const { t } = useTranslation();
  const [error] = useState<string | null>(null);
  const router = useRouter();

  return (
    <KeyboardAvoidingView
      className="flex-1 bg-background-light"
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <StatusBar style="dark" />
      
      {/* Top Design Element */}
      <View className="bg-primary-600 h-56 rounded-b-[40px] absolute top-0 left-0 right-0">
        <View className="absolute bottom-0 right-0 w-24 h-24 bg-primary-500 rounded-tl-full" />
        <View className="absolute top-12 left-8 w-16 h-16 bg-primary-500/50 rounded-full" />
      </View>
      
      {/* Back Button and Language Switcher */}
      <View className="absolute top-14 left-0 right-0 z-10 px-5 flex-row justify-between">
        <TouchableOpacity 
          className="bg-white/20 p-2 rounded-full"
          onPress={() => router.back()}
        >
          <Ionicons name="arrow-back" size={24} color="white" />
        </TouchableOpacity>
        
        <LanguageSwitcher isDark={true} />
      </View>
      
      <View className="flex-1 px-6 justify-center max-w-[500px] w-full self-center pt-20">
        <View className="mb-8 items-center">
          <Text className="text-3xl font-bold text-text-primary mb-2">{t('auth.createAccount')}</Text>
          <Text className="text-text-secondary text-center">{t('auth.signUpSubtitle')}</Text>
        </View>
        
        {error && (
          <View className="bg-danger-50 p-4 rounded-xl mb-5 border border-danger-200 flex-row items-center">
            <Ionicons name="alert-circle" size={24} color="#ef4444" className="mr-2" />
            <Text className="text-danger-800 text-base flex-1">{error}</Text>
          </View>
        )}

        <View className="card mb-6">
          <GoogleSignInButton />
        </View>

        <View className="flex-row items-center my-8">
          <View className="flex-1 h-[1px] bg-surface-border" />
          <Text className="text-text-muted mx-4">or</Text>
          <View className="flex-1 h-[1px] bg-surface-border" />
        </View>

        {/* Email Sign Up Option */}
        <TouchableOpacity 
          className="border border-surface-borderDark bg-surface-light rounded-xl py-4 px-6 flex-row items-center justify-center mb-6 shadow-sm"
          onPress={() => router.push('/email-signup')}
        >
          <Ionicons name="mail-outline" size={20} color="#64748b" />
          <Text className="text-text-primary ml-2 font-medium">{t('auth.signUpWithEmail')}</Text>
        </TouchableOpacity>

        <View className="flex-row justify-center mt-4">
          <Text className="text-text-secondary">{t('auth.alreadyHaveAccount')} </Text>
          <TouchableOpacity onPress={() => router.replace('/signin')}>
            <Text className="text-primary-600 font-semibold">{t('auth.signIn')}</Text>
          </TouchableOpacity>
        </View>
        
        {/* Footer Security Message */}
        <View className="mt-12 items-center">
          <View className="flex-row items-center mb-2">
            <Ionicons name="shield-checkmark" size={16} color="#64748b" />
            <Text className="text-text-muted ml-2 text-sm">{t('auth.secureAuthentication')}</Text>
          </View>
          <Text className="text-text-muted text-xs text-center">
            {t('auth.termsAndPrivacy')}
          </Text>
        </View>
      </View>
    </KeyboardAvoidingView>
  );
}