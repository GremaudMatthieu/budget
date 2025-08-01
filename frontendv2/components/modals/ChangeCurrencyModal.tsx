import React, { useState, useEffect } from 'react';
import { View, Text, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import { getCurrencyOptions } from '@/utils/currencyUtils';
import ActionButton from '@/components/buttons/ActionButton';
import SelectField from '@/components/inputs/SelectField';
import ResponsiveModal from '@/components/modals/ResponsiveModal';
import { ResponsiveForm, ResponsiveFormField, ResponsiveFormActions } from '@/components/forms/ResponsiveForm';

interface ChangeCurrencyModalProps {
  visible: boolean;
  onClose: () => void;
  onSubmit: (currency: string) => void;
  currentCurrency: string;
  itemName: string; // envelope name or budget plan name
  loading?: boolean;
}

const ChangeCurrencyModal: React.FC<ChangeCurrencyModalProps> = ({
  visible,
  onClose,
  onSubmit,
  currentCurrency,
  itemName,
  loading = false
}) => {
  const { t } = useTranslation();
  const [currency, setCurrency] = useState(currentCurrency);
  const [submitted, setSubmitted] = useState(false);

  // Reset form when modal becomes visible
  useEffect(() => {
    if (visible) {
      setCurrency(currentCurrency);
      setSubmitted(false);
    }
  }, [visible, currentCurrency]);

  const handleSubmit = () => {
    if (currency === currentCurrency) {
      onClose();
      return;
    }
    setSubmitted(true);
    onSubmit(currency);
  };

  const handleClose = () => {
    setCurrency(currentCurrency);
    setSubmitted(false);
    onClose();
  };

  // Use the more dynamic currency options with translated labels
  const currencyOptionsList = getCurrencyOptions();

  return (
    <ResponsiveModal
      visible={visible}
      onClose={handleClose}
      title={t('modals.changeCurrency')}
      size="md"
    >
      <ResponsiveForm>
        <View className="mb-4">
          <Text className="text-text-secondary text-center">
            {t('modals.changeCurrencyDescription', { item: itemName })}
          </Text>
        </View>

        <ResponsiveFormField>
          <SelectField
            label={t('common.newCurrency')}
            placeholder={t('modals.selectCurrency')}
            options={currencyOptionsList.map(option => ({
              id: option.value,
              name: option.label,
              icon: "cash-outline",
              iconColor: "#16a34a"
            }))}
            value={currency}
            onChange={setCurrency}
            icon={<Ionicons name="cash-outline" size={18} color="#16a34a" />}
            disabled={submitted || loading}
            noMargin={true}
          />
        </ResponsiveFormField>

        <ResponsiveFormActions>
          <ActionButton
            label={t('common.cancel')}
            onPress={handleClose}
            disabled={submitted || loading}
            variant="secondary"
            size="md"
          />
          <ActionButton
            label={submitted || loading ? t('common.loading') : t('common.save')}
            onPress={handleSubmit}
            disabled={(submitted || loading) || (currency === currentCurrency)}
            variant="primary"
            size="md"
          />
        </ResponsiveFormActions>
      </ResponsiveForm>
    </ResponsiveModal>
  );
};

export default ChangeCurrencyModal;