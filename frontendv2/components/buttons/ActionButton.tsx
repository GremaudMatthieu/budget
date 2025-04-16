import React from 'react';
import { TouchableOpacity, Text, ActivityIndicator, View, TouchableOpacityProps } from 'react-native';

interface ActionButtonProps extends TouchableOpacityProps {
  label: string;
  variant?: 'primary' | 'secondary' | 'outline' | 'danger';
  size?: 'sm' | 'md' | 'lg';
  loading?: boolean;
  icon?: React.ReactNode;
  iconPosition?: 'left' | 'right';
}

const ActionButton: React.FC<ActionButtonProps> = ({ 
  label, 
  variant = 'primary',
  size = 'md',
  loading = false,
  icon,
  iconPosition = 'left',
  ...props 
}) => {
  // Determine styles based on variant
  let buttonStyles = '';
  let textStyles = '';
  
  // Variant styling
  switch(variant) {
    case 'primary':
      buttonStyles = 'bg-primary-600 shadow-sm';
      textStyles = 'text-white font-semibold';
      break;
    case 'secondary':
      buttonStyles = 'bg-secondary-600 shadow-sm';
      textStyles = 'text-white font-semibold';
      break;
    case 'outline':
      buttonStyles = 'bg-transparent border border-primary-600';
      textStyles = 'text-primary-600 font-medium';
      break;
    case 'danger':
      buttonStyles = 'bg-danger-600 shadow-sm';
      textStyles = 'text-white font-semibold';
      break;
    default:
      buttonStyles = 'bg-primary-600 shadow-sm';
      textStyles = 'text-white font-semibold';
  }
  
  // Size styling
  let sizeStyles = '';
  let textSizeStyles = '';
  
  switch(size) {
    case 'sm':
      sizeStyles = 'px-3 py-2';
      textSizeStyles = 'text-sm';
      break;
    case 'lg':
      sizeStyles = 'px-6 py-4';
      textSizeStyles = 'text-lg';
      break;
    default:
      sizeStyles = 'px-4 py-3';
      textSizeStyles = 'text-base';
  }
  
  // Disabled state
  if (props.disabled) {
    buttonStyles += ' opacity-50';
  }
  
  return (
    <TouchableOpacity
      className={`rounded-xl ${buttonStyles} ${sizeStyles}`}
      activeOpacity={0.7}
      disabled={loading || props.disabled}
      {...props}
    >
      <View className="flex-row items-center justify-center">
        {loading ? (
          <ActivityIndicator 
            size="small" 
            color={variant === 'outline' ? '#0284c7' : '#ffffff'} 
            className="mr-2" 
          />
        ) : icon && iconPosition === 'left' ? (
          <View className="mr-2">{icon}</View>
        ) : null}
        
        <Text className={`${textStyles} ${textSizeStyles} text-center`}>
          {loading ? 'Please wait...' : label}
        </Text>
        
        {!loading && icon && iconPosition === 'right' && (
          <View className="ml-2">{icon}</View>
        )}
      </View>
    </TouchableOpacity>
  );
};

export default ActionButton;