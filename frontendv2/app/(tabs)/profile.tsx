import React from 'react';
import { View, Text, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '@/contexts/AuthContext';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import { useTranslation } from '@/utils/useTranslation';

// Content component for the profile, which will be wrapped with the animated header
function ProfileContent() {
  const router = useRouter();
  const { user, logout } = useAuth();
  const { t } = useTranslation();

  const handleLogout = async () => {
    await logout();
    router.replace('/');
  };

  return (
    <View className="flex-1">
      {/* User Profile Card */}
      <View className="bg-white rounded-xl shadow-sm p-5 mb-6 mx-4">
        <View className="items-center mb-4">
          <View className="w-20 h-20 rounded-full bg-primary-100 items-center justify-center mb-2">
            <Text className="text-primary-600 text-2xl font-bold">
              {user?.firstname ? user.firstname.charAt(0).toUpperCase() : 'U'}
            </Text>
          </View>
          <Text className="text-lg font-semibold text-text-primary">{user?.firstname || t('profile.user')}</Text>
          <Text className="text-text-secondary">{user?.email || ''}</Text>
        </View>
      </View>

      {/* Settings Menu */}
      <View className="bg-white rounded-xl shadow-sm mb-6 mx-4">
        <TouchableOpacity
          className="flex-row items-center justify-between p-4 border-b border-surface-border"
          onPress={() => {
            router.push('/profile/account-settings');
          }}
        >
          <View className="flex-row items-center">
            <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
              <Ionicons name="person-outline" size={16} color="#0c6cf2" />
            </View>
            <Text className="text-text-primary">{t('profile.accountSettings')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#64748b" />
        </TouchableOpacity>

        <TouchableOpacity
          className="flex-row items-center justify-between p-4 border-b border-surface-border"
          onPress={() => {
            // Navigate to notifications settings
          }}
        >
          <View className="flex-row items-center">
            <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
              <Ionicons name="notifications-outline" size={16} color="#0c6cf2" />
            </View>
            <Text className="text-text-primary">{t('profile.notifications')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#64748b" />
        </TouchableOpacity>

        <TouchableOpacity
          className="flex-row items-center justify-between p-4 border-b border-surface-border"
          onPress={() => {
            // Navigate to security settings
          }}
        >
          <View className="flex-row items-center">
            <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
              <Ionicons name="shield-checkmark-outline" size={16} color="#0c6cf2" />
            </View>
            <Text className="text-text-primary">{t('profile.security')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#64748b" />
        </TouchableOpacity>

        <TouchableOpacity
          className="flex-row items-center justify-between p-4"
          onPress={() => {
            // Navigate to appearance settings
          }}
        >
          <View className="flex-row items-center">
            <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
              <Ionicons name="color-palette-outline" size={16} color="#0c6cf2" />
            </View>
            <Text className="text-text-primary">{t('profile.appearance')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#64748b" />
        </TouchableOpacity>
      </View>

      {/* Support and About */}
      <View className="bg-white rounded-xl shadow-sm mb-6 mx-4">
        <TouchableOpacity
          className="flex-row items-center justify-between p-4 border-b border-surface-border"
          onPress={() => {
            // Navigate to help & support
          }}
        >
          <View className="flex-row items-center">
            <View className="w-8 h-8 rounded-full bg-accent-100 items-center justify-center mr-3">
              <Ionicons name="help-circle-outline" size={16} color="#4f46e5" />
            </View>
            <Text className="text-text-primary">{t('profile.helpSupport')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#64748b" />
        </TouchableOpacity>

        <TouchableOpacity
          className="flex-row items-center justify-between p-4"
          onPress={() => {
            // Navigate to about page
          }}
        >
          <View className="flex-row items-center">
            <View className="w-8 h-8 rounded-full bg-accent-100 items-center justify-center mr-3">
              <Ionicons name="information-circle-outline" size={16} color="#4f46e5" />
            </View>
            <Text className="text-text-primary">{t('profile.about')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#64748b" />
        </TouchableOpacity>
      </View>

      {/* Logout Button */}
      <TouchableOpacity
        className="bg-danger-100 py-4 rounded-xl mb-6 mx-4"
        onPress={handleLogout}
      >
        <Text className="text-danger-600 text-center font-semibold">{t('profile.logout')}</Text>
      </TouchableOpacity>

      <Text className="text-center text-text-muted text-xs mb-6">
        {t('profile.version', { version: '1.0.0' })}
      </Text>
    </View>
  );
}

function ProfileScreen() {
  const { t } = useTranslation();
  
  return (
    <AnimatedHeaderLayout
      title={t('profile.title')}
      subtitle={t('profile.subtitle')}
      headerHeight={130}
    >
      <ProfileContent />
    </AnimatedHeaderLayout>
  );
}

// Apply the withAnimatedHeader HOC to wrap the Profile content
export default ProfileScreen