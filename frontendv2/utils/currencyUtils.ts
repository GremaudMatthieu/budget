/**
 * Format a numeric value as currency
 * @param amount The amount to format
 * @param currencyCode The currency code (USD, EUR, etc.)
 * @returns Formatted currency string
 */
export function formatCurrency(amount: number | string, currencyCode: string = 'USD'): string {
  const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
  
  if (isNaN(numericAmount)) {
    return `${getCurrencySymbol(currencyCode)}0.00`;
  }
  
  try {
    return new Intl.NumberFormat('en-US', {
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
 * Currency options for dropdowns
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