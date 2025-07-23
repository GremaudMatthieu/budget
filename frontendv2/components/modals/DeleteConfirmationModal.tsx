import React from 'react';
import { 
  View, 
  Text, 
  TouchableOpacity, 
  Modal, 
  TouchableWithoutFeedback 
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import ResponsiveModal from './ResponsiveModal';
import { ResponsiveFormActions } from '@/components/forms/ResponsiveForm';
import ActionButton from '@/components/buttons/ActionButton';

interface DeleteConfirmationModalProps {
  visible: boolean;
  onClose: () => void;
  onConfirm: () => void;
  name: string;
  message?: string;
}

const DeleteConfirmationModal: React.FC<DeleteConfirmationModalProps> = ({
  visible,
  onClose,
  onConfirm,
  name,
  message
}) => {
  const { t } = useTranslation();

  return (
    <ResponsiveModal
      visible={visible}
      onClose={onClose}
      title={t('modals.confirmDeletion')}
      size="sm"
      scrollable={false}
    >
      <View className="text-center space-y-6">
        <View className="items-center">
          <View className="w-16 h-16 rounded-full bg-danger-100 items-center justify-center mb-4">
            <Ionicons name="alert-outline" size={32} color="#dc2626" />
          </View>
        </View>
        
        <Text className="text-center text-gray-600 text-base">
          {message || t('modals.deleteConfirmation', { name })}
        </Text>

        <ResponsiveFormActions>
          <ActionButton
            label={t('common.cancel')}
            onPress={onClose}
            variant="secondary"
            size="md"
          />
          <ActionButton
            label={t('common.delete')}
            onPress={onConfirm}
            variant="danger"
            size="md"
          />
        </ResponsiveFormActions>
      </View>
    </ResponsiveModal>
  );
};

export default DeleteConfirmationModal;