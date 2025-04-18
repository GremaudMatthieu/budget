import React from 'react';
import { TouchableOpacity, Text, ActivityIndicator, View, TouchableOpacityProps } from 'react-native';

interface ActionButtonProps extends TouchableOpacityProps {
  onPress: () => void;
  label: string;
  disabled?: boolean;
  className?: string;
}

const ActionButton: React.FC<ActionButtonProps> = ({
  onPress,
  label,
  disabled = false,
  className
}) => {
  // Determine styles based on variant



  return (
    <TouchableOpacity
      disabled={disabled}
      onPress={onPress}
      className="flex-1 py-3 rounded-xl bg-surface-light border border-surface-border items-center"
    >
      <Text className={`${className} text-text-primary`} font-medium>{label}</Text>
    </TouchableOpacity>
  );
};
export default ActionButton;