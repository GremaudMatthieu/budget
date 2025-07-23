import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
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

  const isWeb = Platform.OS === 'web';
  
  // Use same size as envelope listing buttons
  const buttonPadding = isWeb ? 'py-2 px-4' : 'p-3';
  const buttonTextSize = isWeb ? 'text-sm' : 'text-base';
  const buttonLayout = 'flex-1';

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
          className={`${buttonLayout} bg-success-100 ${buttonPadding} rounded-lg flex-row items-center justify-center ${isWeb ? 'hover:bg-success-200 transition-colors cursor-pointer' : ''}`}
          disabled={disabled || !!error || !amount.trim()}
        >
          <Ionicons name="add" size={16} color="#16a34a" />
          <Text className={`text-success-700 font-medium ml-1 ${buttonTextSize}`}>
            {t('envelopes.addFunds')}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          onPress={handleDebit}
          className={`${buttonLayout} bg-danger-100 ${buttonPadding} rounded-lg flex-row items-center justify-center ${isWeb ? 'hover:bg-danger-200 transition-colors cursor-pointer' : ''}`}
          disabled={disabled || !!error || !amount.trim()}
        >
          <Ionicons name="remove" size={16} color="#dc2626" />
          <Text className={`text-danger-700 font-medium ml-1 ${buttonTextSize}`}>
            {t('envelopes.withdraw')}
          </Text>
        </TouchableOpacity>
      </View>
    </View>
  );
};

export default EnvelopeCustomAmountCard; 