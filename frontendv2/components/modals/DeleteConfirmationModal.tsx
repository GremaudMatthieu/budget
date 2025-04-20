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
    <Modal
      animationType="fade"
      transparent={true}
      visible={visible}
      onRequestClose={onClose}
    >
      <View className="flex-1 justify-center items-center bg-black/50 px-6">
        <View className="bg-white rounded-2xl w-full p-6">
          <View className="items-center mb-4">
            <View className="w-16 h-16 rounded-full bg-danger-100 items-center justify-center mb-4">
              <Ionicons name="alert-outline" size={32} color="#dc2626" />
            </View>
            
            <Text className="text-xl font-bold text-text-primary">{t('modals.confirmDeletion')}</Text>
          </View>
          
          <Text className="text-center text-text-secondary mb-6">
            {message || t('modals.deleteConfirmation', { name })}
          </Text>
          
          <View className="space-y-3">
            <TouchableOpacity
              onPress={onConfirm}
              className="py-3 bg-danger-600 rounded-xl"
            >
              <Text className="text-white text-center font-semibold">{t('common.delete')}</Text>
            </TouchableOpacity>
            
            <TouchableOpacity
              onPress={onClose}
              className="py-3 border border-gray-300 rounded-xl"
            >
              <Text className="text-text-primary text-center">{t('common.cancel')}</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

export default DeleteConfirmationModal;