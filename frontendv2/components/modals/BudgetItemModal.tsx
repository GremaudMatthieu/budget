import React, { useState, useEffect, useRef } from 'react';
import { 
  View, 
  Text, 
  TextInput, 
  TouchableOpacity, 
  Modal, 
  ScrollView, 
  TouchableWithoutFeedback,
  KeyboardAvoidingView,
  Platform,
  Keyboard
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import validateAmount from '@/utils/validateAmount';
import { Category } from '@/types/budgetTypes';
import SelectField from '@/components/inputs/SelectField';
import { SelectOption } from '@/components/modals/SelectModal';
import { useTranslation } from '@/utils/useTranslation';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';
import { validateEnvelopeAmountField } from '@/utils/validateEnvelopeAmount';
import ResponsiveModal from '@/components/modals/ResponsiveModal';
import { ResponsiveForm, ResponsiveFormField, ResponsiveFormActions } from '@/components/forms/ResponsiveForm';
import ResponsiveInput from '@/components/inputs/ResponsiveInput';
import ActionButton from '@/components/buttons/ActionButton';

interface BudgetItemModalProps {
  isVisible: boolean;
  onClose: () => void;
  onSubmit: (name: string, amount: string, category: string) => void;
  title: string;
  initialName?: string;
  initialAmount?: string;
  initialCategory?: string;
  isEdit?: boolean;
  itemType: 'need' | 'want' | 'saving' | 'income';
  categories: Category[];
}

const BudgetItemModal: React.FC<BudgetItemModalProps> = ({
  isVisible,
  onClose,
  onSubmit,
  title,
  initialName = '',
  initialAmount = '',
  initialCategory = '',
  isEdit = false,
  itemType,
  categories
}) => {
  const { t } = useTranslation();
  const [name, setName] = useState(initialName);
  const [amount, setAmount] = useState(initialAmount);
  const [category, setCategory] = useState(initialCategory);
  const [errors, setErrors] = useState<{ name?: string; amount?: string; category?: string }>({});
  const nameInputRef = useRef<TextInput>(null);
  const [touched, setTouched] = useState<{ name: boolean; amount: boolean; category: boolean }>({
    name: false,
    amount: false,
    category: false,
  });

  useEffect(() => {
    if (isVisible) {
      setName(initialName);
      setAmount(initialAmount);
      setCategory(initialCategory);
      setErrors({});
      setTouched({ name: false, amount: false, category: false });
    }
  }, [isVisible, initialName, initialAmount, initialCategory]);

  // Re-validate on input change to update errors and button state
  useEffect(() => {
    validate();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [name, amount, category]);

  const validate = () => {
    const newErrors: { name?: string; amount?: string; category?: string } = {};
    if (!name.trim()) newErrors.name = t('errors.nameRequired', { defaultValue: 'Name is required' });
    else if (name.trim().length < 3) newErrors.name = t('errors.nameTooShort', { defaultValue: 'Name must be at least 3 characters' });
    else if (name.trim().length > 35) newErrors.name = t('errors.nameTooLong', { defaultValue: 'Name must be at most 35 characters' });
    const amountError = validateEnvelopeAmountField(amount, t);
    if (amountError) newErrors.amount = amountError;
    if (!category.trim()) newErrors.category = t('errors.categoryRequired', { defaultValue: 'Category is required' });
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleAmountChange = (value: string) => {
    // Allow ',' as decimal separator, convert to '.'
    let input = value.replace(/,/g, '.');
    // Remove anything that's not a digit or decimal point
    input = input.replace(/[^0-9.]/g, '');
    // Ensure only one decimal point
    const parts = input.split('.');
    let intPart = parts[0].slice(0, 10);
    let formatted = intPart;
    if (parts.length > 1) {
      let decPart = parts[1].slice(0, 2);
      formatted += '.' + decPart;
    }
    setAmount(formatted);
  };

  const handleSubmit = () => {
    if (validate()) {
      Keyboard.dismiss();
      // Format amount to two decimal places
      const formattedAmount = Number(amount).toFixed(2);
      onSubmit(name.trim(), formattedAmount, category.trim());
    }
  };

  return (
    <ResponsiveModal
      visible={isVisible}
      onClose={onClose}
      title={title}
      size="md"
    >
      <ResponsiveForm>
        <ResponsiveFormField>
          <ResponsiveInput
            ref={nameInputRef}
            label={t('common.name')}
            value={name}
            onChangeText={setName}
            placeholder={t(`modals.${itemType}Name`) || `${itemType.charAt(0).toUpperCase() + itemType.slice(1)} name`}
            returnKeyType="next"
            onSubmitEditing={() => {
              // Focus amount field next
            }}
            blurOnSubmit={false}
            maxLength={35}
            onBlur={() => setTouched(t => ({ ...t, name: true }))}
            onFocus={() => setTouched(t => ({ ...t, name: true }))}
            error={touched.name && errors.name ? errors.name : undefined}
          />
        </ResponsiveFormField>
        
        <ResponsiveFormField>
          <ResponsiveInput
            label={t('common.amount')}
            value={amount}
            onChangeText={handleAmountChange}
            placeholder={t('modals.amountPlaceholder') || '0.00'}
            keyboardType="decimal-pad"
            returnKeyType="done"
            onSubmitEditing={handleSubmit}
            maxLength={13}
            onBlur={() => setTouched(t => ({ ...t, amount: true }))}
            onFocus={() => setTouched(t => ({ ...t, amount: true }))}
            error={touched.amount && errors.amount ? errors.amount : undefined}
          />
        </ResponsiveFormField>
        
        <ResponsiveFormField>
          <SelectField
            label={t('common.category')}
            placeholder={t('modals.selectCategory')}
            options={categories.map(cat => ({
              id: cat.id,
              name: cat.name,
              icon: itemType === 'need' ? "bag-outline" :
                    itemType === 'want' ? "cart-outline" :
                    itemType === 'saving' ? "save-outline" : "wallet-outline",
              iconColor: itemType === 'need' ? "#16a34a" :
                         itemType === 'want' ? "#0284c7" :
                         itemType === 'saving' ? "#ca8a04" : "#9333ea"
            }))}
            value={category}
            onChange={(value) => {
              setCategory(value);
              setTouched(t => ({ ...t, category: true }));
            }}
            icon={
              <Ionicons 
                name={
                  itemType === 'need' ? "bag-outline" :
                  itemType === 'want' ? "cart-outline" :
                  itemType === 'saving' ? "save-outline" : "wallet-outline"
                } 
                size={18} 
                color={
                  itemType === 'need' ? "#16a34a" :
                  itemType === 'want' ? "#0284c7" :
                  itemType === 'saving' ? "#ca8a04" : "#9333ea"
                } 
              />
            }
            noMargin={true}
            error={touched.category && errors.category ? errors.category : undefined}
          />
        </ResponsiveFormField>

        <ResponsiveFormActions>
          <ActionButton
            label={isEdit ? t('common.save') : t('common.add')}
            onPress={handleSubmit}
            disabled={Object.keys(errors).length > 0 || !name.trim() || !amount.trim() || !category.trim()}
            variant="primary"
            size="md"
          />
        </ResponsiveFormActions>
      </ResponsiveForm>
    </ResponsiveModal>
  );
};

export default BudgetItemModal;