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
              <View>
                <Text className="text-gray-700 mb-1">Category</Text>
                <TouchableOpacity 
                  onPress={() => setShowCategorySelect(!showCategorySelect)}
                  className="border border-gray-300 rounded-lg p-3 flex-row justify-between items-center"
                >
                  <Text className={category ? 'text-black' : 'text-gray-400'}>
                    {category || 'Select category'}
                  </Text>
                  <Ionicons name="chevron-down" size={18} color="#666" />
                </TouchableOpacity>
                
                {showCategorySelect && (
                  <View className="border border-gray-300 rounded-lg mt-1 max-h-48">
                    <ScrollView>
                      {categories.map((cat) => (
                        <TouchableOpacity
                          key={cat.id}
                          onPress={() => {
                            setCategory(cat.id);
                            setShowCategorySelect(false);
                          }}
                          className={`p-3 border-b border-gray-200 ${
                            category === cat.id ? 'bg-primary-50' : ''
                          }`}
                        >
                          <Text className={category === cat.id ? 'text-primary-600 font-medium' : ''}>
                            {cat.name}
                          </Text>
                        </TouchableOpacity>
                      ))}
                      <TouchableOpacity
                        onPress={() => {
                          const newCategory = prompt('Enter new category');
                          if (newCategory) {
                            setCategory(newCategory);
                            setShowCategorySelect(false);
                          }
                        }}
                        className="p-3 flex-row items-center"
                      >
                        <Ionicons name="add-circle-outline" size={18} color="#0c6cf2" />
                        <Text className="text-primary-600 ml-2">Add New Category</Text>
                      </TouchableOpacity>
                    </ScrollView>
                  </View>
                )}
              </View>
              
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