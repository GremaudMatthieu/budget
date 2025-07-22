import { translations, type TranslationKey, type Locale } from './translations';

export function t(key: TranslationKey, locale: Locale = 'fr'): string {
  return translations[locale][key] || translations.fr[key] || key;
}

export function getLocaleFromUrl(url: URL): Locale {
  const pathname = url.pathname;
  if (pathname.startsWith('/en')) return 'en';
  return 'fr';
}

export function getAlternateUrl(currentUrl: URL, targetLocale: Locale): string {
  const currentLocale = getLocaleFromUrl(currentUrl);
  const pathname = currentUrl.pathname;
  
  if (currentLocale === 'fr' && targetLocale === 'en') {
    return `/en${pathname === '/' ? '/' : pathname}`;
  }
  
  if (currentLocale === 'en' && targetLocale === 'fr') {
    return pathname.replace(/^\/en/, '') || '/';
  }
  
  return pathname;
}