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
    <View className="p-2">
      <View className="flex-row justify-between items-center mb-2">
        {progress >= 1 ? (
          <View className="bg-success-50 px-1.5 py-0.5 rounded-full">
            <Text className="text-success-700 text-[10px] font-medium">{t('envelopes.completed')}</Text>
          </View>
        ) : progress > 0 ? (
          <View className="bg-primary-50 px-1.5 py-0.5 rounded-full">
            <Text className="text-primary-700 text-[10px] font-medium">{Math.round(progress * 100)}% {t('envelopes.filled')}</Text>
          </View>
        ) : (
          <View className="bg-secondary-50 px-1.5 py-0.5 rounded-full">
            <Text className="text-secondary-700 text-[10px] font-medium">{t('envelopes.notStarted')}</Text>
          </View>
        )}
        
        {envelope.pending && (
          <View className="bg-warning-50 px-1.5 py-0.5 rounded-full flex-row items-center">
            <Ionicons name="sync" size={10} color="#d97706" className="animate-spin mr-1" />
            <Text className="text-warning-700 text-[10px] font-medium">{t('common.pending')}</Text>
          </View>
        )}
      </View>
      
      <View className="flex-row justify-between items-center" style={{ alignItems: 'center' }}>
        <View style={{ minWidth: 0, flex: 1 }}>
          <Text
            className="text-base font-semibold text-text-primary mb-0.5 min-w-0 break-all"
            numberOfLines={2}
            ellipsizeMode="tail"
            style={{ fontSize: Number(envelope.currentAmount) > 9999999 ? 13 : 15 }}
          >
            {formatCurrency(envelope.currentAmount, envelope.currency)}
          </Text>
          <Text className="text-xs text-text-secondary min-w-0 break-all">
            {t('envelopes.of')} {formatCurrency(envelope.targetedAmount, envelope.currency)}
          </Text>
        </View>
        <View className="w-10 h-10 justify-center items-center" style={{ alignSelf: 'center' }}>
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