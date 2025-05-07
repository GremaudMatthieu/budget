import React from 'react';
import { View, Text, TextInput, TextInputProps } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';

interface AmountInputProps extends TextInputProps {
  label?: string;
  error?: string;
  currency?: string;
  className?: string;
}

const AmountInput: React.FC<AmountInputProps> = ({ 
  value, 
  onChangeText, 
  label,
  error,
  currency = '$',
  className,
  ...props 
}) => {
  // Handle amount input - only allow numbers and a single decimal point or comma
  const handleAmountChange = (text: string) => {
    // Allow digits, period, and comma
    const filtered = text.replace(/[^0-9.,]/g, '');
    // Replace commas with periods
    const normalized = filtered.replace(/,/g, '.');
    // Ensure only one decimal point
    const parts = normalized.split('.');
    let formatted = parts[0];
    if (parts.length > 1) {
      formatted += '.' + parts[1];
    }
    if (onChangeText) {
      onChangeText(formatted);
    }
  };

  return (
    <View className="mb-4">
      {label && (
        <Text className="mb-1 text-sm font-medium text-text-secondary">{label}</Text>
      )}
      
      <View className="relative flex-row items-center">
        <View className="absolute left-3 top-0 bottom-0 justify-center z-10">
          <Text className="text-text-secondary font-medium">{currency}</Text>
        </View>
        
        <TextInput
          value={normalizeAmountInput(value || '')}
          onChangeText={handleAmountChange}
          keyboardType="decimal-pad"
          className={`p-3 pl-8 rounded-xl border ${error ? 'border-danger-500' : 'border-surface-border'} bg-white text-text-primary ${className || ''}`}
          placeholder="0.00"
          placeholderTextColor="#9ca3af"
          {...props}
        />
      </View>
      
      {error && (
        <View className="flex-row items-center mt-1">
          <Ionicons name="alert-circle-outline" size={14} color="#ef4444" />
          <Text className="ml-1 text-xs text-danger-600">{error}</Text>
        </View>
      )}
    </View>
  );
};

export default AmountInput;