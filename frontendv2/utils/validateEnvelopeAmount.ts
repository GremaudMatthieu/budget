import { normalizeAmountInput } from './normalizeAmountInput';

/**
 * Validates envelope amount input for both list and detail screens.
 * @param value The input string
 * @param t The translation function
 * @returns Error string (translated) or null if valid
 */
export function validateEnvelopeAmountField(
  value: string,
  t: (key: string, params?: Record<string, any>) => string
): string | null {
  const MAX_AMOUNT = 9999999999.99;
  const normalized = normalizeAmountInput(value);
  if (!normalized.trim()) return t('errors.amountRequired', { defaultValue: 'Amount is required' });
  if (normalized.trim().length < 1) return t('errors.amountTooShort', { defaultValue: 'Amount must be at least 1 character' });
  if (normalized.trim().length > 13) return t('errors.amountTooLong', { defaultValue: 'Amount must be at most 13 characters (e.g. 9999999999.99)' });
  if (!/^\d{1,10}(\.\d{0,2})?$/.test(normalized)) return t('errors.amountInvalid', { defaultValue: 'Enter a valid amount with up to 2 decimals' });
  if (isNaN(Number(normalized)) || Number(normalized) <= 0) return t('errors.amountInvalid', { defaultValue: 'Enter a valid positive amount' });
  if (Number(normalized) > MAX_AMOUNT) return t('errors.amountTooLarge', { defaultValue: 'Amount must be at most 9999999999.99' });
  return null;
} 