import React from 'react';
import { TouchableOpacity, Text, ActivityIndicator, View, TouchableOpacityProps, Platform } from 'react-native';

interface ActionButtonProps extends TouchableOpacityProps {
  onPress: () => void;
  label: string;
  disabled?: boolean;
  className?: string;
  variant?: 'primary' | 'secondary' | 'danger';
  size?: 'sm' | 'md' | 'lg';
  fullWidth?: boolean;
}

const ActionButton: React.FC<ActionButtonProps> = ({
  onPress,
  label,
  disabled = false,
  className,
  variant = 'primary',
  size = 'md',
  fullWidth = true,
}) => {
  const isWeb = Platform.OS === 'web';
  
  let bgClass = '';
  let textClass = '';
  if (variant === 'primary') {
    bgClass = disabled ? 'bg-gray-300' : 'bg-primary-600 hover:bg-primary-700';
    textClass = disabled ? 'text-gray-400' : 'text-white';
  } else if (variant === 'secondary') {
    bgClass = disabled ? 'bg-gray-100 border border-gray-200' : 'bg-surface-light border border-surface-border hover:bg-gray-50';
    textClass = disabled ? 'text-gray-400' : 'text-primary-600';
  } else if (variant === 'danger') {
    bgClass = disabled ? 'bg-gray-300' : 'bg-danger-600 hover:bg-danger-700';
    textClass = disabled ? 'text-gray-400' : 'text-white';
  }

  // Size classes
  let sizeClass = '';
  let textSizeClass = '';
  if (size === 'sm') {
    sizeClass = 'py-2 px-4';
    textSizeClass = 'text-sm';
  } else if (size === 'lg') {
    sizeClass = 'py-4 px-8';
    textSizeClass = 'text-lg';
  } else {
    sizeClass = isWeb ? 'py-2.5 px-6' : 'py-3 px-6';
    textSizeClass = 'text-base';
  }

  // Width classes
  const widthClass = fullWidth ? (isWeb ? 'w-full sm:w-auto sm:flex-1' : 'flex-1') : '';

  if (isWeb) {
    return (
      <button
        disabled={disabled}
        onClick={onPress}
        className={`${widthClass} ${sizeClass} rounded-xl items-center justify-center font-medium transition-colors ${bgClass} ${textClass} ${textSizeClass} ${disabled ? 'cursor-not-allowed' : 'cursor-pointer'} ${className || ''}`}
      >
        {label}
      </button>
    );
  }

  return (
    <TouchableOpacity
      disabled={disabled}
      onPress={onPress}
      className={`${widthClass} ${sizeClass} rounded-xl items-center justify-center ${bgClass} ${className || ''}`}
    >
      <Text className={`font-medium ${textClass} ${textSizeClass}`}>{label}</Text>
    </TouchableOpacity>
  );
};
export default ActionButton;