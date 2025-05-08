import React from 'react';
import { View, Text, TextInput, TouchableOpacity, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export interface AccountCardProps {
  label: string;
  value: string;
  icon: string;
  editable?: boolean;
  editing?: boolean;
  onEdit?: () => void;
  onCancel?: () => void;
  onChange?: (text: string) => void;
  onSubmit?: () => void;
  loading?: boolean;
}

/**
 * Reusable card for account settings fields (text fields, select fields, etc.)
 */
const AccountCard: React.FC<AccountCardProps> = ({
  label,
  value,
  icon,
  editable = false,
  editing = false,
  onEdit,
  onCancel,
  onChange,
  onSubmit,
  loading = false,
}) => {
  return (
    <View className="bg-white dark:bg-background-dark rounded-xl shadow-md p-4 mb-4">
      <View className="card-content">
        <View className="flex-row items-center">
          <View className="w-10 h-10 rounded-full bg-primary-100 items-center justify-center mr-3">
            <Ionicons name={icon as any} size={20} color="#0284c7" />
          </View>
          <View className="flex-1">
            <Text className="text-sm text-secondary-600 mb-1">{label}</Text>
            <View className="flex-row items-center">
              {editing ? (
                <View className="flex-1 flex-row items-center">
                  <TextInput
                    className="flex-1 border-b border-primary-300 py-1 text-text-primary"
                    value={value}
                    onChangeText={onChange}
                    autoFocus
                  />
                  <TouchableOpacity className="ml-2 p-2" onPress={onSubmit} disabled={loading}>
                    {loading ? (
                      <ActivityIndicator size="small" color="#16a34a" />
                    ) : (
                      <Ionicons name="checkmark" size={20} color="#16a34a" />
                    )}
                  </TouchableOpacity>
                  <TouchableOpacity className="p-2" onPress={onCancel} disabled={loading}>
                    <Ionicons name="close" size={20} color="#dc2626" />
                  </TouchableOpacity>
                </View>
              ) : (
                <>
                  <Text className="flex-1 text-text-primary font-medium">{value}</Text>
                  {editable && (
                    <TouchableOpacity className="p-2" onPress={onEdit} disabled={loading}>
                      {loading ? (
                        <ActivityIndicator size="small" color="#64748b" />
                      ) : (
                        <Ionicons name="create-outline" size={20} color="#64748b" />
                      )}
                    </TouchableOpacity>
                  )}
                </>
              )}
            </View>
          </View>
        </View>
      </View>
    </View>
  );
};

export default AccountCard; 