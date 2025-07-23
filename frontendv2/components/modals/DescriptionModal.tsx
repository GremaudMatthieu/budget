import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import ResponsiveModal from './ResponsiveModal';
import { ResponsiveFormField, ResponsiveFormActions } from '@/components/forms/ResponsiveForm';
import ActionButton from '@/components/buttons/ActionButton';

export interface DescriptionModalProps {
  visible: boolean;
  onClose: () => void;
  onSubmit: (description: string) => void;
  actionType: 'credit' | 'debit';
  loading?: boolean;
}

/**
 * Modal for entering a description before credit/debit.
 */
const DescriptionModal: React.FC<DescriptionModalProps> = ({
  visible,
  onClose,
  onSubmit,
  actionType,
  loading = false,
}) => {
  const { t } = useTranslation();
  const [description, setDescription] = useState('');
  const [touched, setTouched] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (visible) {
      setDescription('');
      setTouched(false);
      setError(null);
    }
  }, [visible]);

  useEffect(() => {
    if (!touched) return;
    if (!description.trim()) setError(t('errors.descriptionRequired', { defaultValue: 'Description is required' }));
    else if (description.trim().length < 1) setError(t('errors.descriptionTooShort', { defaultValue: 'Description must be at least 1 character' }));
    else if (description.length > 13) setError(t('errors.descriptionTooLong', { count: 13, defaultValue: 'Description must be at most 13 characters' }));
    else setError(null);
  }, [description, touched, t]);

  const handleSubmit = () => {
    if (!error && description.trim().length >= 1 && description.length <= 13) {
      onSubmit(description.trim());
    } else {
      setTouched(true);
    }
  };

  const handleClose = () => {
    onClose();
  };

  // Choose icon and colors based on action type
  const icon = actionType === 'credit'
    ? "arrow-down-circle-outline"
    : "arrow-up-circle-outline";

  const iconColor = actionType === 'credit'
    ? "#16a34a" // success-600
    : "#dc2626"; // danger-600

  const iconBgColor = actionType === 'credit'
    ? "bg-success-100"
    : "bg-danger-100";

  const actionLabel = actionType === 'credit'
    ? t('envelopes.addFunds')
    : t('envelopes.withdraw');

  const modalTitle = actionType === 'credit' 
    ? t('envelopes.confirmDeposit')
    : t('envelopes.confirmWithdrawal');

  return (
    <ResponsiveModal
      visible={visible}
      onClose={onClose}
      title={modalTitle}
      size="sm"
      scrollable={false}
    >
      <View className="space-y-4">
        <ResponsiveFormField>
          <Text className="mb-2 text-sm font-medium text-text-secondary">
            {t('modals.addDescription')}:
          </Text>
          <View className="relative">
            <View className="absolute left-3 top-3 z-10">
              <Ionicons name="create-outline" size={18} color="#64748b" />
            </View>
            <TextInput
              value={description}
              onChangeText={setDescription}
              placeholder={t('common.description')}
              placeholderTextColor="#9ca3af"
              className={`p-3 pl-10 border border-surface-border rounded-xl bg-white text-text-primary ${
                Platform.OS === 'web' ? 'focus:border-primary-500 focus:ring-1 focus:ring-primary-500' : ''
              }`}
              multiline
              numberOfLines={3}
              textAlignVertical="top"
              style={{ minHeight: 80 }}
              editable={!loading}
              maxLength={13}
              onBlur={() => setTouched(true)}
              onFocus={() => setTouched(true)}
            />
            {touched && error && (
              <Text className="text-red-500 text-xs mt-1">{error}</Text>
            )}
          </View>
        </ResponsiveFormField>

        <ResponsiveFormActions>
          <ActionButton
            label={t('common.cancel')}
            onPress={onClose}
            disabled={loading}
            variant="secondary"
            size="md"
          />
          <ActionButton
            label={actionLabel}
            onPress={handleSubmit}
            disabled={loading || !!error || !description.trim()}
            variant="primary"
            size="md"
          />
        </ResponsiveFormActions>
      </View>
    </ResponsiveModal>
  );
};

export default DescriptionModal;