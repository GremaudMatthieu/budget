import React, { useState, useEffect } from 'react';
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
  const [name, setName] = useState(initialName);
  const [amount, setAmount] = useState(initialAmount);
  const [category, setCategory] = useState(initialCategory);
  const [showCategorySelect, setShowCategorySelect] = useState(false);

  // Reset form when modal opens
  useEffect(() => {
    if (isVisible) {
      setName(initialName);
      setAmount(initialAmount);
      setCategory(initialCategory);
    }
  }, [isVisible, initialName, initialAmount, initialCategory]);

  // Handle amount input validation
  const handleAmountChange = (value: string) => {
    if (value === '' || validateAmount(value)) {
      setAmount(value);
    }
  };

  // Check if form is valid
  const isFormValid = () => {
    return (
      name.trim() !== '' && 
      amount.trim() !== '' && 
      parseFloat(amount) > 0 && 
      category.trim() !== ''
    );
  };

  // Handle form submission
  const handleSubmit = () => {
    if (isFormValid()) {
      onSubmit(name, amount, category);
    }
  };

  return (
    <Modal
      animationType="slide"
      transparent={true}
      visible={isVisible}
      onRequestClose={onClose}
    >
      <TouchableWithoutFeedback onPress={Keyboard.dismiss}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          className="flex-1 justify-end"
        >
          <TouchableWithoutFeedback onPress={onClose}>
            <View className="flex-1 bg-black/50" />
          </TouchableWithoutFeedback>
          
          <View className="bg-white rounded-t-3xl px-6 pt-6 pb-10">
            <View className="flex-row justify-between items-center mb-6">
              <Text className="text-xl font-bold">{title}</Text>
              <TouchableOpacity onPress={onClose} className="p-2">
                <Ionicons name="close" size={24} color="#666" />
              </TouchableOpacity>
            </View>
            
            {/* Form */}
            <View className="space-y-4">
              {/* Name Field */}
              <View>
                <Text className="text-gray-700 mb-1">Name</Text>
                <TextInput
                  value={name}
                  onChangeText={setName}
                  placeholder={`${itemType.charAt(0).toUpperCase() + itemType.slice(1)} name`}
                  className="border border-gray-300 rounded-lg p-3 bg-white"
                />
              </View>
              
              {/* Amount Field */}
              <View>
                <Text className="text-gray-700 mb-1">Amount</Text>
                <TextInput
                  value={amount}
                  onChangeText={handleAmountChange}
                  placeholder="0.00"
                  keyboardType="decimal-pad"
                  className="border border-gray-300 rounded-lg p-3 bg-white"
                />
              </View>
              
              {/* Category Field */}
              <SelectField
                label="Category"
                placeholder="Select a category"
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
                onChange={setCategory}
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
              />
              
              {/* Submit Button */}
              <TouchableOpacity
                onPress={handleSubmit}
                disabled={!isFormValid()}
                className={`py-3 rounded-lg mt-4 ${
                  isFormValid() ? 'bg-primary-600' : 'bg-gray-300'
                }`}
              >
                <Text className="text-white text-center font-medium">
                  {isEdit ? 'Save Changes' : 'Add Item'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        </KeyboardAvoidingView>
      </TouchableWithoutFeedback>
    </Modal>
  );
};

export default BudgetItemModal;