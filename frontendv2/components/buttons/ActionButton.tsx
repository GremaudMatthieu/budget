import React from 'react';
import { TouchableOpacity, Text, ActivityIndicator, View, TouchableOpacityProps } from 'react-native';

interface ActionButtonProps extends TouchableOpacityProps {
  onPress: () => void;
  label: string;
  disabled?: boolean;
  className?: string;
  variant?: 'primary' | 'secondary' | 'danger';
}

const ActionButton: React.FC<ActionButtonProps> = ({
  onPress,
  label,
  disabled = false,
  className,
  variant = 'primary',
}) => {
  let bgClass = '';
  let textClass = '';
  if (variant === 'primary') {
    bgClass = disabled ? 'bg-gray-300' : 'bg-primary-600';
    textClass = disabled ? 'text-gray-400' : 'text-white';
  } else if (variant === 'secondary') {
    bgClass = disabled ? 'bg-gray-100 border border-gray-200' : 'bg-surface-light border border-surface-border';
    textClass = disabled ? 'text-gray-400' : 'text-primary-600';
  } else if (variant === 'danger') {
    bgClass = disabled ? 'bg-gray-300' : 'bg-danger-600';
    textClass = disabled ? 'text-gray-400' : 'text-white';
  }

  return (
    <TouchableOpacity
      disabled={disabled}
      onPress={onPress}
      className={`flex-1 py-3 rounded-xl items-center justify-center ${bgClass} ${className || ''}`}
    >
      <Text className={`font-medium ${textClass}`}>{label}</Text>
    </TouchableOpacity>
  );
};
export default ActionButton;