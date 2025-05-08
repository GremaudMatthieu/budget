import React from 'react';
import { View, Text, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export interface EnvelopeItemCardProps {
  name: string;
  amount: string;
  currency: string;
  onPress?: () => void;
  onDelete?: () => void;
  loading?: boolean;
}

/**
 * Reusable card for an envelope in the list
 */
const EnvelopeItemCard: React.FC<EnvelopeItemCardProps> = ({
  name,
  amount,
  currency,
  onPress,
  onDelete,
  loading = false,
}) => {
  return (
    <TouchableOpacity onPress={onPress} className="card mb-3" disabled={loading}>
      <View className="bg-white dark:bg-background-dark rounded-xl shadow-md p-4 mb-4">
        <View className="card-content flex-row items-center justify-between">
          <View className="flex-row items-center">
            <View className="w-10 h-10 rounded-full bg-blue-100 items-center justify-center mr-3">
              <Ionicons name="wallet-outline" size={20} color="#0284c7" />
            </View>
            <Text className="text-lg font-semibold text-text-primary dark:text-text-light mb-2" numberOfLines={1} ellipsizeMode="tail">
              {name}
            </Text>
          </View>
          <View className="flex-row items-center">
            <Text className="text-primary-600 font-bold mr-2">{amount} {currency}</Text>
            {onDelete && (
              <TouchableOpacity onPress={onDelete} className="p-2 bg-red-50 rounded-full" disabled={loading}>
                <Ionicons name="trash-outline" size={18} color="#dc2626" />
              </TouchableOpacity>
            )}
          </View>
        </View>
      </View>
    </TouchableOpacity>
  );
};

export default EnvelopeItemCard; 