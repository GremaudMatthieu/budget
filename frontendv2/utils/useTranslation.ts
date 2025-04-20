import { useCallback } from 'react';
import { useLanguage } from '@/contexts/LanguageContext';
import { t as translate } from './i18n';

/**
 * Custom hook for using translations with automatic re-render when language changes
 * @returns Object with t function for translations and current locale
 */
export const useTranslation = () => {
  const { language } = useLanguage();
  
  // Memoize the translation function so it only changes when language changes
  const t = useCallback((key: string, params?: Record<string, any>): string => {
    return translate(key, params);
  }, [language]);
  
  return { t, language };
};