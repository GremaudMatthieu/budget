import React, { useState } from 'react';
import { View, Text, TouchableOpacity, Modal, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useLanguage } from '@/contexts/LanguageContext';
import { useTranslation } from '@/utils/useTranslation';

interface LanguageSwitcherProps {
  isDark?: boolean;
  small?: boolean;
}

const LanguageSwitcher: React.FC<LanguageSwitcherProps> = ({ isDark = false, small = false }) => {
  const [modalVisible, setModalVisible] = useState(false);
  const { language, changeLanguage } = useLanguage();
  const { t } = useTranslation();

  const toggleModal = () => {
    setModalVisible(!modalVisible);
  };

  const selectLanguage = async (lang: string) => {
    await changeLanguage(lang);
    setModalVisible(false);
  };

  const languages = [
    { code: 'en', name: t('profile.english') },
    { code: 'fr', name: t('profile.french') }
  ];

  const textColor = isDark ? 'text-white' : 'text-text-primary';
  const buttonSize = small ? 'p-1.5' : 'p-2';
  const iconSize = small ? 16 : 20;

  return (
    <View>
      <TouchableOpacity 
        onPress={toggleModal} 
        className={`flex-row items-center ${isDark ? 'bg-white/20' : 'bg-primary-50'} rounded-full ${buttonSize}`}
      >
        <Ionicons name="language" size={iconSize} color={isDark ? "white" : "#0284c7"} />
        {!small && (
          <Text className={`ml-1 ${textColor} text-sm font-medium`}>
            {language === 'en' ? 'EN' : 'FR'}
          </Text>
        )}
      </TouchableOpacity>

      <Modal
        visible={modalVisible}
        transparent={true}
        animationType="fade"
        onRequestClose={toggleModal}
      >
        <TouchableOpacity 
          style={styles.modalOverlay} 
          activeOpacity={1} 
          onPress={toggleModal}
        >
          <View className="bg-white rounded-xl p-4 w-40 shadow-lg">
            <View className="flex-row justify-between items-center mb-3">
              <Text className="font-medium text-text-primary">{t('profile.language')}</Text>
              <TouchableOpacity onPress={toggleModal}>
                <Ionicons name="close" size={20} color="#64748b" />
              </TouchableOpacity>
            </View>
            
            {languages.map(lang => (
              <TouchableOpacity
                key={lang.code}
                onPress={() => selectLanguage(lang.code)}
                className={`p-2 ${language === lang.code ? 'bg-primary-50 rounded-lg' : ''} mb-1 flex-row items-center`}
              >
                <Ionicons 
                  name={language === lang.code ? "radio-button-on" : "radio-button-off"} 
                  size={16} 
                  color="#0284c7" 
                  className="mr-2"
                />
                <Text className="text-text-primary">{lang.name}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </TouchableOpacity>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  modalOverlay: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
  },
});

export default LanguageSwitcher;