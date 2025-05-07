import React, { useState, useEffect } from 'react';
import { View, Text, Modal, TouchableOpacity, ScrollView, KeyboardAvoidingView, Platform, TouchableWithoutFeedback, Keyboard } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';
import { getCurrencyOptions } from '@/utils/currencyUtils';
import AmountInput from '@/components/inputs/AmountInput';
import NameInput from '@/components/inputs/NameInput';
import ActionButton from '@/components/buttons/ActionButton';
import SelectField from '@/components/inputs/SelectField';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';
import validateAmount from '@/utils/validateAmount';

interface CreateEnvelopeModalProps {
  visible: boolean;
  onClose: () => void;
  onSubmit: (name: string, targetAmount: string, currency: string) => void;
}

const CreateEnvelopeModal: React.FC<CreateEnvelopeModalProps> = ({
  visible,
  onClose,
  onSubmit
}) => {
  const { t } = useTranslation();
  const [name, setName] = useState('');
  const [targetAmount, setTargetAmount] = useState('');
  const [currency, setCurrency] = useState('USD');
  const [error, setError] = useState<string | null>(null);
  const [submitted, setSubmitted] = useState(false);
  const [nameTouched, setNameTouched] = useState(false);
  const [amountTouched, setAmountTouched] = useState(false);
  const [nameError, setNameError] = useState<string | null>(null);
  const [amountError, setAmountError] = useState<string | null>(null);

  // Clear form when modal becomes visible
  useEffect(() => {
    if (visible) {
      resetForm();
    }
  }, [visible]);

  // Auto-close after submission with a delay (as a fallback)
  useEffect(() => {
    if (submitted) {
      const timer = setTimeout(() => {
        setSubmitted(false);
        onClose();
      }, 1000); // Close after 1 second if websocket doesn't respond
      return () => clearTimeout(timer);
    }
  }, [submitted, onClose]);

  // Validate name
  useEffect(() => {
    if (!nameTouched) return;
    if (!name.trim()) setNameError(t('errors.fieldRequired'));
    else if (name.trim().length < 1) setNameError(t('validation.minLength', { count: 1 }));
    else if (name.trim().length > 50) setNameError(t('validation.maxLength', { count: 50 }));
    else setNameError(null);
  }, [name, nameTouched, t]);

  // Validate amount
  useEffect(() => {
    if (!amountTouched) return;
    const MAX_AMOUNT = 9999999999.99;
    const normalized = normalizeAmountInput(targetAmount);
    if (!normalized.trim()) setAmountError(t('errors.fieldRequired'));
    else if (normalized.trim().length < 1) setAmountError(t('validation.minLength', { count: 1 }));
    else if (normalized.trim().length > 13) setAmountError(t('validation.maxLength', { count: 13 }));
    else if (!/^\d{1,10}(\.\d{0,2})?$/.test(normalized)) setAmountError(t('validation.invalidAmount'));
    else if (isNaN(Number(normalized)) || Number(normalized) <= 0) setAmountError(t('validation.positiveNumber'));
    else if (Number(normalized) > MAX_AMOUNT) setAmountError(t('validation.maxLength', { count: 13 }));
    else setAmountError(null);
  }, [targetAmount, amountTouched, t]);

  const handleNameChange = (v: string) => {
    setName(v);
    if (!nameTouched) setNameTouched(true);
  };

  const handleAmountChange = (v: string) => {
    const normalized = normalizeAmountInput(v);
    setTargetAmount(normalized);
    if (!amountTouched) setAmountTouched(true);
  };

  const handleSubmit = () => {
    setNameTouched(true);
    setAmountTouched(true);
    const normalized = normalizeAmountInput(targetAmount);
    if (nameError || amountError || !name.trim() || !normalized.trim()) return;
    setSubmitted(true);
    // Format amount to two decimals
    const formattedAmount = Number(normalized).toFixed(2);
    onSubmit(name.trim(), formattedAmount, currency);
  };

  const resetForm = () => {
    setName('');
    setTargetAmount('');
    setCurrency('USD');
    setError(null);
    setSubmitted(false);
    setNameTouched(false);
    setAmountTouched(false);
    setNameError(null);
    setAmountError(null);
  };

  const handleClose = () => {
    resetForm();
    onClose();
  };

  // Use the more dynamic currency options with translated labels
  const currencyOptionsList = getCurrencyOptions();

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onClose}
      accessibilityViewIsModal
      accessible
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1 justify-end"
      >
        <TouchableWithoutFeedback onPress={onClose} accessible={false}>
          <View className="flex-1 bg-black/50" />
        </TouchableWithoutFeedback>
        <View className="bg-white rounded-t-3xl px-6 pt-6 pb-10">
          <View className="flex-row justify-between items-center mb-6">
            <Text className="text-xl font-bold" accessibilityRole="header">{t('modals.createEnvelope')}</Text>
            <TouchableOpacity onPress={onClose} className="p-2" accessibilityLabel={t('common.close')}>
              <Ionicons name="close" size={24} color="#666" />
            </TouchableOpacity>
          </View>
          <ScrollView className="space-y-4" keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>
            <NameInput
              label={t('envelopes.envelopeName')}
              value={name}
              onChangeText={handleNameChange}
              placeholder={t('modals.nameFieldHint')}
              icon={<Ionicons name="bookmark-outline" size={18} color="#64748b" />}
              editable={!submitted}
              onBlur={() => setNameTouched(true)}
              onFocus={() => setNameTouched(true)}
              error={nameTouched && nameError ? nameError : undefined}
            />
            <AmountInput
              label={t('envelopes.targetAmount')}
              value={targetAmount}
              onChangeText={handleAmountChange}
              currency="$"
              editable={!submitted}
              placeholder={t('modals.amountFieldHint')}
              onBlur={() => setAmountTouched(true)}
              onFocus={() => setAmountTouched(true)}
              error={amountTouched && amountError ? amountError : undefined}
              maxLength={13}
            />
            <SelectField
              label={t('common.currency')}
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
              disabled={submitted}
            />
          </ScrollView>
          <View className="flex-row space-x-3 mt-6">
            <ActionButton
              label={t('common.cancel')}
              onPress={onClose}
              disabled={submitted}
              className="flex-1"
              variant="secondary"
            />
            <ActionButton
              label={submitted ? t('common.loading') : t('envelopes.createEnvelope')}
              onPress={handleSubmit}
              disabled={submitted || !!nameError || !!amountError || !nameTouched || !amountTouched}
              className="flex-1"
              variant="primary"
            />
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
};

export default CreateEnvelopeModal;