import React from 'react';
import { View, Text, Platform } from 'react-native';

interface ResponsiveFormProps {
  children: React.ReactNode;
  className?: string;
}

interface ResponsiveFormFieldProps {
  children: React.ReactNode;
  className?: string;
}

interface ResponsiveFormActionsProps {
  children: React.ReactNode;
  className?: string;
}

export const ResponsiveForm: React.FC<ResponsiveFormProps> = ({ 
  children, 
  className = '' 
}) => {
  const isWeb = Platform.OS === 'web';
  
  return (
    <View className={`${isWeb ? 'space-y-6' : 'space-y-4'} ${className}`}>
      {children}
    </View>
  );
};

export const ResponsiveFormField: React.FC<ResponsiveFormFieldProps> = ({ 
  children, 
  className = '' 
}) => {
  const isWeb = Platform.OS === 'web';
  
  return (
    <View className={`${isWeb ? 'mb-6' : 'mb-4'} ${className}`}>
      {children}
    </View>
  );
};

export const ResponsiveFormActions: React.FC<ResponsiveFormActionsProps> = ({ 
  children, 
  className = '' 
}) => {
  const isWeb = Platform.OS === 'web';
  
  if (isWeb) {
    return (
      <div className={`flex flex-col sm:flex-row gap-3 sm:gap-4 pt-6 border-t border-gray-100 ${className}`}>
        {children}
      </div>
    );
  }
  
  return (
    <View className={`flex-row space-x-3 mt-6 ${className}`}>
      {children}
    </View>
  );
};

export default ResponsiveForm;