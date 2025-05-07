import React from 'react';
import { View, Text, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';

export interface EnvelopeQuickActionsCardProps {
  onQuickCredit: (amount: string) => void;
  onQuickDebit: (amount: string) => void;
  disabled?: boolean;
  currency: string;
}

/**
 * Card for quick credit/debit actions on an envelope.
 */
const EnvelopeQuickActionsCard: React.FC<EnvelopeQuickActionsCardProps> = ({
  onQuickCredit,
  onQuickDebit,
  disabled = false,
  currency,
}) => {
  const { t } = useTranslation();
  return (
    <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6 p-6">
      <Text className="text-lg font-semibold text-gray-900 mb-4">{t('envelopes.quickActions')}</Text>
      <View className="flex-row space-x-3 mb-4">
        <TouchableOpacity
          onPress={() => onQuickCredit('10')}
          className="flex-1 bg-green-100 p-4 rounded-xl flex-row items-center justify-center"
          disabled={disabled}
        >
          <Ionicons name="arrow-down-circle-outline" size={20} color="#16a34a" />
          <Text className="text-green-700 font-medium ml-2">{t('envelopes.addX', { amount: 10, currency })}</Text>
        </TouchableOpacity>
        <TouchableOpacity
          onPress={() => onQuickDebit('10')}
          className="flex-1 bg-red-100 p-4 rounded-xl flex-row items-center justify-center"
          disabled={disabled}
        >
          <Ionicons name="arrow-up-circle-outline" size={20} color="#dc2626" />
          <Text className="text-red-700 font-medium ml-2">{t('envelopes.spendX', { amount: 10, currency })}</Text>
        </TouchableOpacity>
      </View>
      <View className="flex-row space-x-3">
        <TouchableOpacity
          onPress={() => onQuickCredit('25')}
          className="flex-1 bg-green-100 p-4 rounded-xl flex-row items-center justify-center"
          disabled={disabled}
        >
          <Ionicons name="arrow-down-circle-outline" size={20} color="#16a34a" />
          <Text className="text-green-700 font-medium ml-2">{t('envelopes.addX', { amount: 25, currency })}</Text>
        </TouchableOpacity>
        <TouchableOpacity
          onPress={() => onQuickDebit('25')}
          className="flex-1 bg-red-100 p-4 rounded-xl flex-row items-center justify-center"
          disabled={disabled}
        >
          <Ionicons name="arrow-up-circle-outline" size={20} color="#dc2626" />
          <Text className="text-red-700 font-medium ml-2">{t('envelopes.spendX', { amount: 25, currency })}</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
};

export default EnvelopeQuickActionsCard; 