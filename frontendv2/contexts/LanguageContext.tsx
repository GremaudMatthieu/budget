import React, { createContext, useContext, useState, useEffect } from 'react';
import * as Localization from 'expo-localization';
import { useAuth } from './AuthContext';
import { setLocale, getStoredLanguage, storeLanguage } from '@/utils/i18n';

type LanguageContextType = {
  language: string;
  changeLanguage: (language: string) => Promise<void>;
};

// Create context with default values
const LanguageContext = createContext<LanguageContextType>({
  language: 'en',
  changeLanguage: async () => {},
});

// Custom hook to use the language context
export const useLanguage = () => useContext(LanguageContext);

export const LanguageProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [language, setLanguage] = useState<string>('en');
  const { user } = useAuth();

  // Initialize language based on user preference or device locale
  useEffect(() => {
    const initializeLanguage = async () => {
      try {
        // First, check if there's a stored language preference
        let storedLanguage = await getStoredLanguage();

        // If logged in, use user's preference if available
        if (user?.languagePreference) {
          storedLanguage = user.languagePreference;
        }

        // If no stored language, use device locale
        if (!storedLanguage) {
          const locales = Localization.getLocales();
          const deviceLocale = locales[0]?.languageCode || 'en';
          // Only allow supported languages (en, fr)
          storedLanguage = deviceLocale === 'fr' ? 'fr' : 'en';
        }

        // Apply the language
        await applyLanguage(storedLanguage);
      } catch (error) {
        console.error('Failed to initialize language:', error);
        // Default to English in case of error
        await applyLanguage('en');
      }
    };

    initializeLanguage();
  }, [user]);

  // Apply language changes
  const applyLanguage = async (lang: string) => {
    // Ensure we only accept supported languages
    const normalizedLang = lang === 'fr' ? 'fr' : 'en';
    
    // Update i18n locale
    setLocale(normalizedLang);
    
    // Update state
    setLanguage(normalizedLang);
    
    // Store preference for next app start
    await storeLanguage(normalizedLang);
  };

  // Function to change language
  const changeLanguage = async (lang: string) => {
    await applyLanguage(lang);
  };

  return (
    <LanguageContext.Provider value={{ language, changeLanguage }}>
      {children}
    </LanguageContext.Provider>
  );
};