import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity } from 'react-native';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';
import { useTranslation } from '@/utils/useTranslation';

export interface EnvelopeCustomAmountCardProps {
  amount: string;
  setAmount: (value: string) => void;
  onCredit: () => void;
  onDebit: () => void;
  disabled?: boolean;
  currency: string;
  currentAmount: number;
  targetAmount: number;
  setError: (msg: string) => void;
}

/**
 * Card for custom credit/debit actions on an envelope.
 */
const EnvelopeCustomAmountCard: React.FC<EnvelopeCustomAmountCardProps> = ({
  amount,
  setAmount,
  onCredit,
  onDebit,
  disabled = false,
  currency,
  currentAmount,
  targetAmount,
  setError,
}) => {
  const { t } = useTranslation();
  const [touched, setTouched] = useState(false);
  const [error, setLocalError] = useState<string | null>(null);

  // Validation logic
  const validateAmountField = (value: string) => {
    const MAX_AMOUNT = 9999999999.99;
    if (!value.trim()) return t('errors.amountRequired', { defaultValue: 'Amount is required' });
    if (value.trim().length < 1) return t('errors.amountTooShort', { defaultValue: 'Amount must be at least 1 character' });
    if (value.trim().length > 13) return t('errors.amountTooLong', { defaultValue: 'Amount must be at most 13 characters (e.g. 9999999999.99)' });
    if (!/^(\d{1,10})(\.\d{0,2})?$/.test(value)) return t('errors.amountInvalid', { defaultValue: 'Enter a valid amount with up to 2 decimals' });
    if (isNaN(Number(value)) || Number(value) <= 0) return t('errors.amountInvalid', { defaultValue: 'Enter a valid positive amount' });
    if (Number(value) > MAX_AMOUNT) return t('errors.amountTooLarge', { defaultValue: 'Amount must be at most 9999999999.99' });
    return null;
  };

  useEffect(() => {
    if (!touched) return;
    setLocalError(validateAmountField(amount));
  }, [amount, touched]);

  const handleCredit = () => {
    const localError = validateAmountField(amount);
    setLocalError(localError);
    if (localError || !amount.trim()) {
      setTouched(true);
      return;
    }
    const entered = parseFloat(amount);
    if (entered + currentAmount > targetAmount) {
      console.log('setError:', t('envelopes.cannotCreditMoreThanTarget'));
      setError(t('envelopes.cannotCreditMoreThanTarget'));
      return;
    }
    setAmount(Number(amount).toFixed(2));
    onCredit();
  };

  const handleDebit = () => {
    const localError = validateAmountField(amount);
    setLocalError(localError);
    if (localError || !amount.trim()) {
      setTouched(true);
      return;
    }
    const entered = parseFloat(amount);
    if (entered > currentAmount) {
      console.log('setError:', t('envelopes.cannotDebitMoreThanBalance'));
      setError(t('envelopes.cannotDebitMoreThanBalance'));
      return;
    }
    setAmount(Number(amount).toFixed(2));
    onDebit();
  };

  return (
    <View className="bg-white rounded-xl shadow-md p-4 mb-4">
      <Text className="text-lg font-semibold text-text-primary mb-2">{t('envelopes.customAmount')}</Text>
      <TextInput
        value={amount}
        onChangeText={v => {
          const normalized = normalizeAmountInput(v);
          setAmount(normalized);
          setTouched(true);
        }}
        placeholder={t('envelopes.enterAmountPlaceholder', { currency })}
        keyboardType="decimal-pad"
        className="border border-gray-300 rounded-lg p-3 mb-1 bg-white"
        maxLength={13}
        onBlur={() => setTouched(true)}
        onFocus={() => setTouched(true)}
      />
      {touched && error && (
        <Text className="text-red-500 text-xs mb-2">{error}</Text>
      )}
      <View className="flex-row space-x-3">
        <TouchableOpacity
          onPress={handleCredit}
          className="flex-1 bg-green-500 p-3 rounded-xl"
          disabled={disabled || !!error || !amount.trim()}
        >
          <Text className="text-white text-center font-semibold">{t('envelopes.credit')}</Text>
        </TouchableOpacity>
        <TouchableOpacity
          onPress={handleDebit}
          className="flex-1 bg-red-500 p-3 rounded-xl"
          disabled={disabled || !!error || !amount.trim()}
        >
          <Text className="text-white text-center font-semibold">{t('envelopes.debit')}</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
};

export default EnvelopeCustomAmountCard; 