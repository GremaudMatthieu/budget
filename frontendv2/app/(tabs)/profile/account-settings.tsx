import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Modal, Pressable, ScrollView } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '@/contexts/AuthContext';
import { useUser } from '@/contexts/UserContext';
import { useTranslation } from '@/utils/useTranslation';
import { useRouter } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import { useLanguage } from '@/contexts/LanguageContext';

export default function AccountSettingsScreen() {
  const { t } = useTranslation();
  const { user, refreshUserData } = useAuth();
  const { updateFirstName, updateLastName, updateLanguagePreference, deleteAccount } = useUser();
  const { changeLanguage, language } = useLanguage();
  const router = useRouter();

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
  const [languageModalOpen, setLanguageModalOpen] = useState(false);

  const handleEdit = (field: 'firstName' | 'lastName' | 'language') => setEditing(field);
  const handleCancel = () => setEditing(null);

  const handleChange = (field: 'firstName' | 'lastName', value: string) => {
    setForm(prev => ({ ...prev, [field]: value }));
  };

  const handleLanguageSelect = (lang: string) => {
    setForm(prev => ({ ...prev, language: lang }));
    setLanguageModalOpen(false);
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
      Alert.alert(t('common.error'), t('account.updateFailed'));
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteAccount = async () => {
    Alert.alert(
      t('account.deleteAccount'),
      t('account.deleteAccountConfirm'),
      [
        { text: t('common.cancel'), style: 'cancel' },
        {
          text: t('common.delete'),
          style: 'destructive',
          onPress: async () => {
            setLoading(true);
            try {
              await deleteAccount();
              router.replace('/signin');
            } catch (err) {
              Alert.alert(t('common.error'), t('account.deleteFailed'));
            } finally {
              setLoading(false);
            }
          },
        },
      ]
    );
  };

        return (
    <SwipeBackWrapper>
      <View className="flex-1 bg-background-light">
        <AnimatedHeaderLayout title={t('account.settings')} headerHeight={180} showBackButton onBack={() => router.replace('/profile')}>
          <StatusBar style="light" />
          {/* First Name Card */}
          <View className="bg-white rounded-xl shadow-md px-4 py-3 mb-3">
            <Text className="text-xs text-gray-500 mb-1">{t('account.firstName')}</Text>
            {editing === 'firstName' ? (
                    <View className="flex-row items-center">
                            <TextInput
                  value={form.firstName}
                  onChangeText={v => handleChange('firstName', v)}
                  className="flex-1 border border-gray-300 rounded-lg p-3 bg-white text-lg"
                                autoFocus
                  editable={!loading}
                  autoCapitalize="words"
                  accessibilityLabel={t('account.firstName')}
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
            <Text className="text-xs text-gray-500 mb-1">{t('account.lastName')}</Text>
            {editing === 'lastName' ? (
                <View className="flex-row items-center">
                <TextInput
                  value={form.lastName}
                  onChangeText={v => handleChange('lastName', v)}
                  className="flex-1 border border-gray-300 rounded-lg p-3 bg-white text-lg"
                  autoFocus
                  editable={!loading}
                  autoCapitalize="words"
                  accessibilityLabel={t('account.lastName')}
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
            <Text className="text-xs text-gray-500 mb-1">{t('account.language')}</Text>
            {editing === 'language' ? (
              <>
                  <TouchableOpacity 
                  onPress={() => setLanguageModalOpen(true)}
                  className="flex-row items-center border border-gray-300 rounded-lg p-3 bg-white"
                  accessibilityLabel={t('account.language')}
                >
                  <Text className="flex-1 text-lg text-gray-900">
                    {form.language === 'en' ? 'English' : 'Français'}
                  </Text>
                  <Ionicons name="chevron-down" size={20} color="#222" />
                </TouchableOpacity>
                <View className="flex-row mt-2">
                  <TouchableOpacity onPress={handleSave} disabled={loading} className="flex-1 p-2 bg-green-100 rounded-full mr-2" accessibilityLabel={t('common.save')}>
                    {loading ? <ActivityIndicator /> : <Text className="text-green-700 text-center font-semibold">{t('common.save')}</Text>}
                  </TouchableOpacity>
                  <TouchableOpacity onPress={handleCancel} disabled={loading} className="flex-1 p-2 bg-gray-200 rounded-full" accessibilityLabel={t('common.cancel')}>
                    <Text className="text-gray-700 text-center font-semibold">{t('common.cancel')}</Text>
                  </TouchableOpacity>
                </View>
                <Modal
                  visible={languageModalOpen}
                  transparent
                  animationType="fade"
                  onRequestClose={() => setLanguageModalOpen(false)}
                >
                  <Pressable style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.3)' }} onPress={() => setLanguageModalOpen(false)} />
                  <View className="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl p-6 shadow-lg">
                    <TouchableOpacity
                      onPress={() => handleLanguageSelect('en')}
                      className={`py-4 ${form.language === 'en' ? 'bg-primary-100' : ''} rounded-lg mb-2`}
                      accessibilityLabel="English"
                    >
                      <Text className={`text-lg ${form.language === 'en' ? 'text-primary-700 font-bold' : 'text-gray-900'}`}>English</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                      onPress={() => handleLanguageSelect('fr')}
                      className={`py-4 ${form.language === 'fr' ? 'bg-primary-100' : ''} rounded-lg mb-2`}
                      accessibilityLabel="Français"
                    >
                      <Text className={`text-lg ${form.language === 'fr' ? 'text-primary-700 font-bold' : 'text-gray-900'}`}>Français</Text>
                    </TouchableOpacity>
                  </View>
                </Modal>
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
          <TouchableOpacity 
            onPress={handleDeleteAccount}
            className="bg-red-100 rounded-xl p-4 items-center mt-8 mb-2"
            disabled={loading}
            accessibilityLabel={t('account.deleteAccount')}
          >
            <Ionicons name="trash-outline" size={20} color="#dc2626" style={{ marginBottom: 2 }} />
            <Text className="text-red-700 font-semibold text-base">{t('account.deleteAccount')}</Text>
          </TouchableOpacity>
        </AnimatedHeaderLayout>
      </View>
    </SwipeBackWrapper>
  );
}