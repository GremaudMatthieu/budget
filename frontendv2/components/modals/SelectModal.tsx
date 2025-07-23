import React from 'react';
import { 
  View, 
  Text, 
  TouchableOpacity, 
  Modal, 
  ScrollView, 
  TouchableWithoutFeedback,
  Platform
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import ResponsiveModal from './ResponsiveModal';

export interface SelectOption {
  id: string;
  name: string;
  icon?: string;
  iconColor?: string;
}

interface SelectModalProps {
  visible: boolean;
  onClose: () => void;
  onSelect: (option: SelectOption) => void;
  title: string;
  options: SelectOption[];
  selectedId?: string;
  showAddOption?: boolean;
  onAddNew?: () => void;
}

const SelectModal: React.FC<SelectModalProps> = ({
  visible,
  onClose,
  onSelect,
  title,
  options,
  selectedId,
  showAddOption = false,
  onAddNew
}) => {
  const { t } = useTranslation();
  
  return (
    <ResponsiveModal
      visible={visible}
      onClose={onClose}
      title={title}
      size="sm"
      scrollable={false}
    >
      <div className={Platform.OS === 'web' ? 'max-h-96 overflow-y-auto' : ''}>
        <ScrollView className={Platform.OS === 'web' ? '' : 'max-h-[400px]'}>
          {options.map((option) => (
            <TouchableOpacity
              key={option.id}
              onPress={() => {
                onSelect(option);
                onClose();
              }}
              className={`
                p-4 border-b border-gray-100 flex-row items-center
                ${selectedId === option.id ? 'bg-primary-50' : ''}
                ${Platform.OS === 'web' ? 'hover:bg-gray-50 cursor-pointer transition-colors' : ''}
              `}
            >
              {option.icon && (
                <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
                  <Ionicons 
                    name={option.icon as any} 
                    size={16} 
                    color={option.iconColor || "#0c6cf2"} 
                  />
                </View>
              )}
              <Text className={`${
                selectedId === option.id ? 'text-primary-600 font-medium' : 'text-text-primary'
              }`}>
                {option.name}
              </Text>
            </TouchableOpacity>
          ))}
          
          {showAddOption && onAddNew && (
            <TouchableOpacity
              onPress={onAddNew}
              className={`
                p-4 flex-row items-center
                ${Platform.OS === 'web' ? 'hover:bg-gray-50 cursor-pointer transition-colors' : ''}
              `}
            >
              <Ionicons name="add-circle-outline" size={18} color="#0c6cf2" />
              <Text className="text-primary-600 ml-2">{t('modals.addNew')}</Text>
            </TouchableOpacity>
          )}
        </ScrollView>
      </div>
    </ResponsiveModal>
  );
};

export default SelectModal;