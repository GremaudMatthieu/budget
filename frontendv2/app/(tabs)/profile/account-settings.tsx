import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ActivityIndicator, Alert, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '@/contexts/AuthContext';
import { useUser } from '@/contexts/UserContext';
import { useTranslation } from '@/utils/useTranslation';
import { useRouter } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import { useLanguage } from '@/contexts/LanguageContext';
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';
import NameInput from '@/components/inputs/NameInput';
import SelectField from '@/components/inputs/SelectField';
import ResponsiveModal from '@/components/modals/ResponsiveModal';
import { ResponsiveFormActions } from '@/components/forms/ResponsiveForm';
import ActionButton from '@/components/buttons/ActionButton';

export default function AccountSettingsScreen() {
  const { t } = useTranslation();
  const { user, refreshUserData, logout } = useAuth();
  const { updateFirstName, updateLastName, updateLanguagePreference, deleteAccount } = useUser();
  const { changeLanguage, language } = useLanguage();
  const router = useRouter();

  // Helper function to clear stored tokens
  const clearStoredTokens = async () => {
    try {
      if (Platform.OS === 'web') {
        await AsyncStorage.removeItem('token');
        await AsyncStorage.removeItem('refresh_token');
      } else {
        await SecureStore.deleteItemAsync('token');
        await SecureStore.deleteItemAsync('refresh_token');
      }
    } catch (error) {
      console.error('Error clearing stored tokens:', error);
    }
  };

  const [form, setForm] = useState({
    firstName: user?.firstname || '',
    lastName: user?.lastname || '',
    language: language,
  });
  useEffect(() => {
    setForm(prev => ({ ...prev, language }));
  }, [language]);
  const [editing, setEditing] = useState<null | 'firstName' | 'lastName' | 'language'>(null);
  const [loading, setLoading] = useState(false);
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);

  const handleEdit = (field: 'firstName' | 'lastName' | 'language') => setEditing(field);
  const handleCancel = () => setEditing(null);

  const handleChange = (field: 'firstName' | 'lastName', value: string) => {
    setForm(prev => ({ ...prev, [field]: value }));
  };


  const handleSave = async () => {
    setLoading(true);
    try {
      if (editing === 'firstName') {
        await updateFirstName(form.firstName);
      } else if (editing === 'lastName') {
        await updateLastName(form.lastName);
      } else if (editing === 'language') {
        await updateLanguagePreference(form.language as 'en' | 'fr');
        await changeLanguage(form.language as 'en' | 'fr');
      }
      await refreshUserData();
      setEditing(null);
    } catch (err) {
      Alert.alert(t('common.error'), t('errors.serverError'));
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteAccount = async () => {
    if (Platform.OS === 'web') {
      setDeleteModalOpen(true);
    } else {
      Alert.alert(
        t('profile.deleteAccount'),
        t('profile.deleteAccountConfirm'),
        [
          { text: t('common.cancel'), style: 'cancel' },
          {
            text: t('common.delete'),
            style: 'destructive',
            onPress: confirmDeleteAccount,
          },
        ]
      );
    }
  };

  const confirmDeleteAccount = async () => {
    setLoading(true);
    setDeleteModalOpen(false);
    try {
      const result = await deleteAccount();
      // Backend handles all cleanup, clear frontend tokens and redirect
      await clearStoredTokens();
      
      if (Platform.OS === 'web') {
        // Force a complete page reload to reinitialize the app
        window.location.href = '/';
      } else {
        // On mobile, redirect to auth and the auth system should handle it
        router.replace('/(auth)');
      }
    } catch (err) {
      if (Platform.OS === 'web') {
        alert(t('errors.serverError'));
      } else {
        Alert.alert(t('common.error'), t('errors.serverError'));
      }
    } finally {
      setLoading(false);
    }
  };

  if (Platform.OS === 'web') {
    return (
      <div className="mb-8 mt-2">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <div>
            <h1 className="text-3xl font-bold text-slate-900 mb-1">{t('profile.accountSettings')}</h1>
          </div>
          {/* Place any important actions/info from the blue header here if needed */}
        </div>
        {/* Main content: replicate the content inside AnimatedHeaderLayout here for web */}
        <div className="bg-white rounded-xl shadow-md px-4 py-3 mb-3">
          <Text className="text-xs text-gray-500 mb-1">{t('profile.firstName')}</Text>
          {editing === 'firstName' ? (
            <View>
              <NameInput
                value={form.firstName}
                onChangeText={v => handleChange('firstName', v)}
                placeholder={t('profile.firstName')}
                autoFocus
                editable={!loading}
                autoCapitalize="words"
                accessibilityLabel={t('profile.firstName')}
                icon={<Ionicons name="person-outline" size={18} color="#64748b" />}
              />
              <View className={`flex-row ${Platform.OS === 'web' ? 'gap-3 justify-end' : 'space-x-3'} mt-3`}>
                <ActionButton
                  label={t('common.cancel')}
                  onPress={handleCancel}
                  disabled={loading}
                  variant="secondary"
                  size="sm"
                  fullWidth={Platform.OS !== 'web'}
                />
                <ActionButton
                  label={t('common.save')}
                  onPress={handleSave}
                  disabled={loading}
                  variant="primary"
                  size="sm"
                  fullWidth={Platform.OS !== 'web'}
                />
              </View>
            </View>
          ) : (
            <View className="flex-row items-center">
              <Text className="flex-1 text-lg text-gray-900">{form.firstName}</Text>
              <TouchableOpacity onPress={() => handleEdit('firstName')} className="ml-2" accessibilityLabel={t('common.edit')}>
                <Ionicons name="create-outline" size={20} color="#222" />
              </TouchableOpacity>
            </View>
          )}
        </div>
        {/* Last Name Card */}
        <div className="bg-white rounded-xl shadow-md px-4 py-3 mb-3">
          <Text className="text-xs text-gray-500 mb-1">{t('profile.lastName')}</Text>
          {editing === 'lastName' ? (
            <View>
              <NameInput
                value={form.lastName}
                onChangeText={v => handleChange('lastName', v)}
                placeholder={t('profile.lastName')}
                autoFocus
                editable={!loading}
                autoCapitalize="words"
                accessibilityLabel={t('profile.lastName')}
                icon={<Ionicons name="person-outline" size={18} color="#64748b" />}
              />
              <View className={`flex-row ${Platform.OS === 'web' ? 'gap-3 justify-end' : 'space-x-3'} mt-3`}>
                <ActionButton
                  label={t('common.cancel')}
                  onPress={handleCancel}
                  disabled={loading}
                  variant="secondary"
                  size="sm"
                  fullWidth={Platform.OS !== 'web'}
                />
                <ActionButton
                  label={t('common.save')}
                  onPress={handleSave}
                  disabled={loading}
                  variant="primary"
                  size="sm"
                  fullWidth={Platform.OS !== 'web'}
                />
              </View>
            </View>
          ) : (
            <View className="flex-row items-center">
              <Text className="flex-1 text-lg text-gray-900">{form.lastName}</Text>
              <TouchableOpacity onPress={() => handleEdit('lastName')} className="ml-2" accessibilityLabel={t('common.edit')}>
                <Ionicons name="create-outline" size={20} color="#222" />
              </TouchableOpacity>
            </View>
          )}
        </div>
        {/* Language Card */}
        <div className="bg-white rounded-xl shadow-md px-4 py-3 mb-3">
          <Text className="text-xs text-gray-500 mb-1">{t('profile.language')}</Text>
          {editing === 'language' ? (
            <>
                                <View>
                <SelectField
                  placeholder={t('profile.selectLanguage')}
                  options={[
                    { id: 'en', name: 'English', icon: 'language-outline', iconColor: '#0c6cf2' },
                    { id: 'fr', name: 'Français', icon: 'language-outline', iconColor: '#0c6cf2' }
                  ]}
                  value={form.language}
                  onChange={(value) => setForm(prev => ({ ...prev, language: value }))}
                  icon={<Ionicons name="language-outline" size={18} color="#64748b" />}
                  disabled={loading}
                  noMargin={true}
                />
                <View className={`flex-row ${Platform.OS === 'web' ? 'gap-3 justify-start' : 'space-x-3'} mt-3`}>
                  <ActionButton
                    label={t('common.save')}
                    onPress={handleSave}
                    disabled={loading}
                    variant="primary"
                    size="sm"
                  />
                  <ActionButton
                    label={t('common.cancel')}
                    onPress={handleCancel}
                    disabled={loading}
                    variant="secondary"
                    size="sm"
                  />
                </View>
              </View>
            </>
          ) : (
            <View className="flex-row items-center">
              <Text className="flex-1 text-lg text-gray-900">
                {form.language === 'en' ? 'English' : 'Français'}
              </Text>
              <TouchableOpacity onPress={() => handleEdit('language')} className="ml-2" accessibilityLabel={t('common.edit')}>
                <Ionicons name="create-outline" size={20} color="#222" />
              </TouchableOpacity>
            </View>
          )}
        </div>
        {/* Delete Account Button */}
        <div className={`${Platform.OS === 'web' ? 'flex justify-center' : ''} mt-8 mb-2`}>
          <TouchableOpacity 
            onPress={handleDeleteAccount}
            className={`bg-red-100 rounded-xl p-4 items-center ${Platform.OS === 'web' ? 'hover:bg-red-200 transition-colors min-w-[200px]' : ''}`}
            disabled={loading}
            accessibilityLabel={t('profile.deleteAccount')}
          >
            <Ionicons name="trash-outline" size={20} color="#dc2626" style={{ marginBottom: 2 }} />
            <Text className="text-red-700 font-semibold text-base">{t('profile.deleteAccount')}</Text>
          </TouchableOpacity>
        </div>

        <ResponsiveModal
          visible={deleteModalOpen}
          onClose={() => setDeleteModalOpen(false)}
          title={t('profile.deleteAccount')}
          size="sm"
          scrollable={false}
        >
          <View className="text-center space-y-6">
            <View className="items-center">
              <View className="w-16 h-16 rounded-full bg-danger-100 items-center justify-center mb-4">
                <Ionicons name="alert-outline" size={32} color="#dc2626" />
              </View>
            </View>
            
            <Text className="text-center text-gray-600 text-base">
              {t('profile.deleteAccountConfirm')}
            </Text>

            <ResponsiveFormActions>
              <ActionButton
                label={t('common.cancel')}
                onPress={() => setDeleteModalOpen(false)}
                variant="secondary"
                size="md"
              />
              <ActionButton
                label={loading ? t('common.loading') : t('common.delete')}
                onPress={confirmDeleteAccount}
                variant="danger"
                size="md"
                disabled={loading}
              />
            </ResponsiveFormActions>
          </View>
        </ResponsiveModal>
      </div>
    );
  }

  return (
    <SwipeBackWrapper>
      <View className="flex-1 bg-background-light">
        <AnimatedHeaderLayout title={t('profile.accountSettings')} headerHeight={180} showBackButton onBack={() => router.replace('/profile')}>
          <StatusBar style="light" />
          {/* First Name Card */}
          <View className="bg-white rounded-xl shadow-md px-4 py-3 mb-3">
            <Text className="text-xs text-gray-500 mb-1">{t('profile.firstName')}</Text>
            {editing === 'firstName' ? (
                    <View className="flex-row items-center">
                            <TextInput
                  value={form.firstName}
                  onChangeText={v => handleChange('firstName', v)}
                  className="flex-1 border border-gray-300 rounded-lg p-3 bg-white text-lg"
                                autoFocus
                  editable={!loading}
                  autoCapitalize="words"
                  accessibilityLabel={t('profile.firstName')}
                            />
                <TouchableOpacity onPress={handleSave} disabled={loading} className="ml-2 p-2 bg-green-100 rounded-full" accessibilityLabel={t('common.save')}>
                  {loading ? <ActivityIndicator /> : <Ionicons name="checkmark" size={20} color="#16a34a" />}
                            </TouchableOpacity>
                <TouchableOpacity onPress={handleCancel} disabled={loading} className="ml-2 p-2 bg-gray-200 rounded-full" accessibilityLabel={t('common.cancel')}>
                  <Ionicons name="close" size={20} color="#222" />
                            </TouchableOpacity>
                          </View>
                      ) : (
              <View className="flex-row items-center">
                <Text className="flex-1 text-lg text-gray-900">{form.firstName}</Text>
                <TouchableOpacity onPress={() => handleEdit('firstName')} className="ml-2" accessibilityLabel={t('common.edit')}>
                  <Ionicons name="create-outline" size={20} color="#222" />
                                </TouchableOpacity>
              </View>
            )}
            </View>

          {/* Last Name Card */}
          <View className="bg-white rounded-xl shadow-md px-4 py-3 mb-3">
            <Text className="text-xs text-gray-500 mb-1">{t('profile.lastName')}</Text>
            {editing === 'lastName' ? (
                <View className="flex-row items-center">
                <TextInput
                                  value={form.lastName}
                onChangeText={v => handleChange('lastName', v)}
                className="flex-1 border border-gray-300 rounded-lg p-3 bg-white text-lg"
                autoFocus
                editable={!loading}
                autoCapitalize="words"
                accessibilityLabel={t('profile.lastName')}
                />
                <TouchableOpacity onPress={handleSave} disabled={loading} className="ml-2 p-2 bg-green-100 rounded-full" accessibilityLabel={t('common.save')}>
                  {loading ? <ActivityIndicator /> : <Ionicons name="checkmark" size={20} color="#16a34a" />}
                </TouchableOpacity>
                <TouchableOpacity onPress={handleCancel} disabled={loading} className="ml-2 p-2 bg-gray-200 rounded-full" accessibilityLabel={t('common.cancel')}>
                  <Ionicons name="close" size={20} color="#222" />
                </TouchableOpacity>
              </View>
            ) : (
                <View className="flex-row items-center">
                <Text className="flex-1 text-lg text-gray-900">{form.lastName}</Text>
                <TouchableOpacity onPress={() => handleEdit('lastName')} className="ml-2" accessibilityLabel={t('common.edit')}>
                  <Ionicons name="create-outline" size={20} color="#222" />
                </TouchableOpacity>
                  </View>
            )}
                  </View>

          {/* Language Card */}
          <View className="bg-white rounded-xl shadow-md px-4 py-3 mb-3">
            <Text className="text-xs text-gray-500 mb-1">{t('profile.language')}</Text>
            {editing === 'language' ? (
              <>
                  <View>
                  <SelectField
                    placeholder={t('profile.selectLanguage')}
                    options={[
                      { id: 'en', name: 'English', icon: 'language-outline', iconColor: '#0c6cf2' },
                      { id: 'fr', name: 'Français', icon: 'language-outline', iconColor: '#0c6cf2' }
                    ]}
                    value={form.language}
                    onChange={(value) => setForm(prev => ({ ...prev, language: value }))}
                    icon={<Ionicons name="language-outline" size={18} color="#64748b" />}
                    disabled={loading}
                    noMargin={true}
                  />
                  <View className={`flex-row ${Platform.OS === 'web' ? 'gap-3 justify-start' : 'space-x-3'} mt-3`}>
                    <ActionButton
                      label={t('common.save')}
                      onPress={handleSave}
                      disabled={loading}
                      variant="primary"
                      size="sm"
                    />
                    <ActionButton
                      label={t('common.cancel')}
                      onPress={handleCancel}
                      disabled={loading}
                      variant="secondary"
                      size="sm"
                    />
                  </View>
                </View>
              </>
            ) : (
              <View className="flex-row items-center">
                <Text className="flex-1 text-lg text-gray-900">
                  {form.language === 'en' ? 'English' : 'Français'}
                </Text>
                <TouchableOpacity onPress={() => handleEdit('language')} className="ml-2" accessibilityLabel={t('common.edit')}>
                  <Ionicons name="create-outline" size={20} color="#222" />
                </TouchableOpacity>
              </View>
            )}
          </View>

          {/* Delete Account Button */}
          <View className="flex items-center mt-8 mb-2">
            <TouchableOpacity 
              onPress={handleDeleteAccount}
              className="bg-red-100 rounded-xl p-4 items-center min-w-[200px]"
              disabled={loading}
              accessibilityLabel={t('profile.deleteAccount')}
            >
              <Ionicons name="trash-outline" size={20} color="#dc2626" style={{ marginBottom: 2 }} />
              <Text className="text-red-700 font-semibold text-base">{t('profile.deleteAccount')}</Text>
            </TouchableOpacity>
          </View>
        </AnimatedHeaderLayout>
      </View>
    </SwipeBackWrapper>
  );
}