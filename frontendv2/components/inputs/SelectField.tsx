import React, { useState } from 'react';
import { View, Text, TouchableOpacity, TextInputProps, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import SelectModal, { SelectOption } from '../modals/SelectModal';
import { useTranslation } from '@/utils/useTranslation';

interface SelectFieldProps extends Omit<TextInputProps, 'value' | 'onChangeText'> {
  label?: string;
  error?: string;
  placeholder?: string;
  options: SelectOption[];
  value?: string;
  onChange: (value: string) => void;
  icon?: React.ReactNode;
  className?: string;
  disabled?: boolean;
  showAddOption?: boolean;
  onAddNew?: () => void;
  noMargin?: boolean; // Pour désactiver les marges quand utilisé dans ResponsiveFormField
}

const SelectField: React.FC<SelectFieldProps> = ({
  label,
  error,
  placeholder = 'Select an option',
  options,
  value,
  onChange,
  icon,
  className,
  disabled = false,
  showAddOption = false,
  onAddNew,
  noMargin = false,
  ...props
}) => {
  const [modalVisible, setModalVisible] = useState(false);
  const { t } = useTranslation();
  
  // Find the selected option to display its name
  const selectedOption = options.find(option => option.id === value);
  
  const isWeb = Platform.OS === 'web';

  return (
    <View className={noMargin ? '' : `${isWeb ? 'mb-6' : 'mb-4'}`}>
      {label && (
        <Text className={`${isWeb ? 'mb-2' : 'mb-1'} text-sm font-medium text-gray-700`}>
          {label}
        </Text>
      )}
      
      <TouchableOpacity 
        onPress={() => !disabled && setModalVisible(true)}
        className={`
          border rounded-xl p-3 bg-white flex-row justify-between items-center
          ${error ? 'border-danger-500' : 'border-surface-border'}
          ${disabled ? 'opacity-50' : ''}
          ${isWeb ? 'hover:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all cursor-pointer' : ''}
          ${className || ''}
        `}
        disabled={disabled}
      >
        <View className="flex-row items-center flex-1">
          {icon && (
            <View className="mr-3">
              {icon}
            </View>
          )}
          <Text 
            className={`${selectedOption ? 'text-text-primary' : 'text-gray-400'} ${isWeb ? 'select-none' : ''}`}
            numberOfLines={1}
          >
            {selectedOption ? selectedOption.name : placeholder}
          </Text>
        </View>
        <Ionicons 
          name="chevron-down" 
          size={18} 
          color="#64748b"
          style={isWeb ? { pointerEvents: 'none' } : {}}
        />
      </TouchableOpacity>
      
      {error && (
        <View className="flex-row items-center mt-2">
          <Ionicons name="alert-circle-outline" size={14} color="#ef4444" />
          <Text className="ml-1 text-xs text-danger-600">{error}</Text>
        </View>
      )}
      
      <SelectModal
        visible={modalVisible}
        onClose={() => setModalVisible(false)}
        onSelect={(option) => onChange(option.id)}
        title={label || t('modals.selectOption', 'Select Option')}
        options={options}
        selectedId={value}
        showAddOption={showAddOption}
        onAddNew={onAddNew}
      />
    </View>
  );
};

export default SelectField;