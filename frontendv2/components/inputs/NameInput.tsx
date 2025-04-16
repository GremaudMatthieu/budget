import React from 'react';
import { View, Text, TextInput, TextInputProps } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

interface NameInputProps extends TextInputProps {
  label?: string;
  error?: string;
  icon?: React.ReactNode;
  className?: string;
}

const NameInput: React.FC<NameInputProps> = ({ 
  value, 
  onChangeText, 
  label,
  error,
  icon,
  className,
  ...props 
}) => {
  return (
    <View className="mb-4">
      {label && (
        <Text className="mb-1 text-sm font-medium text-text-secondary">{label}</Text>
      )}
      
      <View className="relative flex-row items-center">
        {icon && (
          <View className="absolute left-3 top-0 bottom-0 justify-center z-10">
            {icon}
          </View>
        )}
        
        <TextInput
          value={value}
          onChangeText={onChangeText}
          className={`p-3 ${icon ? 'pl-10' : 'pl-4'} rounded-xl border ${error ? 'border-danger-500' : 'border-surface-border'} bg-white text-text-primary ${className || ''}`}
          placeholder="Enter name"
          placeholderTextColor="#9ca3af"
          maxLength={25}
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

export default NameInput;