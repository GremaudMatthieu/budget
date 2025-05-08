import React from 'react';
import { View, Text } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';

export interface EnvelopeTransactionHistoryCardProps {
  ledger: Array<{
    entry_type: 'credit' | 'debit';
    monetary_amount: number;
    description?: string;
    created_at: string;
  }>;
  currency: string;
  loading?: boolean;
}

/**
 * Card for displaying envelope transaction history.
 */
const EnvelopeTransactionHistoryCard: React.FC<EnvelopeTransactionHistoryCardProps> = ({
  ledger,
  currency,
  loading = false,
}) => {
  const { t } = useTranslation();
  return (
    <View className="bg-white rounded-xl shadow-md p-4 mb-4">
      <Text className="text-lg font-semibold text-text-primary mb-2">{t('envelopes.transactionHistory')}</Text>
      {loading ? (
        <Text>{t('common.loading')}</Text>
      ) : ledger.length === 0 ? (
        <View className="py-4 items-center">
          <View className="w-16 h-16 rounded-full bg-gray-100 items-center justify-center mb-2">
            <Ionicons name="document-text-outline" size={24} color="#64748b" />
          </View>
          <Text className="text-gray-500 text-center">{t('envelopes.noTransactions')}</Text>
        </View>
      ) : (
        <View className="space-y-3">
          {ledger.map((transaction, index) => (
            <View
              key={index}
              className="p-3 bg-gray-50 rounded-lg flex-row justify-between items-center"
            >
              <View className="flex-row items-center">
                <View className={`w-10 h-10 rounded-full items-center justify-center mr-3 ${transaction.entry_type === 'credit' ? 'bg-green-100' : 'bg-red-100'}`}> 
                  <Ionicons
                    name={transaction.entry_type === 'credit' ? 'arrow-down' : 'arrow-up'}
                    size={18}
                    color={transaction.entry_type === 'credit' ? '#16a34a' : '#dc2626'}
                  />
                </View>
                <View>
                  <Text className="font-medium text-gray-900">
                    {transaction.description || (transaction.entry_type === 'credit' ? t('envelopes.addedFunds') : t('envelopes.spentFunds'))}
                  </Text>
                  <Text className="text-sm text-gray-500">
                    {new Date(transaction.created_at).toLocaleDateString()}
                  </Text>
                </View>
              </View>
              <Text className={transaction.entry_type === 'credit' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'}>
                {transaction.entry_type === 'credit' ? '+' : '-'}{transaction.monetary_amount} {currency}
              </Text>
            </View>
          ))}
        </View>
      )}
    </View>
  );
};

export default EnvelopeTransactionHistoryCard; 