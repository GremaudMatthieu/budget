import { useLanguage } from '@/contexts/LanguageContext';
import { t } from '@/utils/i18n';

/**
 * Format a numeric value as currency
 * @param amount The amount to format
 * @param currencyCode The currency code (USD, EUR, etc.)
 * @param language Optional language code to override the current language
 * @returns Formatted currency string
 */
export function formatCurrency(amount: number | string, currencyCode: string = 'USD', language?: string): string {
  const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
  
  if (isNaN(numericAmount)) {
    return `${getCurrencySymbol(currencyCode)}0.00`;
  }
  
  try {
    // Use the provided language or default to 'en-US'
    const locale = language === 'fr' ? 'fr-FR' : 'en-US';
    
    return new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currencyCode
    }).format(numericAmount);
  } catch (error) {
    // Fallback if the currency code is invalid
    return `${getCurrencySymbol(currencyCode)}${numericAmount.toFixed(2)}`;
  }
}

/**
 * Get the currency symbol for a given currency code
 * @param currencyCode The currency code (USD, EUR, etc.)
 * @returns The currency symbol
 */
export function getCurrencySymbol(currencyCode: string = 'USD'): string {
  const currencySymbols: {[key: string]: string} = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'JPY': '¥',
    'CAD': 'C$',
    'AUD': 'A$',
    'CHF': 'Fr',
    'CNY': '¥',
    'INR': '₹',
    'BRL': 'R$',
  };
  
  return currencySymbols[currencyCode] || currencyCode;
}

/**
 * Custom hook for formatting currency that automatically uses the current language
 * @returns A function to format currency based on the current language
 */
export function useCurrencyFormatter() {
  const { language } = useLanguage();
  
  return (amount: number | string, currencyCode: string = 'USD') => {
    return formatCurrency(amount, currencyCode, language);
  };
}

/**
 * Currency options for dropdowns with localized labels
 */
export function getCurrencyOptions() {
  return [
    { value: 'USD', label: `USD (${getCurrencySymbol('USD')})` },
    { value: 'EUR', label: `EUR (${getCurrencySymbol('EUR')})` },
    { value: 'GBP', label: `GBP (${getCurrencySymbol('GBP')})` },
    { value: 'JPY', label: `JPY (${getCurrencySymbol('JPY')})` },
    { value: 'CAD', label: `CAD (${getCurrencySymbol('CAD')})` },
    { value: 'AUD', label: `AUD (${getCurrencySymbol('AUD')})` },
    { value: 'CHF', label: `CHF (${getCurrencySymbol('CHF')})` },
    { value: 'CNY', label: `CNY (${getCurrencySymbol('CNY')})` },
    { value: 'INR', label: `INR (${getCurrencySymbol('INR')})` }
  ];
}

/**
 * Static currency options for dropdowns
 */
export const currencyOptions = [
  { value: 'USD', label: 'USD ($)' },
  { value: 'EUR', label: 'EUR (€)' },
  { value: 'GBP', label: 'GBP (£)' },
  { value: 'JPY', label: 'JPY (¥)' },
  { value: 'CAD', label: 'CAD (C$)' },
  { value: 'AUD', label: 'AUD (A$)' },
  { value: 'CHF', label: 'CHF' },
  { value: 'CNY', label: 'CNY (¥)' },
  { value: 'INR', label: 'INR (₹)' }
];