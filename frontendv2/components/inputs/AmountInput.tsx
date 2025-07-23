import React from 'react';
import { View, Text, TextInput, TextInputProps, Platform } from 'react-native';
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
    // Filter out all non-numeric characters except '.'
    let input = text.replace(/[^0-9.]/g, '');
    const normalized = normalizeAmountInput(input);
    if (onChangeText) {
      onChangeText(normalized);
    }
  };

  const isWeb = Platform.OS === 'web';
  
  return (
    <View>
      {label && (
        <Text className={`${isWeb ? 'mb-2' : 'mb-1'} text-sm font-medium text-gray-700`}>{label}</Text>
      )}
      
      <View className={`
        flex-row items-center rounded-xl border bg-white overflow-hidden
        ${error ? 'border-danger-500' : 'border-surface-border'}
        ${isWeb ? 'focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500 transition-all' : ''}
      `}>
        <View className="px-3 py-3 bg-gray-50 border-r border-surface-border min-w-[50px] items-center">
          <Text className="font-medium text-text-secondary text-sm">{currency}</Text>
        </View>
        <TextInput
          value={normalizeAmountInput(value || '')}
          onChangeText={handleAmountChange}
          keyboardType="decimal-pad"
          className={`flex-1 p-3 text-text-primary ${className || ''}`}
          placeholder="0.00"
          placeholderTextColor="#9ca3af"
          {...props}
        />
      </View>
      
      {error && (
        <View className="flex-row items-center mt-2">
          <Ionicons name="alert-circle-outline" size={14} color="#ef4444" />
          <Text className="ml-1 text-xs text-danger-600">{error}</Text>
        </View>
      )}
    </View>
  );
};

export default AmountInput;