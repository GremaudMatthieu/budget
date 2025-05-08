import React from 'react';
import { View, Text, Linking, TouchableOpacity, Platform } from 'react-native';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import { useTranslation } from '@/utils/useTranslation';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';
import { useRouter } from 'expo-router';
import WebContainer from '@/components/web/WebContainer';

export default function AboutScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  if (Platform.OS === 'web') {
    return (
      <WebContainer>
        <div className="flex flex-col min-h-[calc(100vh-10rem)] justify-center items-center">
          <div className="bg-white rounded-xl shadow-md p-8 max-w-lg w-full">
            <h1 className="text-2xl font-bold mb-2">Budget App</h1>
            <p className="text-text-secondary mb-2">v1.0.0</p>
            <p className="text-base text-text-primary mb-4">
              {t('profile.aboutDescription', { defaultValue: 'A modern budgeting app to help you manage your finances with ease.' })}
            </p>
            <a href="https://yourcompany.com" className="text-primary-600 underline text-base font-semibold block text-center hover:text-primary-700 transition" target="_blank" rel="noopener noreferrer">
              yourcompany.com
            </a>
          </div>
        </div>
      </WebContainer>
    );
  }
  return (
    <SwipeBackWrapper>
      <AnimatedHeaderLayout title={t('profile.about')} headerHeight={130} showBackButton onBack={() => router.replace('/profile')}>
        <View className="bg-white rounded-xl shadow-md p-6 mt-6 mx-4">
          <Text className="text-2xl font-bold mb-2">Budget App</Text>
          <Text className="text-text-secondary mb-2">v1.0.0</Text>
          <Text className="text-base text-text-primary mb-4">
            {t('profile.aboutDescription', { defaultValue: 'A modern budgeting app to help you manage your finances with ease.' })}
          </Text>
          <TouchableOpacity onPress={() => Linking.openURL('https://yourcompany.com')}>
            <Text className="text-primary-600 underline text-base font-semibold">yourcompany.com</Text>
          </TouchableOpacity>
        </View>
      </AnimatedHeaderLayout>
    </SwipeBackWrapper>
  );
} 