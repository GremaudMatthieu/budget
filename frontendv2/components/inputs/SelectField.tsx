import React, { useState } from 'react';
import { View, Text, TouchableOpacity, TextInputProps } from 'react-native';
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
  ...props
}) => {
  const [modalVisible, setModalVisible] = useState(false);
  const { t } = useTranslation();
  
  // Find the selected option to display its name
  const selectedOption = options.find(option => option.id === value);
  
  return (
    <View className="mb-4">
      {label && (
        <Text className="mb-1 text-sm font-medium text-text-secondary">{label}</Text>
      )}
      
      <TouchableOpacity 
        onPress={() => !disabled && setModalVisible(true)}
        className={`border rounded-xl p-3 bg-white flex-row justify-between items-center ${
          error ? 'border-danger-500' : 'border-surface-border'
        } ${disabled ? 'opacity-50' : ''} ${className || ''}`}
        disabled={disabled}
      >
        <View className="flex-row items-center flex-1">
          {icon && (
            <View className="mr-3">
              {icon}
            </View>
          )}
          <Text 
            className={selectedOption ? 'text-text-primary' : 'text-gray-400'}
            numberOfLines={1}
          >
            {selectedOption ? selectedOption.name : placeholder}
          </Text>
        </View>
        <Ionicons name="chevron-down" size={18} color="#64748b" />
      </TouchableOpacity>
      
      {error && (
        <View className="flex-row items-center mt-1">
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