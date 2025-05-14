import React, { useState, useEffect } from 'react';
import { 
  View, 
  Text, 
  ScrollView, 
  TouchableOpacity, 
  TextInput,
  ActivityIndicator,
  Platform,
  KeyboardAvoidingView,
  Modal
} from 'react-native';
import { useLocalSearchParams, useRouter, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { useBudget } from '@/contexts/BudgetContext';
import { useErrorContext } from '@/contexts/ErrorContext';
import { useTranslation } from '@/utils/useTranslation';
import validateAmount from '@/utils/validateAmount';
import { currencyOptions } from '@/utils/currencyUtils';
import SelectField from '@/components/inputs/SelectField';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import { normalizeAmountInput } from '@/utils/normalizeAmountInput';
import { validateEnvelopeAmountField } from '@/utils/validateEnvelopeAmount';

export default function CreateBudgetPlanScreen() {
  const params = useLocalSearchParams();
  const router = useRouter();
  const { setError } = useErrorContext();
  const { t } = useTranslation();
  const { 
    createBudgetPlan, 
    newlyCreatedBudgetPlanId, 
    loading,
    incomesCategories,
    fetchIncomesCategories
  } = useBudget();

  // Form state
  const [currency, setCurrency] = useState('USD');
  const [currencySelectVisible, setCurrencySelectVisible] = useState(false);
  const [incomes, setIncomes] = useState([{ name: '', amount: '', category: 'Employment' }]);
  const [year, setYear] = useState<number>(0);
  const [month, setMonth] = useState<number>(0);

  // State for category selection modal
  const [categorySelectVisible, setCategorySelectVisible] = useState(false);
  const [currentEditingIncomeIndex, setCurrentEditingIncomeIndex] = useState<number | null>(null);

  // Add per-income error state
  const [incomeErrors, setIncomeErrors] = useState<{ name?: string; amount?: string; category?: string }[]>([]);

  // Get year and month from URL params
  useEffect(() => {
    if (params.year && params.month) {
      setYear(Number(params.year));
      setMonth(Number(params.month));
    } else {
      const now = new Date();
      setYear(now.getFullYear());
      setMonth(now.getMonth() + 1);
    }
  }, [params]);

  // Navigate to newly created budget plan when it's ready
  useEffect(() => {
    if (newlyCreatedBudgetPlanId) {
      router.push(`/budget-plans/${newlyCreatedBudgetPlanId}`);
    }
  }, [newlyCreatedBudgetPlanId, router]);
  
  // Fetch income categories when component mounts
  useEffect(() => {
    fetchIncomesCategories();
  }, [fetchIncomesCategories]);

  // Format month for display
  const formatMonthYear = (year: number, month: number) => {
    if (!year || !month) return '';
    const date = new Date(year, month - 1, 1);
    return date.toLocaleDateString(t('common.locale'), { month: 'long', year: 'numeric' });
  };

  // Handle form submission
  const handleSubmit = async () => {
    // Validate form
    if (!isFormValid()) {
      setError(t('errors.fillAllRequiredFields'));
      return;
    }
    
    try {
      // Create date for the first day of the selected month
      const date = new Date(year, month - 1, 1);
      await createBudgetPlan(date, currency, incomes);
    } catch (error) {
      console.error('Failed to create budget plan:', error);
      setError(t('errors.createBudgetPlanFailed'));
    }
  };

  // Add new income field
  const handleAddIncome = () => {
    setIncomes([...incomes, { name: '', amount: '', category: '' }]);
  };

  // Remove income field
  const handleRemoveIncome = (index: number) => {
    if (incomes.length <= 1) return;
    const newIncomes = [...incomes];
    newIncomes.splice(index, 1);
    setIncomes(newIncomes);
  };

  // Update income name
  const handleIncomeNameChange = (index: number, value: string) => {
    const newIncomes = [...incomes];
    newIncomes[index].name = value;
    setIncomes(newIncomes);
  };

  // Update income amount
  const handleIncomeAmountChange = (index: number, value: string) => {
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
    const newIncomes = [...incomes];
    newIncomes[index].amount = formatted;
    setIncomes(newIncomes);
  };

  // Update income category
  const handleIncomeCategoryChange = (index: number, value: string) => {
    const newIncomes = [...incomes];
    newIncomes[index].category = value;
    setIncomes(newIncomes);
  };

  // Validate a single income entry (name, amount, category)
  const validateIncome = (income: { name: string; amount: string; category: string }) => {
    const errors: { name?: string; amount?: string; category?: string } = {};
    if (!income.name.trim()) errors.name = t('errors.nameRequired');
    else if (income.name.trim().length < 3) errors.name = t('errors.nameTooShort');
    else if (income.name.trim().length > 35) errors.name = t('errors.nameTooLong', { count: 35 });
    const amountError = validateEnvelopeAmountField(income.amount, t);
    if (amountError) errors.amount = amountError;
    if (!income.category.trim()) errors.category = t('errors.categoryRequired');
    return errors;
  };

  // Validate all incomes and update error state
  useEffect(() => {
    setIncomeErrors(incomes.map(validateIncome));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [incomes]);

  // Update isFormValid to use new validation
  const isFormValid = () => {
    return (
      currency && 
      incomes.length > 0 &&
      incomeErrors.every(err => Object.keys(err).length === 0)
    );
  };

  // Get currency label
  const getCurrencyLabel = (value: string): string => {
    const option = currencyOptions.find(opt => opt.value === value);
    return option ? option.label : value;
  };

  return (
    <KeyboardAvoidingView 
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      className="flex-1"
    >
      <View className="flex-1 bg-background-subtle">
        <StatusBar style="dark" />
        <Stack.Screen options={{ headerShown: false }} />
        <AnimatedHeaderLayout
          title={t('budgetPlans.createBudgetPlan')}
          subtitle={formatMonthYear(year, month) ? `${t('budgetPlans.planning')} ${formatMonthYear(year, month)}` : undefined}
          showBackButton={true}
          headerHeight={130}
        >
          <ScrollView 
            className="flex-1 px-4 pt-6"
            contentContainerStyle={{ paddingBottom: 40 }}
          >
            {/* Instructions Card */}
            <View className="card mb-6">
              <View className="card-content">
                <View className="flex-row items-start">
                  <View className="w-10 h-10 rounded-full bg-primary-100 items-center justify-center mr-3">
                    <Ionicons name="information-circle" size={24} color="#0c6cf2" />
                  </View>
                  <View className="flex-1">
                    <Text className="text-lg font-semibold text-text-primary mb-2">{t('budgetPlans.gettingStarted')}</Text>
                    <Text className="text-text-secondary">
                      {t('budgetPlans.gettingStartedText')}
                    </Text>
                  </View>
                </View>
              </View>
            </View>
            
            {/* Currency Selection */}
            <View className="card mb-6">
              <View className="card-content">
                <Text className="text-lg font-semibold text-text-primary mb-4">{t('budgetPlans.selectCurrency')}</Text>
                
                <TouchableOpacity 
                  onPress={() => setCurrencySelectVisible(true)}
                  className="neomorphic-inset p-4 rounded-lg flex-row justify-between items-center"
                >
                  <View className="flex-row items-center">
                    <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-2">
                      <Text className="text-primary-700 font-bold">{currency.charAt(0)}</Text>
                    </View>
                    <Text className="text-text-primary font-medium">{getCurrencyLabel(currency)}</Text>
                  </View>
                  <Ionicons name="chevron-down" size={18} color="#64748b" />
                </TouchableOpacity>
              </View>
            </View>
            
            {/* Income Entries */}
            <View className="card mb-6">
              <View className="card-content">
                <View className="flex-row justify-between items-center mb-4">
                  <View className="flex-row items-center">
                    <View className="w-10 h-10 rounded-full bg-purple-100 items-center justify-center mr-3">
                      <Ionicons name="wallet-outline" size={20} color="#9333ea" />
                    </View>
                    <Text className="text-lg font-semibold text-text-primary">{t('budgetPlans.incomeSources')}</Text>
                  </View>
                  <TouchableOpacity 
                    onPress={handleAddIncome}
                    className="bg-primary-100 p-3 rounded-full"
                  >
                    <Ionicons name="add" size={20} color="#0c6cf2" />
                  </TouchableOpacity>
                </View>
                
                {incomes.map((income, index) => (
                  <View 
                    key={index} 
                    className="neomorphic-inset p-4 rounded-lg mb-4"
                  >
                    <View className="flex-row justify-between items-center mb-3">
                      <Text className="font-medium text-primary-600">{t('budgetPlans.incomeSource')} #{index + 1}</Text>
                      
                      {incomes.length > 1 && (
                        <TouchableOpacity 
                          onPress={() => handleRemoveIncome(index)}
                          className="p-2 bg-red-50 rounded-full"
                        >
                          <Ionicons name="trash-outline" size={16} color="#dc2626" />
                        </TouchableOpacity>
                      )}
                    </View>
                    
                    <View className="mb-3">
                      <Text className="text-text-secondary text-sm mb-1">{t('common.name')}</Text>
                      <TextInput
                        value={income.name}
                        onChangeText={(value) => handleIncomeNameChange(index, value)}
                        placeholder={t('budgetPlans.incomeNamePlaceholder')}
                        className="border border-surface-border rounded-lg p-3 bg-white"
                        maxLength={35}
                      />
                      {incomeErrors[index]?.name && (
                        <Text className="text-red-500 text-xs mt-1">{incomeErrors[index].name}</Text>
                      )}
                    </View>
                    
                    <View className="mb-3">
                      <Text className="text-text-secondary text-sm mb-1">{t('common.amount')}</Text>
                      <View className="flex-row items-center border border-surface-border rounded-lg bg-white overflow-hidden">
                        <View className="px-3 py-3 bg-gray-50 border-r border-surface-border">
                          <Text className="font-medium text-text-secondary">{currency}</Text>
                        </View>
                        <TextInput
                          value={income.amount}
                          onChangeText={(value) => handleIncomeAmountChange(index, value)}
                          placeholder={t('modals.amountPlaceholder')}
                          keyboardType="decimal-pad"
                          className="flex-1 p-3"
                        />
                      </View>
                    </View>
                    
                    <View>
                      <SelectField
                        label={t('common.category')}
                        placeholder={t('modals.selectIncomeCategory')}
                        options={incomesCategories.map(cat => ({
                          id: cat.id,
                          name: cat.name,
                          icon: "pricetag-outline",
                          iconColor: "#9333ea"
                        }))}
                        value={income.category}
                        onChange={(value) => handleIncomeCategoryChange(index, value)}
                        icon={<Ionicons name="pricetag-outline" size={18} color="#9333ea" />}
                      />
                    </View>
                  </View>
                ))}
                
                <TouchableOpacity
                  onPress={handleAddIncome}
                  className="flex-row items-center justify-center p-3 border-2 border-dashed border-primary-200 rounded-lg"
                >
                  <Ionicons name="add-circle-outline" size={20} color="#0c6cf2" style={{ marginRight: 8 }} />
                  <Text className="text-primary-600 font-medium">{t('budgetPlans.addIncomeSource')}</Text>
                </TouchableOpacity>
              </View>
            </View>
            
            {/* Budget Tips */}
            <View className="bg-secondary-900 rounded-xl p-6 mb-6">
              <View className="flex-row items-start mb-4">
                <View className="w-10 h-10 rounded-full bg-secondary-800 items-center justify-center mr-3">
                  <Ionicons name="bulb-outline" size={20} color="#fbbf24" />
                </View>
                <View className="flex-1">
                  <Text className="text-white font-semibold mb-1">{t('budgetPlans.budgetTip')}</Text>
                  <Text className="text-secondary-200">
                    {t('budgetPlans.budgetTipText')}
                  </Text>
                </View>
              </View>
            </View>
            
            {/* Action Buttons */}
            <View className="flex-row space-x-4 mb-10">
              <TouchableOpacity
                onPress={() => router.back()}
                className="flex-1 py-4 px-6 rounded-xl border-2 border-primary-600"
              >
                <Text className="text-primary-600 text-center font-semibold">{t('common.cancel')}</Text>
              </TouchableOpacity>
              
              <TouchableOpacity
                onPress={handleSubmit}
                disabled={loading || !isFormValid()}
                className={`flex-1 py-4 px-6 rounded-xl shadow-md ${
                  loading || !isFormValid() ? 'bg-gray-400' : 'bg-primary-600'
                }`}
              >
                {loading ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <Text className="text-white text-center font-semibold">{t('common.create')}</Text>
                )}
              </TouchableOpacity>
            </View>
          </ScrollView>
        </AnimatedHeaderLayout>
        
        {/* Currency Selector Modal */}
        <Modal
          visible={currencySelectVisible}
          transparent={true}
          animationType="fade"
          onRequestClose={() => setCurrencySelectVisible(false)}
        >
          <View className="flex-1 bg-black/50 justify-center items-center p-6">
            <View className="bg-white w-full max-h-[70%] rounded-xl overflow-hidden">
              <View className="flex-row justify-between items-center p-4 border-b border-gray-200">
                <Text className="text-lg font-semibold text-text-primary">{t('modals.selectCurrency')}</Text>
                <TouchableOpacity onPress={() => setCurrencySelectVisible(false)}>
                  <Ionicons name="close" size={24} color="#64748b" />
                </TouchableOpacity>
              </View>
              
              <ScrollView>
                {currencyOptions.map((option) => (
                  <TouchableOpacity
                    key={option.value}
                    onPress={() => {
                      setCurrency(option.value);
                      setCurrencySelectVisible(false);
                    }}
                    className={`p-4 border-b border-gray-100 flex-row items-center ${
                      currency === option.value ? 'bg-primary-50' : ''
                    }`}
                  >
                    <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
                      <Text className="font-bold text-primary-700">{option.value.charAt(0)}</Text>
                    </View>
                    <Text className={`${currency === option.value ? 'text-primary-600 font-medium' : 'text-text-primary'}`}>
                      {option.label}
                    </Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </View>
          </View>
        </Modal>

        {/* Category Selector Modal */}
        <Modal
          visible={categorySelectVisible}
          transparent={true}
          animationType="fade"
          onRequestClose={() => setCategorySelectVisible(false)}
        >
          <View className="flex-1 bg-black/50 justify-center items-center p-6">
            <View className="bg-white w-full max-h-[70%] rounded-xl overflow-hidden">
              <View className="flex-row justify-between items-center p-4 border-b border-gray-200">
                <Text className="text-lg font-semibold text-text-primary">{t('modals.selectIncomeCategory')}</Text>
                <TouchableOpacity onPress={() => setCategorySelectVisible(false)}>
                  <Ionicons name="close" size={24} color="#64748b" />
                </TouchableOpacity>
              </View>
              
              <ScrollView>
                {incomesCategories.map((category) => (
                  <TouchableOpacity
                    key={category.id}
                    onPress={() => {
                      if (currentEditingIncomeIndex !== null) {
                        handleIncomeCategoryChange(currentEditingIncomeIndex, category.id);
                      }
                      setCategorySelectVisible(false);
                    }}
                    className={`p-4 border-b border-gray-100 flex-row items-center ${
                      currentEditingIncomeIndex !== null && 
                      incomes[currentEditingIncomeIndex].category === category.id 
                        ? 'bg-primary-50' 
                        : ''
                    }`}
                  >
                    <View className="w-8 h-8 rounded-full bg-purple-100 items-center justify-center mr-3">
                      <Ionicons name="pricetag-outline" size={16} color="#9333ea" />
                    </View>
                    <Text className={`${
                      currentEditingIncomeIndex !== null && 
                      incomes[currentEditingIncomeIndex].category === category.id 
                        ? 'text-primary-600 font-medium' 
                        : 'text-text-primary'
                    }`}>
                      {category.name}
                    </Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </View>
          </View>
        </Modal>
      </View>
    </KeyboardAvoidingView>
  );
}