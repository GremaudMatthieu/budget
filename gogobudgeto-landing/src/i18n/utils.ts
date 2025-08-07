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
    return pathname === '/' ? '/en/' : `/en${pathname}`;
  }
  
  if (currentLocale === 'en' && targetLocale === 'fr') {
    return pathname.replace(/^\/en/, '') || '/';
  }
  
  return pathname;
}

export function getHreflangUrls(currentUrl: URL, site: string | URL | undefined): { fr: string, en: string } {
  const currentLocale = getLocaleFromUrl(currentUrl);
  const pathname = currentUrl.pathname;
  
  // Convert site to string and handle undefined
  const siteStr = site ? (typeof site === 'string' ? site : site.toString()) : 'https://gogobudgeto.com';
  const cleanSite = siteStr.endsWith('/') ? siteStr.slice(0, -1) : siteStr;
  
  if (currentLocale === 'fr') {
    const frUrl = currentUrl.href;
    const enPath = pathname === '/' ? '/en/' : `/en${pathname}`;
    const enUrl = `${cleanSite}${enPath}`;
    return { fr: frUrl, en: enUrl };
  } else {
    const enUrl = currentUrl.href;
    const frPath = pathname.replace(/^\/en/, '') || '/';
    const frUrl = `${cleanSite}${frPath}`;
    return { fr: frUrl, en: enUrl };
  }
}