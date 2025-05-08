import React from 'react';
import { View, Text, TextInput, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';

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
}) => {
  const { t } = useTranslation();
  return (
    <View className="bg-white rounded-xl shadow-md p-4 mb-4">
      {/* Name editing */}
      <View className="flex-row items-center mb-2">
        {editingName !== null ? (
          <>
            <TextInput
              value={editingName}
              onChangeText={setEditingName}
              className="flex-1 bg-gray-100 px-3 py-2 rounded-lg text-lg font-semibold"
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
          <>
            <Text className="text-lg font-semibold text-text-primary mb-2">{name}</Text>
            <TouchableOpacity onPress={() => setEditingName(name)} disabled={pending} className="ml-2">
              <Ionicons name="create-outline" size={20} color="#222" />
            </TouchableOpacity>
          </>
        )}
      </View>
      {/* Progress bar and target editing */}
      <View className="flex-row items-center mt-2">
        <Text className="text-gray-500 mr-2">{currentAmount} {t('envelopes.of')} </Text>
        {editingTarget !== null ? (
          <>
            <TextInput
              value={editingTarget}
              onChangeText={setEditingTarget}
              className="w-20 bg-gray-100 px-2 py-1 rounded-lg text-lg"
              keyboardType="numeric"
              maxLength={10}
              autoFocus
              editable={!pending}
            />
            <TouchableOpacity onPress={onUpdateTarget} className="ml-1 p-1 bg-gray-200 rounded-full">
              <Ionicons name="checkmark" size={16} color="#222" />
            </TouchableOpacity>
            <TouchableOpacity onPress={() => setEditingTarget(null)} className="ml-1 p-1 bg-gray-200 rounded-full">
              <Ionicons name="close" size={16} color="#222" />
            </TouchableOpacity>
          </>
        ) : (
          <TouchableOpacity onPress={() => setEditingTarget(String(targetedAmount))} disabled={pending} className="flex-row items-center">
            <Text className="text-lg font-medium text-gray-700">{targetedAmount} {currency}</Text>
            <Ionicons name="create-outline" size={16} color="#222" className="ml-1" />
          </TouchableOpacity>
        )}
        <Text className="ml-2 text-gray-500">({Math.round(progress * 100)}% {t('envelopes.progress')})</Text>
      </View>
      {/* Progress bar visual */}
      <View className="w-full h-3 bg-gray-200 rounded-full mt-4">
        <View style={{ width: `${Math.min(progress * 100, 100)}%` }} className="h-3 bg-indigo-500 rounded-full" />
      </View>
    </View>
  );
};

export default EnvelopeProgressCard; 