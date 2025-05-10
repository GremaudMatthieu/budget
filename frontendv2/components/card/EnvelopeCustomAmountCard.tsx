import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity } from 'react-native';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';
import { useTranslation } from '@/utils/useTranslation';
import { validateEnvelopeAmountField } from '@/utils/validateEnvelopeAmount';

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

  // Use shared validation
  const validateAmountField = (value: string) => validateEnvelopeAmountField(value, t);

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
      <Text className="text-lg font-semibold text-text-primary mb-2 min-w-0 break-all" numberOfLines={2} ellipsizeMode="tail">{t('envelopes.customAmount')}</Text>
      <TextInput
        value={amount}
        onChangeText={v => {
          // Filter out all non-numeric characters except '.'
          const normalized = normalizeAmountInput(v);
          const filtered = normalized.replace(/[^0-9.]/g, '');
          setAmount(filtered);
          setTouched(true);
        }}
        placeholder={t('envelopes.enterAmountPlaceholder', { currency })}
        keyboardType="decimal-pad"
        className="border border-gray-300 rounded-lg p-3 mb-1 bg-white min-w-0 break-all"
        maxLength={13}
        onBlur={() => setTouched(true)}
        onFocus={() => setTouched(true)}
        style={{ fontSize: Number(amount) > 9999999 ? 14 : 16 }}
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