import AsyncStorage from '@react-native-async-storage/async-storage';
import en from '@/translations/en';
import fr from '@/translations/fr';

// Available translations
const translations = {
  en,
  fr,
};

// Current locale
let currentLocale = 'en';

// Storage key for language preference
const LANGUAGE_STORAGE_KEY = 'app_language';

/**
 * Initialize the locale
 * @param locale The locale to set
 */
export const setLocale = (locale: string) => {
  currentLocale = locale in translations ? locale : 'en';
};

/**
 * Get a translation by key
 * @param key The translation key in dot notation (e.g., 'auth.login')
 * @param params Optional parameters for string interpolation
 * @returns The translated string
 */
export const t = (key: string, params?: Record<string, any>): string => {
  // Split the key by dots to traverse the translations object
  const keys = key.split('.');
  
  // Get the translations for the current locale
  const localeTranslations = translations[currentLocale as keyof typeof translations] || translations.en;
  
  // Traverse the translation object to find the value
  let value = localeTranslations;
  for (const k of keys) {
    if (value && typeof value === 'object' && k in value) {
      value = value[k as keyof typeof value];
    } else {
      // If the key doesn't exist, return the key itself as fallback
      return key;
    }
  }
  
  // If the value is not a string, return the key
  if (typeof value !== 'string') {
    return key;
  }
  
  // Substitute any parameters in the string
  if (params) {
    return value.replace(/\{\{(\w+)\}\}/g, (_, paramKey) => {
      return params[paramKey] !== undefined ? String(params[paramKey]) : `{{${paramKey}}}`;
    });
  }
  
  return value;
};

/**
 * Store the language preference
 * @param language The language code to store
 */
export const storeLanguage = async (language: string): Promise<void> => {
  try {
    await AsyncStorage.setItem(LANGUAGE_STORAGE_KEY, language);
  } catch (error) {
    console.error('Failed to store language preference:', error);
  }
};

/**
 * Get the stored language preference
 * @returns The stored language code or null if not found
 */
export const getStoredLanguage = async (): Promise<string | null> => {
  try {
    return await AsyncStorage.getItem(LANGUAGE_STORAGE_KEY);
  } catch (error) {
    console.error('Failed to get stored language preference:', error);
    return null;
  }
};

// Export supported languages
export const supportedLanguages = [
  { code: 'en', name: 'English' },
  { code: 'fr', name: 'Français' },
];