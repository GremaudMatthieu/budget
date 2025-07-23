import React, { forwardRef } from 'react';
import { View, Text, TextInput, TextInputProps, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

interface ResponsiveInputProps extends Omit<TextInputProps, 'className'> {
  label?: string;
  error?: string;
  icon?: React.ReactNode;
  className?: string;
  containerClassName?: string;
}

const ResponsiveInput = forwardRef<TextInput, ResponsiveInputProps>(({
  label,
  error,
  icon,
  className = '',
  containerClassName = '',
  ...props
}, ref) => {
  const isWeb = Platform.OS === 'web';

  return (
    <View className={containerClassName}>
      {label && (
        <Text className={`${isWeb ? 'mb-2' : 'mb-1'} text-sm font-medium text-gray-700`}>
          {label}
        </Text>
      )}
      
      <View className="relative">
        {icon && (
          <View className="absolute left-3 top-3 z-10">
            {icon}
          </View>
        )}
        
        <TextInput
          ref={ref}
          {...props}
          className={`
            w-full p-3 ${icon ? 'pl-10' : ''} rounded-xl border bg-white text-text-primary
            ${error ? 'border-danger-500' : 'border-surface-border'}
            ${isWeb ? 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500 transition-all' : ''}
            ${className}
          `}
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
});

export default ResponsiveInput;