import React from 'react';
import { View, Text, TextInput, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';

export interface EnvelopeProgressCardProps {
  name: string;
  currentAmount: number;
  targetedAmount: number;
  currency: string;
  progress: number;
  editingName: string | null;
  setEditingName: (name: string | null) => void;
  editingTarget: string | null;
  setEditingTarget: (target: string | null) => void;
  onUpdateName: () => void;
  onUpdateTarget: () => void;
  pending: boolean;
  editingTargetError?: string;
}

/**
 * Card for displaying and editing envelope progress, name, and target.
 */
const EnvelopeProgressCard: React.FC<EnvelopeProgressCardProps> = ({
  name,
  currentAmount,
  targetedAmount,
  currency,
  progress,
  editingName,
  setEditingName,
  editingTarget,
  setEditingTarget,
  onUpdateName,
  onUpdateTarget,
  pending,
  editingTargetError,
}) => {
  const { t } = useTranslation();
  return (
    <View className="bg-white rounded-xl shadow-md p-4 mb-4">
      {/* Name editing */}
      <View className="flex-row items-center mb-2 min-w-0">
        {editingName !== null ? (
          <>
            <TextInput
              value={editingName}
              onChangeText={setEditingName}
              className="flex-1 bg-gray-100 px-3 py-2 rounded-lg text-lg font-semibold min-w-0 break-all"
              maxLength={25}
              autoFocus
              editable={!pending}
            />
            <TouchableOpacity onPress={onUpdateName} className="ml-2 p-2 bg-gray-200 rounded-full">
              <Ionicons name="checkmark" size={20} color="#222" />
            </TouchableOpacity>
            <TouchableOpacity onPress={() => setEditingName(null)} className="ml-2 p-2 bg-gray-200 rounded-full">
              <Ionicons name="close" size={20} color="#222" />
            </TouchableOpacity>
          </>
        ) : (
          <View className="flex-row items-center flex-1 min-w-0">
            <Text className="text-lg font-semibold text-text-primary mb-2 min-w-0 flex-shrink" numberOfLines={2} ellipsizeMode="tail" style={{ flex: 1 }}>
              {name}
            </Text>
            <TouchableOpacity onPress={() => setEditingName(name)} disabled={pending} className="ml-2">
              <Ionicons name="create-outline" size={20} color="#222" />
            </TouchableOpacity>
          </View>
        )}
      </View>
      {/* Progress bar and target editing */}
      <View className="flex-row items-center mt-2 min-w-0">
        <Text className="text-gray-500 mr-2 min-w-0 break-all" style={{ fontSize: currentAmount > 9999999 ? 14 : 16 }}>{currentAmount} {t('envelopes.of')}</Text>
        {editingTarget !== null ? (
          <>
            <TextInput
              value={editingTarget}
              onChangeText={v => {
                // Use shared normalization for consistency
                let input = v.replace(/[^0-9.]/g, '');
                const normalized = normalizeAmountInput(input);
                setEditingTarget(normalized);
              }}
              className="w-20 bg-gray-100 px-2 py-1 rounded-lg text-lg min-w-0 break-all"
              keyboardType="numeric"
              maxLength={13}
              autoFocus
              editable={!pending}
            />
            {/* Show error if present */}
            {typeof editingTargetError === 'string' && editingTargetError && (
              <Text className="text-red-500 text-xs mt-1 ml-1">{editingTargetError}</Text>
            )}
            <TouchableOpacity onPress={onUpdateTarget} className="ml-1 p-1 bg-gray-200 rounded-full">
              <Ionicons name="checkmark" size={16} color="#222" />
            </TouchableOpacity>
            <TouchableOpacity onPress={() => setEditingTarget(null)} className="ml-1 p-1 bg-gray-200 rounded-full">
              <Ionicons name="close" size={16} color="#222" />
            </TouchableOpacity>
          </>
        ) : (
          <TouchableOpacity onPress={() => setEditingTarget(String(targetedAmount))} disabled={pending} className="flex-row items-center">
            <Text className="text-lg font-medium text-gray-700 min-w-0 break-all" style={{ fontSize: targetedAmount > 9999999 ? 14 : 16 }}>{targetedAmount} {currency}</Text>
            <Ionicons name="create-outline" size={16} color="#222" className="ml-1" />
          </TouchableOpacity>
        )}
        <Text className="ml-2 text-gray-500 min-w-0 break-all">({Math.round(progress * 100)}%)</Text>
      </View>
      {/* Progress bar visual */}
      <View className="w-full h-3 bg-gray-200 rounded-full mt-4">
        <View style={{ width: `${Math.min(progress * 100, 100)}%` }} className="h-3 bg-indigo-500 rounded-full" />
      </View>
    </View>
  );
};

export default EnvelopeProgressCard; 