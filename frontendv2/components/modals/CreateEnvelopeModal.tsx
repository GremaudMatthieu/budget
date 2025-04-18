import React, { useState, useEffect } from 'react';
import { View, Text, Modal, TouchableOpacity, ScrollView } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Picker } from '@react-native-picker/picker';
import { currencyOptions } from '@/utils/currencyUtils';
import AmountInput from '@/components/inputs/AmountInput';
import NameInput from '@/components/inputs/NameInput';
import ActionButton from '@/components/buttons/ActionButton';

interface CreateEnvelopeModalProps {
  visible: boolean;
  onClose: () => void;
  onSubmit: (name: string, targetAmount: string, currency: string) => void;
}

const CreateEnvelopeModal: React.FC<CreateEnvelopeModalProps> = ({
  visible,
  onClose,
  onSubmit
}) => {
  const [name, setName] = useState('');
  const [targetAmount, setTargetAmount] = useState('');
  const [currency, setCurrency] = useState('USD');
  const [error, setError] = useState<string | null>(null);
  const [submitted, setSubmitted] = useState(false);

  // Clear form when modal becomes visible
  useEffect(() => {
    if (visible) {
      resetForm();
    }
  }, [visible]);

  // Auto-close after submission with a delay (as a fallback)
  useEffect(() => {
    if (submitted) {
      const timer = setTimeout(() => {
        setSubmitted(false);
        onClose();
      }, 1000); // Close after 1 second if websocket doesn't respond
      return () => clearTimeout(timer);
    }
  }, [submitted, onClose]);

  const handleSubmit = () => {
    if (!name.trim()) {
      setError('Please enter a name for your envelope');
      return;
    }

    if (name.length > 25) {
      setError('Name cannot exceed 25 characters');
      return;
    }

    if (!targetAmount || Number(targetAmount) <= 0) {
      setError('Please enter a valid target amount');
      return;
    }

    setSubmitted(true);
    onSubmit(name, targetAmount, currency);
  };

  const resetForm = () => {
    setName('');
    setTargetAmount('');
    setCurrency('USD');
    setError(null);
    setSubmitted(false);
  };

  const handleClose = () => {
    resetForm();
    onClose();
  };

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
              <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-2">
                <Ionicons name="wallet-outline" size={18} color="#0284c7" />
              </View>
              <Text className="text-xl font-bold text-text-primary">
                New Envelope
              </Text>
            </View>
            <TouchableOpacity
              onPress={handleClose}
              className="w-8 h-8 rounded-full bg-surface-subtle items-center justify-center"
              disabled={submitted}
              hitSlop={{ top: 10, right: 10, bottom: 10, left: 10 }}
            >
              <Ionicons name="close" size={20} color="#64748b" />
            </TouchableOpacity>
          </View>

          <ScrollView className="max-h-[400px]">
            {error && (
              <View className="bg-danger-50 p-3 rounded-xl mb-4 border border-danger-200 flex-row items-center">
                <Ionicons name="alert-circle" size={18} color="#dc2626" />
                <Text className="text-danger-700 ml-2 flex-1">{error}</Text>
              </View>
            )}

            <NameInput
              label="Envelope Name"
              value={name}
              onChangeText={setName}
              placeholder="e.g., Vacation, Emergency Fund"
              icon={<Ionicons name="bookmark-outline" size={18} color="#64748b" />}
              editable={!submitted}
            />

            <AmountInput
              label="Target Amount"
              value={targetAmount}
              onChangeText={setTargetAmount}
              currency="$"
              editable={!submitted}
            />

            <View className="mb-5">
              <Text className="mb-1 text-sm font-medium text-text-secondary">Currency</Text>
              <View className="border border-surface-border rounded-xl overflow-hidden bg-white">
                <Picker
                  selectedValue={currency}
                  onValueChange={(value) => setCurrency(value)}
                  style={{ color: '#334155' }}
                  enabled={!submitted}
                >
                  {currencyOptions.map((option) => (
                    <Picker.Item
                      key={option.value}
                      label={option.label}
                      value={option.value}
                    />
                  ))}
                </Picker>
              </View>
            </View>
          </ScrollView>

          <View className="flex-row space-x-3 mt-2">
            <ActionButton
              label={"Cancel"}
              onPress={handleClose}
              disabled={submitted}
              className="flex-1"
            />
            <ActionButton
              label={submitted ? "Creating..." : "Create Envelope"}
              onPress={handleSubmit}
              disabled={submitted}
              className="flex-1 "
            />
          </View>
        </View>
      </View>
    </Modal>
  );
};

export default CreateEnvelopeModal;