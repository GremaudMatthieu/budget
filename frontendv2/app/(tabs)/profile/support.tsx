import React from 'react';
import { View, Text, TouchableOpacity, Linking } from 'react-native';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import { useTranslation } from '@/utils/useTranslation';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';
import { useRouter } from 'expo-router';

export default function SupportScreen() {
  const { t } = useTranslation();
  const supportEmail = 'support@yourcompany.com';
  const router = useRouter();
  return (
    <SwipeBackWrapper>
      <AnimatedHeaderLayout title={t('profile.helpSupport')} headerHeight={130} showBackButton onBack={() => router.replace('/profile')}>
        <View className="bg-white rounded-xl shadow-md p-6 mt-6 mx-4">
          <Text className="text-base text-text-primary mb-4">
            {t('profile.supportDescription', { defaultValue: 'Need help? Contact our support team and we will get back to you as soon as possible.' })}
          </Text>
          <TouchableOpacity
            className="bg-primary-600 rounded-xl py-3 px-6 items-center mb-2"
            onPress={() => Linking.openURL(`mailto:${supportEmail}`)}
          >
            <Text className="text-white font-semibold text-base">{supportEmail}</Text>
          </TouchableOpacity>
        </View>
      </AnimatedHeaderLayout>
    </SwipeBackWrapper>
  );
} 