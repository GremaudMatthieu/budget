import React from 'react';
import { View, Text } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Envelope } from '@/services/envelopeService';
import { useCurrencyFormatter } from '@/utils/currencyUtils';
import { useTranslation } from '@/utils/useTranslation';
import EnvelopePieChart from './EnvelopePieChart';

interface EnvelopeCardProps {
  envelope: Envelope;
}

const EnvelopeCard: React.FC<EnvelopeCardProps> = ({ envelope }) => {
  const progress = Number(envelope.currentAmount) / Number(envelope.targetedAmount);
  const formatCurrency = useCurrencyFormatter();
  const { t } = useTranslation();
  
  return (
    <View className="p-4">
      <View className="flex-row justify-between items-center mb-4">
        {progress >= 1 ? (
          <View className="bg-success-50 px-2 py-1 rounded-full">
            <Text className="text-success-700 text-xs font-medium">{t('envelopes.completed')}</Text>
          </View>
        ) : progress > 0 ? (
          <View className="bg-primary-50 px-2 py-1 rounded-full">
            <Text className="text-primary-700 text-xs font-medium">{Math.round(progress * 100)}% {t('envelopes.filled')}</Text>
          </View>
        ) : (
          <View className="bg-secondary-50 px-2 py-1 rounded-full">
            <Text className="text-secondary-700 text-xs font-medium">{t('envelopes.notStarted')}</Text>
          </View>
        )}
        
        {envelope.pending && (
          <View className="bg-warning-50 px-2 py-1 rounded-full flex-row items-center">
            <Ionicons name="sync" size={12} color="#d97706" className="animate-spin mr-1" />
            <Text className="text-warning-700 text-xs font-medium">{t('common.pending')}</Text>
          </View>
        )}
      </View>
      
      <View className="flex-row justify-between items-center">
        <View style={{ minWidth: 0, flex: 1 }}>
          <Text
            className="text-lg font-semibold text-text-primary mb-1 min-w-0 break-all"
            numberOfLines={2}
            ellipsizeMode="tail"
            style={{ fontSize: Number(envelope.currentAmount) > 9999999 ? 14 : 16 }}
          >
            {formatCurrency(envelope.currentAmount, envelope.currency)}
          </Text>
          <Text className="text-sm text-text-secondary min-w-0 break-all">
            {t('envelopes.of')} {formatCurrency(envelope.targetedAmount, envelope.currency)}
          </Text>
        </View>
        
        <View className="w-16 h-16">
          <EnvelopePieChart 
            currentAmount={Number(envelope.currentAmount)}
            targetedAmount={Number(envelope.targetedAmount)}
          />
        </View>
      </View>
    </View>
  );
};

export default EnvelopeCard;