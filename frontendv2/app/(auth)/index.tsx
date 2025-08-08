import React from 'react';
import { View, Text, TouchableOpacity, ScrollView, Platform, Linking } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import { useLanguage } from '@/contexts/LanguageContext';
import LanguageSwitcher from '@/components/LanguageSwitcher';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import WebContainer from '@/components/web/WebContainer';

function WelcomeContent() {
  const router = useRouter();
  const { t } = useTranslation();
  const { language } = useLanguage();

  const handleOpenTerms = () => {
    const url = language === 'fr' ? 'https://gogobudgeto.com/terms' : 'https://gogobudgeto.com/en/terms';
    Linking.openURL(url);
  };

  const handleOpenPrivacy = () => {
    const url = language === 'fr' ? 'https://gogobudgeto.com/privacy' : 'https://gogobudgeto.com/en/privacy';
    Linking.openURL(url);
  };

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

      {/* SEO Content Section */}
      <View className="px-6 mb-8">
        <Text className="text-xl font-semibold text-secondary-800 mb-3">{t('auth.whyChooseTitle')}</Text>
        <Text className="text-text-secondary mb-4 leading-6">{t('auth.whyChooseDescription')}</Text>
        
        <Text className="text-lg font-semibold text-secondary-800 mb-3 mt-6">{t('auth.howItWorksTitle')}</Text>
        <Text className="text-text-secondary mb-4 leading-6">{t('auth.howItWorksDescription')}</Text>
        
        <Text className="text-text-secondary leading-6">{t('auth.budgetingBenefits')}</Text>
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
      <View className="px-6 pb-20">
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
        
        <View className="flex-row flex-wrap justify-center mt-6">
          <Text className="text-text-muted text-sm text-center">
            {t('auth.termsAndPrivacy')}
          </Text>
          <TouchableOpacity onPress={handleOpenTerms}>
            <Text className="text-primary-600 text-sm underline">
              {t('auth.terms')}
            </Text>
          </TouchableOpacity>
          <Text className="text-text-muted text-sm text-center">
            {t('auth.and')}
          </Text>
          <TouchableOpacity onPress={handleOpenPrivacy}>
            <Text className="text-primary-600 text-sm underline">
              {t('auth.privacyPolicy')}
            </Text>
          </TouchableOpacity>
        </View>
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
        <View className="flex min-h-screen">
          <div className="text-center mb-8 pt-12">
            <h1 className="text-4xl font-bold text-slate-900 mb-4">{t('auth.seoTitle')}</h1>
            <h2 className="text-xl text-slate-600 mb-6">{t('auth.seoSubtitle')}</h2>
          </div>
          <div className="pb-32">
            <WelcomeContent />
          </div>
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