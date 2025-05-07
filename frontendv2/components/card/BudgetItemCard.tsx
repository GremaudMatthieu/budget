import React from 'react';
import { View, Text, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export interface BudgetItemCardProps {
  name: string;
  amount: string;
  category: string;
  icon: string;
  color: string;
  bgColor: string;
  textColor: string;
  currency?: string;
  onEdit?: () => void;
  onDelete?: () => void;
  loading?: boolean;
}

/**
 * Reusable card for a budget item (need, want, saving, income)
 */
const BudgetItemCard: React.FC<BudgetItemCardProps> = ({
  name,
  amount,
  category,
  icon,
  color,
  bgColor,
  textColor,
  currency,
  onEdit,
  onDelete,
  loading = false,
}) => {
  return (
    <View className="neomorphic-inset p-3 rounded-lg mb-2">
      <View className="flex-row justify-between items-center">
        <View className="flex-row items-center flex-1">
          <View className={`w-8 h-8 rounded-full ${bgColor} items-center justify-center mr-2`}>
            <Ionicons name={icon as any} size={18} color={color} />
          </View>
          <View style={{ flex: 1 }}>
            <Text className="font-medium text-text-primary" numberOfLines={1} ellipsizeMode="tail">
              {name}
            </Text>
            <Text className="text-xs text-text-secondary" numberOfLines={1} ellipsizeMode="tail">
              {category}
            </Text>
          </View>
        </View>
        <Text className="font-semibold mr-4 text-primary-600" numberOfLines={1} ellipsizeMode="tail" style={{ maxWidth: 100 }}>
          {amount} {currency}
        </Text>
        {onEdit && (
          <TouchableOpacity onPress={onEdit} className="p-2 bg-gray-100 rounded-full mr-1" disabled={loading}>
            <Ionicons name="create-outline" size={16} color="#64748b" />
          </TouchableOpacity>
        )}
        {onDelete && (
          <TouchableOpacity onPress={onDelete} className="p-2 bg-red-50 rounded-full" disabled={loading}>
            <Ionicons name="trash-outline" size={16} color="#dc2626" />
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
};

export default BudgetItemCard; 