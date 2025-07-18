import { useState } from 'react';
import { View, Text, TouchableOpacity, KeyboardAvoidingView, Platform, ScrollView } from 'react-native';
import { useRouter } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useTranslation } from '../../utils/useTranslation';
import GoogleSignInButton from '../../components/GoogleSignInButton';
import { Ionicons } from '@expo/vector-icons';
import LanguageSwitcher from '@/components/LanguageSwitcher';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import WebContainer from '@/components/web/WebContainer';

function SignInContent() {
  const { t } = useTranslation();
  const [error] = useState<string | null>(null);
  const router = useRouter();

  return (
    <View className="flex-1 px-6 justify-center max-w-[500px] w-full self-center">
      <View className="mb-8 items-center">
        <Text className="text-3xl font-bold text-text-primary mb-2">{t('auth.welcomeBack')}</Text>
        <Text className="text-text-secondary text-center">{t('auth.signInSubtitle')}</Text>
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

      <View className="flex-row justify-center mt-4">
        <Text className="text-text-secondary">{t('auth.dontHaveAccount')} </Text>
        <TouchableOpacity onPress={() => router.push('/signup')}>
          <Text className="text-primary-600 font-semibold">{t('auth.signUp')}</Text>
        </TouchableOpacity>
      </View>
      
      {/* Footer Security Message */}
      <View className="mt-12 items-center">
        <View className="flex-row items-center mb-2">
          <Ionicons name="shield-checkmark" size={16} color="#64748b" />
          <Text className="text-text-muted ml-2 text-sm">{t('auth.secureAuthentication')}</Text>
        </View>
        <Text className="text-text-muted text-xs text-center">
          {t('auth.dataProtection')}
        </Text>
      </View>
    </View>
  );
}

export default function SignInScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  // Custom header buttons for language switcher
  const headerButtons = (
    <LanguageSwitcher isDark={true} />
  );
  if (Platform.OS === 'web') {
    return (
      <WebContainer>
        <View className="flex min-h-screen justify-center items-center">
          <SignInContent />
        </View>
      </WebContainer>
    );
  }
  return (
    <KeyboardAvoidingView
      className="flex-1"
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <AnimatedHeaderLayout
        title={t('auth.signIn')}
        subtitle={t('auth.welcomeBack')}
        showBackButton={true}
        headerHeight={130}
        collapsePercentage={80}
        rightComponent={headerButtons}
      >
        <SignInContent />
      </AnimatedHeaderLayout>
    </KeyboardAvoidingView>
  );
}