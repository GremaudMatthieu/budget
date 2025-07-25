import React from 'react';
import { View, Text, TextInput, TextInputProps, Platform } from 'react-native';
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
  const isWeb = Platform.OS === 'web';
  
  return (
    <View>
      {label && (
        <Text className={`${isWeb ? 'mb-2' : 'mb-1'} text-sm font-medium text-gray-700`}>{label}</Text>
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
          className={`
            w-full p-3 ${icon ? 'pl-10' : 'pl-4'} rounded-xl border bg-white text-text-primary
            ${error ? 'border-danger-500' : 'border-surface-border'}
            ${isWeb ? 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500 transition-all' : ''}
            ${className || ''}
          `}
          placeholder="Enter name"
          placeholderTextColor="#9ca3af"
          maxLength={25}
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

export default NameInput;