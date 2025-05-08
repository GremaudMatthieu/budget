import React from 'react';
import { View, Text, TouchableOpacity, ScrollView, Platform } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import LanguageSwitcher from '@/components/LanguageSwitcher';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import WebContainer from '@/components/web/WebContainer';

function WelcomeContent() {
  const router = useRouter();
  const { t } = useTranslation();

  return (
    <View className="flex-1">
      {/* Features Section */}
      <View className="px-6 mt-4">
        <Text className="text-xl font-semibold text-secondary-800 mb-4">{t('auth.features')}</Text>
        
        <View className="bg-white p-4 rounded-xl shadow-sm mb-4">
          <View className="flex-row items-center">
            <View className="w-12 h-12 rounded-full bg-primary-100 items-center justify-center mr-4">
              <Ionicons name="wallet-outline" size={24} color="#0284c7" />
            </View>
            <View className="flex-1">
              <Text className="text-lg font-semibold text-text-primary mb-1">{t('auth.smartBudgetingTitle')}</Text>
              <Text className="text-text-secondary">{t('auth.smartBudgetingDescription')}</Text>
            </View>
          </View>
        </View>
        
        <View className="bg-white p-4 rounded-xl shadow-sm mb-4">
          <View className="flex-row items-center">
            <View className="w-12 h-12 rounded-full bg-accent-100 items-center justify-center mr-4">
              <Ionicons name="pie-chart-outline" size={24} color="#4f46e5" />
            </View>
            <View className="flex-1">
              <Text className="text-lg font-semibold text-text-primary mb-1">{t('auth.budgetPlanningTitle')}</Text>
              <Text className="text-text-secondary">{t('auth.budgetPlanningDescription')}</Text>
            </View>
          </View>
        </View>
        
        <View className="bg-white p-4 rounded-xl shadow-sm mb-8">
          <View className="flex-row items-center">
            <View className="w-12 h-12 rounded-full bg-success-100 items-center justify-center mr-4">
              <Ionicons name="analytics-outline" size={24} color="#16a34a" />
            </View>
            <View className="flex-1">
              <Text className="text-lg font-semibold text-text-primary mb-1">{t('auth.smartInsightsTitle')}</Text>
              <Text className="text-text-secondary">{t('auth.smartInsightsDescription')}</Text>
            </View>
          </View>
        </View>
      </View>
      
      {/* Testimonial/Highlight Section */}
      <View className="bg-secondary-900 mx-6 rounded-xl p-6 mb-8">
        <View className="bg-secondary-800 rounded-lg p-4 mb-4">
          <Text className="text-2xl font-bold text-white">{t('auth.saveUpTo')}</Text>
          <Text className="text-secondary-300">{t('auth.ofMonthlyExpenses')}</Text>
        </View>
        <View className="flex-row items-center">
          <View className="h-1 flex-1 bg-primary-500 rounded-full" />
          <View className="h-1 flex-1 bg-accent-500 rounded-full mx-1" />
          <View className="h-1 flex-1 bg-success-500 rounded-full" />
        </View>
      </View>

      {/* CTA Section */}
      <View className="px-6 pb-10">
        <TouchableOpacity
          className="bg-primary-600 rounded-xl py-4 items-center mb-4 shadow-md"
          onPress={() => router.push('/(auth)/signup')}
        >
          <Text className="text-white text-lg font-semibold">{t('auth.getStartedFree')}</Text>
        </TouchableOpacity>

        <TouchableOpacity
          className="bg-background-light border border-surface-border rounded-xl py-4 items-center"
          onPress={() => router.push('/(auth)/signin')}
        >
          <Text className="text-text-primary text-lg font-semibold">{t('auth.signIn')}</Text>
        </TouchableOpacity>
        
        <Text className="text-center mt-6 text-text-muted">
          {t('auth.termsAndPrivacy')}
        </Text>
      </View>
    </View>
  );
}

export default function WelcomeScreen() {
  const { t } = useTranslation();
  
  // Custom header buttons for language switcher
  const headerButtons = (
    <LanguageSwitcher isDark={true} />
  );
  
  if (Platform.OS === 'web') {
    return (
      <WebContainer>
        <View className="flex min-h-screen justify-center items-center">
          <WelcomeContent />
        </View>
      </WebContainer>
    );
  }
  return (
    <View className="flex-1">
      <AnimatedHeaderLayout
        title={t('auth.welcomeTitle')}
        subtitle={t('auth.welcomeSubtitle')}
        showBackButton={false}
        headerHeight={150}
        collapsePercentage={80}
        rightComponent={headerButtons}
      >
        <WelcomeContent />
      </AnimatedHeaderLayout>
    </View>
  );
}