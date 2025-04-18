import React, { useState } from 'react';
import { View, Text, Modal, TextInput, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import ActionButton from '@/components/buttons/ActionButton';

interface DescriptionModalProps {
  visible: boolean;
  onClose: () => void;
  onSubmit: (description: string) => void;
  actionType: 'credit' | 'debit';
}

const DescriptionModal: React.FC<DescriptionModalProps> = ({
  visible,
  onClose,
  onSubmit,
  actionType
}) => {
  const [description, setDescription] = useState('');

  const handleSubmit = () => {
    onSubmit(description);
    setDescription('');
  };

  const handleClose = () => {
    setDescription('');
    onClose();
  };

  // Choose icon and colors based on action type
  const icon = actionType === 'credit'
    ? "arrow-down-circle-outline"
    : "arrow-up-circle-outline";

  const iconColor = actionType === 'credit'
    ? "#16a34a" // success-600
    : "#dc2626"; // danger-600

  const iconBgColor = actionType === 'credit'
    ? "bg-success-100"
    : "bg-danger-100";

  const actionLabel = actionType === 'credit'
    ? "Add Funds"
    : "Remove Funds";

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
    >
      <View className="flex-1 justify-center items-center bg-black/50">
        <View className="w-[90%] max-w-md bg-background-light p-6 rounded-2xl shadow-lg">
          <View className="flex-row justify-between items-center mb-5">
            <View className="flex-row items-center">
              <View className={`w-8 h-8 rounded-full ${iconBgColor} items-center justify-center mr-2`}>
                <Ionicons name={icon} size={18} color={iconColor} />
              </View>
              <Text className="text-xl font-bold text-text-primary">
                {actionType === 'credit' ? 'Add to Envelope' : 'Remove from Envelope'}
              </Text>
            </View>
            <TouchableOpacity
              onPress={handleClose}
              className="w-8 h-8 rounded-full bg-surface-subtle items-center justify-center"
              hitSlop={{ top: 10, right: 10, bottom: 10, left: 10 }}
            >
              <Ionicons name="close" size={20} color="#64748b" />
            </TouchableOpacity>
          </View>

          <View className="mb-5">
            <Text className="mb-2 text-sm font-medium text-text-secondary">
              Add a description for this transaction:
            </Text>

            <View className="relative">
              <View className="absolute left-3 top-3">
                <Ionicons name="create-outline" size={18} color="#64748b" />
              </View>

              <TextInput
                value={description}
                onChangeText={setDescription}
                placeholder="e.g., Monthly savings, Grocery shopping"
                placeholderTextColor="#9ca3af"
                className="p-3 pl-10 border border-surface-border rounded-xl bg-white text-text-primary"
                multiline
                numberOfLines={3}
                textAlignVertical="top"
                style={{ minHeight: 80 }}
              />
            </View>
          </View>

          <View className="flex-row space-x-3 mt-2">
            <ActionButton
              label={"Cancel"}
              onPress={handleClose}
              className="flex-1"
             
            />
            <ActionButton
              label={actionLabel}
              onPress={handleSubmit}
              className="flex-1"
            />
          </View>
        </View>
      </View>
    </Modal>
  );
};

export default DescriptionModal;