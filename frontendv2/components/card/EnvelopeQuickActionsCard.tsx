import React from 'react';
import { View, Text, TouchableOpacity, Platform } from 'react-native';
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
  const isWeb = Platform.OS === 'web';
  
  // Use appropriate size for web vs mobile
  const buttonPadding = isWeb ? 'py-2 px-4' : 'p-4';
  const buttonTextSize = isWeb ? 'text-sm' : 'text-base';
  const buttonLayout = 'flex-1';
  
  return (
    <View className="bg-white rounded-xl shadow-md p-4 mb-4">
      <Text className="text-lg font-semibold text-text-primary mb-2">{t('envelopes.quickActions')}</Text>
      <View className="flex-row space-x-3 mb-4">
        <TouchableOpacity
          onPress={() => onQuickCredit('10')}
          className={`${buttonLayout} bg-success-100 ${buttonPadding} rounded-lg flex-row items-center justify-center ${isWeb ? 'hover:bg-success-200 transition-colors cursor-pointer' : ''}`}
          disabled={disabled}
        >
          <Ionicons name="add" size={16} color="#16a34a" />
          <Text className={`text-success-700 font-medium ml-1 ${buttonTextSize}`}>
            {t('envelopes.addFunds')} {10} {currency}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          onPress={() => onQuickDebit('10')}
          className={`${buttonLayout} bg-danger-100 ${buttonPadding} rounded-lg flex-row items-center justify-center ${isWeb ? 'hover:bg-danger-200 transition-colors cursor-pointer' : ''}`}
          disabled={disabled}
        >
          <Ionicons name="remove" size={16} color="#dc2626" />
          <Text className={`text-danger-700 font-medium ml-1 ${buttonTextSize}`}>
            {t('envelopes.withdraw')} {10} {currency}
          </Text>
        </TouchableOpacity>
      </View>
      <View className="flex-row space-x-3">
        <TouchableOpacity
          onPress={() => onQuickCredit('25')}
          className={`${buttonLayout} bg-success-100 ${buttonPadding} rounded-lg flex-row items-center justify-center ${isWeb ? 'hover:bg-success-200 transition-colors cursor-pointer' : ''}`}
          disabled={disabled}
        >
          <Ionicons name="add" size={16} color="#16a34a" />
          <Text className={`text-success-700 font-medium ml-1 ${buttonTextSize}`}>
            {t('envelopes.addFunds')} {25} {currency}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          onPress={() => onQuickDebit('25')}
          className={`${buttonLayout} bg-danger-100 ${buttonPadding} rounded-lg flex-row items-center justify-center ${isWeb ? 'hover:bg-danger-200 transition-colors cursor-pointer' : ''}`}
          disabled={disabled}
        >
          <Ionicons name="remove" size={16} color="#dc2626" />
          <Text className={`text-danger-700 font-medium ml-1 ${buttonTextSize}`}>
            {t('envelopes.withdraw')} {25} {currency}
          </Text>
        </TouchableOpacity>
      </View>
    </View>
  );
};

export default EnvelopeQuickActionsCard; 