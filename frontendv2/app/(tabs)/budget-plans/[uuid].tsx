import React, { useState, useEffect, useCallback, useRef } from 'react';
import { 
  View, 
  Text, 
  ScrollView, 
  TouchableOpacity, 
  ActivityIndicator,
  RefreshControl,
  TextInput,
  Image
} from 'react-native';
import { useLocalSearchParams, Stack, useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { useBudget } from '@/contexts/BudgetContext';
import { formatCurrency, getCurrencySymbol } from '@/utils/currencyUtils';
import formatAmount from '@/utils/formatAmount';
import validateAmount from '@/utils/validateAmount';
import { useErrorContext } from '@/contexts/ErrorContext';
import { useSocket } from '@/contexts/SocketContext';
import { useLanguage } from '@/contexts/LanguageContext';
import { useTranslation } from '@/utils/useTranslation';
import BudgetItemPieChart from '@/components/BudgetItemPieChart';
import BudgetItemModal from '@/components/modals/BudgetItemModal';
import DeleteConfirmationModal from '@/components/modals/DeleteConfirmationModal';
import DuplicateBudgetPlanModal from '@/components/modals/DuplicateBudgetPlanModal';
import TabNavigation from '@/components/TabNavigation';
import { normalizeMonthYear } from '@/utils/dateUtils';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';

// Types for the tab navigation
type TabType = 'overview' | 'needs' | 'wants' | 'savings' | 'incomes';

export default function BudgetPlanDetailScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const { setError } = useErrorContext();
  const { socket, connected } = useSocket();
  const { language } = useLanguage();
  const { t } = useTranslation();
  
  // Get the budget plan ID from route params
  const uuid = params.uuid as string;
  
  const { 
    fetchBudgetPlan, 
    selectedBudgetPlan,
    setSelectedBudgetPlan,
    addBudgetItem,
    adjustBudgetItem,
    removeBudgetItem,
    loading,
    needsCategories,
    wantsCategories,
    savingsCategories,
    incomesCategories,
    fetchNeedsCategories,
    fetchWantsCategories,
    fetchSavingsCategories,
    fetchIncomesCategories,
  } = useBudget();
  
  // State
  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [refreshing, setRefreshing] = useState(false);
  
  // State for modals
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItemType, setCurrentItemType] = useState<'need' | 'want' | 'saving' | 'income'>('need');
  const [currentItem, setCurrentItem] = useState<{ id: string; name: string; amount: string; category: string } | null>(null);
  
  // State for duplicate modal
  const [isDuplicateModalOpen, setIsDuplicateModalOpen] = useState(false);
  
  // Ref to track mount state
  const isMounted = useRef(true);

  // Format with exactly two decimal places
  const formatWithTwoDecimals = (num: number): number => {
    return Math.round(num * 100) / 100;
  };
  
  // Load budget plan details and setup cleanup
  useEffect(() => {
    if (!uuid) return;
    
    const loadBudgetPlanDetails = async () => {
      try {
        await fetchBudgetPlan(uuid);
      } catch (err) {
        if (isMounted.current) {
          setError(t('errors.failedToLoadBudgetPlanDetails'));
        }
      }
    };
    
    loadBudgetPlanDetails();
    
    return () => {
      isMounted.current = false;
    };
  }, [uuid, fetchBudgetPlan, setError]);
  
  // Set up WebSocket event listeners
  useEffect(() => {
    if (!socket || !connected || !uuid) return;
    
    const eventTypes = [
      'BudgetPlanIncomeAdded',
      'BudgetPlanIncomeAdjusted',
      'BudgetPlanIncomeRemoved',
      'BudgetPlanNeedAdded',
      'BudgetPlanNeedAdjusted',
      'BudgetPlanNeedRemoved',
      'BudgetPlanSavingAdded',
      'BudgetPlanSavingAdjusted',
      'BudgetPlanSavingRemoved',
      'BudgetPlanWantAdded',
      'BudgetPlanWantAdjusted',
      'BudgetPlanWantRemoved',
    ];
    
    // Generic event handler for all events
    const handleBudgetPlanEvent = (event: any) => {
      // Only process events for this budget plan
      if (event.aggregateId === uuid) {
        // Refresh the budget plan data
        fetchBudgetPlan(uuid);
      }
    };
    
    // Register handlers for all event types
    eventTypes.forEach(eventType => {
      socket.on(eventType, handleBudgetPlanEvent);
    });
    
    // Cleanup function to remove event listeners
    return () => {
      eventTypes.forEach(eventType => {
        socket.off(eventType, handleBudgetPlanEvent);
      });
    };
  }, [socket, connected, uuid, fetchBudgetPlan]);
  
  // Handle refreshing the data
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await fetchBudgetPlan(uuid);
    } catch (err) {
      setError(t('errors.failedToRefreshBudgetPlan'));
    } finally {
      setRefreshing(false);
    }
  }, [uuid, fetchBudgetPlan, setError]);
  
  // Format date
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString(language === 'fr' ? 'fr-FR' : 'en-US', { year: 'numeric', month: 'long' });
  };
  
  // Handle opening add modal
  const handleOpenAddModal = async (type: 'need' | 'want' | 'saving' | 'income') => {
    setCurrentItemType(type);
    
    // Fetch the appropriate categories for the modal
    try {
      switch (type) {
        case 'need':
          await fetchNeedsCategories();
          break;
        case 'want': 
          await fetchWantsCategories();
          break;
        case 'saving':
          await fetchSavingsCategories();
          break;
        case 'income':
          await fetchIncomesCategories();
          break;
      }
    } catch (err) {
      console.error(`Failed to fetch ${type} categories:`, err);
    }
    
    setIsAddModalOpen(true);
  };
  
  // Handle opening edit modal
  const handleOpenEditModal = async (
    type: 'need' | 'want' | 'saving' | 'income',
    id: string,
    name: string,
    amount: string,
    category: string,
  ) => {
    setCurrentItemType(type);
    setCurrentItem({ id, name, amount, category });
    
    // Fetch the appropriate categories for the modal
    try {
      switch (type) {
        case 'need':
          await fetchNeedsCategories();
          break;
        case 'want': 
          await fetchWantsCategories();
          break;
        case 'saving':
          await fetchSavingsCategories();
          break;
        case 'income':
          await fetchIncomesCategories();
          break;
      }
    } catch (err) {
      console.error(`Failed to fetch ${type} categories:`, err);
    }
    
    setIsEditModalOpen(true);
  };
  
  // Handle opening delete modal
  const handleOpenDeleteModal = (type: 'need' | 'want' | 'saving' | 'income', id: string, name: string) => {
    setCurrentItemType(type);
    setCurrentItem({ id, name, amount: '', category: '' });
    setIsDeleteModalOpen(true);
  };
  
  // Handle adding a budget item
  const handleAddItem = useCallback(async (name: string, amount: string, category: string) => {
    try {
      const success = await addBudgetItem(currentItemType, name, amount, category);
      if (success) {
        setIsAddModalOpen(false);
        await fetchBudgetPlan(uuid);
      }
    } catch (err) {
      setError(t(`errors.failedToAdd${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`));
    }
  }, [addBudgetItem, currentItemType, fetchBudgetPlan, uuid, setError]);
  
  // Handle editing a budget item
  const handleEditItem = useCallback(async (name: string, amount: string, category: string) => {
    if (!currentItem) return;
    
    try {
      const success = await adjustBudgetItem(
        currentItemType,
        currentItem.id,
        name,
        amount,
        category
      );
      
      if (success) {
        setIsEditModalOpen(false);
        await fetchBudgetPlan(uuid);
      }
    } catch (err) {
      setError(t(`errors.failedToUpdate${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`));
    }
  }, [adjustBudgetItem, currentItem, currentItemType, fetchBudgetPlan, uuid, setError]);
  
  // Handle deleting a budget item
  const handleDeleteItem = useCallback(async () => {
    if (!currentItem) return;
    
    try {
      const success = await removeBudgetItem(currentItemType, currentItem.id);
      
      if (success) {
        setIsDeleteModalOpen(false);
        await fetchBudgetPlan(uuid);
      }
    } catch (err) {
      setError(t(`errors.failedToDelete${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`));
    }
  }, [removeBudgetItem, currentItem, currentItemType, fetchBudgetPlan, uuid, setError]);
  
  // Get the appropriate field names based on item type
  const getFieldNames = (type: 'need' | 'want' | 'saving' | 'income') => {
    switch (type) {
      case 'need':
        return { name: 'needName', amount: 'needAmount', category: 'category' };
      case 'want':
        return { name: 'wantName', amount: 'wantAmount', category: 'category' };
      case 'saving':
        return { name: 'savingName', amount: 'savingAmount', category: 'category' };
      case 'income':
        return { name: 'incomeName', amount: 'incomeAmount', category: 'category' };
    }
  };
  
  // Get category name from category ID
  const getCategoryName = (type: 'need' | 'want' | 'saving' | 'income', categoryId: string) => {
    let categoryList: any[] = [];
    
    switch (type) {
      case 'need':
        categoryList = needsCategories;
        break;
      case 'want':
        categoryList = wantsCategories;
        break;
      case 'saving':
        categoryList = savingsCategories;
        break;
      case 'income':
        categoryList = incomesCategories;
        break;
    }
    
    const category = categoryList.find(cat => cat.id === categoryId);
    return category ? category.name : categoryId;
  };
  
  // Extract budget plan details
  const { budgetPlan: planDetails, needs, wants, savings, incomes } = selectedBudgetPlan || {};
  
  // Parse the budget plan date to extract month and year
  const getBudgetPlanMonthYear = () => {
    if (!planDetails?.date) return { month: new Date().getMonth() + 1, year: new Date().getFullYear() };
    
    // Make sure we parse the date correctly by normalizing it
    const date = new Date(planDetails.date);
    return normalizeMonthYear(date);
  };
  
  // Get source month and year for duplicate modal
  const { month: sourceMonth, year: sourceYear } = getBudgetPlanMonthYear();
  
  // Calculate totals
  const totalIncome = incomes?.reduce((sum, income) => sum + parseFloat(income.incomeAmount), 0) || 0;
  const totalNeeds = needs?.reduce((sum, need) => sum + parseFloat(need.needAmount), 0) || 0;
  const totalWants = wants?.reduce((sum, want) => sum + parseFloat(want.wantAmount), 0) || 0;
  const totalSavings = savings?.reduce((sum, saving) => sum + parseFloat(saving.savingAmount), 0) || 0;
  
  // Calculate percentages
  const needsPercentage = totalIncome > 0 ? (totalNeeds / totalIncome) * 100 : 0;
  const wantsPercentage = totalIncome > 0 ? (totalWants / totalIncome) * 100 : 0;
  const savingsPercentage = totalIncome > 0 ? (totalSavings / totalIncome) * 100 : 0;
  
  // Calculate remaining (unallocated) amount
  const totalAllocated = totalNeeds + totalWants + totalSavings;
  const remaining = totalIncome - totalAllocated;
  
  // Prepare chart data
  const chartData = [
    { name: t('budgetPlans.needs'), value: totalNeeds, color: '#4CAF50' },
    { name: t('budgetPlans.wants'), value: totalWants, color: '#2196F3' },
    { name: t('budgetPlans.savings'), value: totalSavings, color: '#FFC107' },
  ];
  
  // Get icon and color for each type
  const getIconAndColor = (type: 'need' | 'want' | 'saving' | 'income') => {
    switch (type) {
      case 'need':
        return { icon: 'cash-outline', color: '#16a34a', bgColor: 'bg-green-100', textColor: 'text-green-700' };
      case 'want':
        return { icon: 'cart-outline', color: '#0284c7', bgColor: 'bg-blue-100', textColor: 'text-blue-700' };
      case 'saving':
        return { icon: 'save-outline', color: '#ca8a04', bgColor: 'bg-amber-100', textColor: 'text-amber-700' };
      case 'income':
        return { icon: 'wallet-outline', color: '#9333ea', bgColor: 'bg-purple-100', textColor: 'text-purple-700' };
    }
  };
  
  // Render a list of budget items
  const renderItemList = (type: 'need' | 'want' | 'saving' | 'income', items: any[]) => {
    if (!items) return null;
    
    const fields = getFieldNames(type);
    const { icon, color, bgColor, textColor } = getIconAndColor(type);
    
    return (
      <SwipeBackWrapper>
      <View className="mb-6">
        <View className="card">
          <View className="card-content">
            <View className="flex-row justify-between items-center mb-4">
              <View className="flex-row items-center">
                <View className={`w-10 h-10 rounded-full ${bgColor} items-center justify-center mr-3`}>
                  <Ionicons name={icon} size={20} color={color} />
                </View>
                <View>
                  <Text className="text-lg font-semibold text-text-primary">
                    {type === 'need' ? t('budgetPlans.needs') : 
                     type === 'want' ? t('budgetPlans.wants') : 
                     type === 'saving' ? t('budgetPlans.savings') : 
                     t('budgetPlans.incomes')}
                  </Text>
                  {type !== 'income' && (
                    <Text className={`text-xs ${textColor}`} numberOfLines={1} ellipsizeMode="tail">
                      {formatWithTwoDecimals(type === 'need' ? needsPercentage : type === 'want' ? wantsPercentage : savingsPercentage)}% {t('budgetPlans.ofIncome')}
                    </Text>
                  )}
                </View>
              </View>
              
              <TouchableOpacity
                onPress={() => handleOpenAddModal(type)}
                className="bg-primary-100 p-3 rounded-full"
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator size="small" color="#0c6cf2" />
                ) : (
                  <Ionicons name="add" size={20} color="#0c6cf2" />
                )}
              </TouchableOpacity>
            </View>
            
            <View className="neomorphic-inset p-3 rounded-lg mb-4">
              <Text className="text-text-secondary">
                {type === 'need' && t('budgetPlans.needsDescription')}
                {type === 'want' && t('budgetPlans.wantsDescription')}
                {type === 'saving' && t('budgetPlans.savingsDescription')}
                {type === 'income' && t('budgetPlans.incomesDescription')}
              </Text>
            </View>
            
            {items.length === 0 ? (
              <View className="py-6 items-center">
                <Ionicons name={icon} size={32} color="#d1d5db" />
                <Text className="text-gray-400 mt-2">{t('budgetPlans.no' + type.charAt(0).toUpperCase() + type.slice(1) + 's')}</Text>
                <TouchableOpacity
                  onPress={() => handleOpenAddModal(type)}
                  className={`mt-4 px-4 py-2 ${bgColor} rounded-lg`}
                >
                  <Text className={textColor}>{t('budgetPlans.add' + type.charAt(0).toUpperCase() + type.slice(1))}</Text>
                </TouchableOpacity>
              </View>
            ) : (
              <View className="space-y-3">
                {items.map((item) => (
                  <View key={item.uuid} className="neomorphic-inset p-3 rounded-lg">
                    <View className="flex-row justify-between items-center">
                      <View className="flex-1">
                        <Text 
                          className="font-medium text-text-primary"
                          numberOfLines={1}
                          ellipsizeMode="tail"
                          style={{ maxWidth: '95%' }}
                        >
                          {item[fields.name]}
                        </Text>
                        <View className="flex-row items-center">
                          <Ionicons name="pricetag-outline" size={12} color="#64748b" />
                          <Text 
                            className="text-xs text-text-secondary ml-1" 
                            numberOfLines={1} 
                            ellipsizeMode="tail"
                            style={{ maxWidth: 120 }}
                          >
                            {getCategoryName(type, item[fields.category])}
                          </Text>
                        </View>
                      </View>
                      
                      <View className="flex-row items-center">
                        <Text 
                          className="font-semibold mr-4 text-primary-600" 
                          numberOfLines={1} 
                          ellipsizeMode="tail"
                          style={{ maxWidth: 100 }}
                        >
                          {formatCurrency(
                            formatWithTwoDecimals(parseFloat(item[fields.amount])), 
                            planDetails?.currency
                          )}
                        </Text>
                        
                        <TouchableOpacity
                          onPress={() => handleOpenEditModal(
                            type,
                            item.uuid,
                            item[fields.name],
                            item[fields.amount],
                            item[fields.category]
                          )}
                          className="p-2 bg-gray-100 rounded-full mr-1"
                          disabled={loading}
                        >
                          <Ionicons name="create-outline" size={16} color="#64748b" />
                        </TouchableOpacity>
                        
                        <TouchableOpacity
                          onPress={() => handleOpenDeleteModal(type, item.uuid, item[fields.name])}
                          className="p-2 bg-red-50 rounded-full"
                          disabled={loading}
                        >
                          <Ionicons name="trash-outline" size={16} color="#dc2626" />
                        </TouchableOpacity>
                      </View>
                    </View>
                  </View>
                ))}
              </View>
            )}
            
            <View className="mt-6 pt-4 border-t border-gray-200">
              <View className="flex-row justify-between items-center">
                <Text className="font-semibold text-text-primary">{t('common.total')}</Text>
                <Text 
                  className="text-xl font-bold text-primary-600"
                  numberOfLines={1}
                  ellipsizeMode="tail"
                  style={{ maxWidth: 150 }}
                >
                  {formatCurrency(
                    formatWithTwoDecimals(
                      type === 'need'
                        ? totalNeeds
                        : type === 'want'
                          ? totalWants
                          : type === 'saving'
                            ? totalSavings
                            : totalIncome
                    ),
                    planDetails?.currency
                  )}
                </Text>
              </View>
              
              {type !== 'income' && (
                <View className="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <View 
                    className={`h-2 ${
                      type === 'need' 
                        ? 'bg-green-500' 
                        : type === 'want' 
                          ? 'bg-blue-500' 
                          : 'bg-amber-500'
                    }`}
                    style={{ 
                      width: `${
                        Math.min(100, (type === 'need' 
                          ? needsPercentage 
                          : type === 'want' 
                            ? wantsPercentage 
                            : savingsPercentage))
                      }%` 
                    }}
                  />
                </View>
              )}
            </View>
          </View>
        </View>
      </View>
      </SwipeBackWrapper>
    );
  };
  
  if (loading && !selectedBudgetPlan) {
    return (
      <SwipeBackWrapper>
      <View className="flex-1 justify-center items-center bg-background-light">
        <View className="w-16 h-16 rounded-full bg-primary-100 items-center justify-center">
          <ActivityIndicator size="large" color="#0c6cf2" />
        </View>
        <Text className="text-secondary-600 mt-4 font-medium">{t('budgetPlans.loading')}</Text>
      </View>
      </SwipeBackWrapper>
    );
  }
  
  if (!selectedBudgetPlan) {
    return (
      <SwipeBackWrapper>
      <View className="flex-1 justify-center items-center bg-background-light">
        <View className="w-16 h-16 bg-red-100 rounded-full items-center justify-center mb-4">
          <Ionicons name="alert-circle-outline" size={32} color="#dc2626" />
        </View>
        <Text className="text-text-primary text-lg font-medium mb-2">{t('budgetPlans.notFound')}</Text>
        <Text className="text-text-secondary mb-6 text-center px-8">
          {t('budgetPlans.notFoundDescription')}
        </Text>
        <TouchableOpacity
          onPress={() => router.push('/budget-plans')}
          className="bg-primary-600 px-6 py-3 rounded-xl shadow-sm"
        >
          <Text className="text-white font-medium">{t('budgetPlans.backToBudgetPlans')}</Text>
        </TouchableOpacity>
      </View>
      </SwipeBackWrapper>
    );
  }
  
  return (
    <SwipeBackWrapper>
    <View className="flex-1 bg-background-subtle">
      <StatusBar style="dark" />
      
      {/* Stack navigation customization */}
      <Stack.Screen 
        options={{
          headerShown: false
        }}
      />
      
      {/* Header Section */}
      <View className="bg-primary-600 px-6 pt-16 pb-12 rounded-b-3xl shadow-lg">
        <View className="flex-row justify-between items-center mb-4">
          <TouchableOpacity
            onPress={() => router.push('/budget-plans')}
            className="bg-white/20 p-2 rounded-full"
          >
            <Ionicons name="chevron-back" size={24} color="white" />
          </TouchableOpacity>
          <Text 
            className="text-2xl font-bold text-white"
            numberOfLines={1}
            ellipsizeMode="tail"
            style={{ maxWidth: 200 }}
          >
            {planDetails?.date ? formatDate(planDetails.date) : t('budgetPlans.budgetPlan')}
          </Text>
          <TouchableOpacity
            onPress={() => setIsDuplicateModalOpen(true)}
            className="bg-white/20 p-2 rounded-full"
          >
            <Ionicons name="copy-outline" size={20} color="white" />
          </TouchableOpacity>
        </View>
        
        <View className="flex-row justify-between items-end mb-6">
          <View>
            <Text className="text-primary-100 mb-1">{t('budgetPlans.totalIncome')}</Text>
            <Text 
              className="text-3xl font-bold text-white"
              numberOfLines={1}
              ellipsizeMode="tail"
              style={{ maxWidth: 200 }}
            >
              {formatCurrency(formatWithTwoDecimals(totalIncome), planDetails?.currency)}
            </Text>
          </View>
          
          <View className="items-end">
            <Text className="text-primary-100 mb-1">{t('budgetPlans.remaining')}</Text>
            <Text 
              className={`text-lg font-bold ${remaining >= 0 ? 'text-white' : 'text-red-300'}`}
              numberOfLines={1}
              ellipsizeMode="tail"
              style={{ maxWidth: 150 }}
            >
              {formatCurrency(formatWithTwoDecimals(remaining), planDetails?.currency)}
            </Text>
          </View>
        </View>
        
        {/* Budget progress */}
        <View className="bg-white/10 p-3 rounded-xl">
          <View className="flex-row justify-between mb-2">
            <Text className="text-primary-100">{t('budgetPlans.allocationProgress')}</Text>
            <Text 
              className="text-white font-medium"
              numberOfLines={1}
              ellipsizeMode="tail"
            >
              {formatWithTwoDecimals(totalIncome > 0 ? (totalAllocated / totalIncome) * 100 : 0)}%
            </Text>
          </View>
          
          <View className="h-3 bg-white/10 rounded-full overflow-hidden">
            <View 
              className="h-3 bg-primary-400 rounded-full" 
              style={{ width: `${Math.min(100, totalIncome > 0 ? (totalAllocated / totalIncome) * 100 : 0)}%` }} 
            />
          </View>
          
          <View className="flex-row justify-between mt-3">
            <View className="items-center">
              <Text className="text-xs text-primary-100">{t('budgetPlans.needs')}</Text>
              <Text 
                className="text-sm font-semibold text-white"
                numberOfLines={1}
                ellipsizeMode="tail"
              >
                {formatWithTwoDecimals(needsPercentage)}%
              </Text>
            </View>
            <View className="items-center">
              <Text className="text-xs text-primary-100">{t('budgetPlans.wants')}</Text>
              <Text 
                className="text-sm font-semibold text-white"
                numberOfLines={1}
                ellipsizeMode="tail"
              >
                {formatWithTwoDecimals(wantsPercentage)}%
              </Text>
            </View>
            <View className="items-center">
              <Text className="text-xs text-primary-100">{t('budgetPlans.savings')}</Text>
              <Text 
                className="text-sm font-semibold text-white"
                numberOfLines={1}
                ellipsizeMode="tail"
              >
                {formatWithTwoDecimals(savingsPercentage)}%
              </Text>
            </View>
          </View>
        </View>
      </View>
      
      {/* Tab Navigation */}
      <View className="px-4 -mt-4 z-10">
        <View className="neomorphic rounded-xl shadow-md">
          <View className="flex-row justify-between">
            {(['overview', 'needs', 'wants', 'savings', 'incomes'] as TabType[]).map((tab) => (
              <TouchableOpacity
                key={tab}
                onPress={() => setActiveTab(tab)}
                className={`flex-1 py-3 ${activeTab === tab ? 'bg-primary-50' : ''} 
                  ${tab === 'overview' ? 'rounded-l-xl' : ''}
                  ${tab === 'incomes' ? 'rounded-r-xl' : ''}
                `}
              >
                <Text 
                  className={`text-center text-xs font-medium ${
                    activeTab === tab ? 'text-primary-600' : 'text-text-secondary'
                  }`}
                >
                  {t(`budgetPlans.tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`)}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      </View>
      
      <ScrollView 
        className="flex-1 px-4 pt-6"
        contentContainerStyle={{ paddingBottom: 80 }}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {activeTab === 'overview' && (
          <View className="space-y-6">
            {/* Budget Summary Card */}
            <View className="card">
              <View className="card-content">
                <Text className="text-xl font-semibold text-text-primary mb-4">{t('budgetPlans.budgetSummary')}</Text>
                
                <View className="items-center py-4">
                  <BudgetItemPieChart data={chartData} />
                </View>
                
                <View className="flex-row flex-wrap mt-4">
                  <View className="w-1/3 pr-2 mb-4">
                    <View className="bg-green-50 p-3 rounded-lg items-center">
                      <Text className="text-green-600 font-medium">{t('budgetPlans.needs')}</Text>
                      <Text 
                        className="text-lg font-bold text-text-primary" 
                        numberOfLines={1} 
                        ellipsizeMode="tail" 
                        style={{ maxWidth: '95%' }}
                      >
                        {formatCurrency(formatWithTwoDecimals(totalNeeds), planDetails?.currency)}
                      </Text>
                      <Text className="text-xs text-text-secondary">
                        {formatWithTwoDecimals(needsPercentage)}%
                      </Text>
                    </View>
                  </View>
                  
                  <View className="w-1/3 px-1 mb-4">
                    <View className="bg-blue-50 p-3 rounded-lg items-center">
                      <Text className="text-blue-600 font-medium">{t('budgetPlans.wants')}</Text>
                      <Text 
                        className="text-lg font-bold text-text-primary" 
                        numberOfLines={1} 
                        ellipsizeMode="tail" 
                        style={{ maxWidth: '95%' }}
                      >
                        {formatCurrency(formatWithTwoDecimals(totalWants), planDetails?.currency)}
                      </Text>
                      <Text className="text-xs text-text-secondary">
                        {formatWithTwoDecimals(wantsPercentage)}%
                      </Text>
                    </View>
                  </View>
                  
                  <View className="w-1/3 pl-2 mb-4">
                    <View className="bg-amber-50 p-3 rounded-lg items-center">
                      <Text className="text-amber-600 font-medium">{t('budgetPlans.savings')}</Text>
                      <Text 
                        className="text-lg font-bold text-text-primary" 
                        numberOfLines={1} 
                        ellipsizeMode="tail" 
                        style={{ maxWidth: '95%' }}
                      >
                        {formatCurrency(formatWithTwoDecimals(totalSavings), planDetails?.currency)}
                      </Text>
                      <Text className="text-xs text-text-secondary">
                        {formatWithTwoDecimals(savingsPercentage)}%
                      </Text>
                    </View>
                  </View>
                </View>
                
                <View className="bg-primary-50 rounded-lg p-4 mt-2">
                  <View className="flex-row justify-between mb-1">
                    <Text className="text-primary-700 font-medium">{t('budgetPlans.totalAllocated')}</Text>
                    <Text 
                      className="font-bold text-primary-700"
                      numberOfLines={1}
                      ellipsizeMode="tail"
                      style={{ maxWidth: 150 }}
                    >
                      {formatCurrency(formatWithTwoDecimals(totalAllocated), planDetails?.currency)}
                    </Text>
                  </View>
                  <View className="flex-row justify-between">
                    <Text className={`font-medium ${remaining >= 0 ? 'text-primary-700' : 'text-red-600'}`}>
                      {t('budgetPlans.remainingToAllocate')}
                    </Text>
                    <Text 
                      className={`font-bold ${remaining >= 0 ? 'text-primary-700' : 'text-red-600'}`}
                      numberOfLines={1}
                      ellipsizeMode="tail"
                      style={{ maxWidth: 150 }}
                    >
                      {formatCurrency(formatWithTwoDecimals(remaining), planDetails?.currency)}
                    </Text>
                  </View>
                </View>
              </View>
            </View>
            
            {/* Income Sources Card */}
            <View className="card">
              <View className="card-content">
                <View className="flex-row justify-between items-center mb-4">
                  <View className="flex-row items-center">
                    <View className="w-10 h-10 rounded-full bg-purple-100 items-center justify-center mr-3">
                      <Ionicons name="wallet-outline" size={20} color="#9333ea" />
                    </View>
                    <Text className="text-lg font-semibold text-text-primary">{t('budgetPlans.incomeSources')}</Text>
                  </View>
                  
                  <TouchableOpacity
                    onPress={() => handleOpenAddModal('income')}
                    className="bg-primary-100 p-3 rounded-full"
                    disabled={loading}
                  >
                    <Ionicons name="add" size={18} color="#0c6cf2" />
                  </TouchableOpacity>
                </View>
                
                {incomes?.length === 0 ? (
                  <View className="neomorphic-inset p-6 rounded-lg items-center">
                    <Ionicons name="wallet-outline" size={32} color="#d1d5db" />
                    <Text className="text-gray-400 mt-2 mb-4">{t('budgetPlans.noIncomes')}</Text>
                    <TouchableOpacity
                      onPress={() => handleOpenAddModal('income')}
                      className="px-4 py-2 bg-purple-100 rounded-lg"
                    >
                      <Text className="text-purple-700">{t('budgetPlans.addIncome')}</Text>
                    </TouchableOpacity>
                  </View>
                ) : (
                  <View className="neomorphic-inset p-3 rounded-lg">
                    <View className="space-y-3">
                      {incomes?.map((income) => (
                        <View key={income.uuid} className="flex-row justify-between items-center p-2">
                          <View className="flex-row items-center">
                            <View className="w-8 h-8 rounded-full bg-purple-100 items-center justify-center mr-2">
                              <Text className="text-purple-700 font-medium">
                                {income.incomeName.charAt(0).toUpperCase()}
                              </Text>
                            </View>
                            <Text 
                              className="font-medium text-text-primary"
                              numberOfLines={1}
                              ellipsizeMode="tail"
                              style={{ maxWidth: 150 }}
                            >
                              {income.incomeName}
                            </Text>
                          </View>
                          <Text 
                            className="font-bold text-primary-600"
                            numberOfLines={1}
                            ellipsizeMode="tail"
                            style={{ maxWidth: 120 }}
                          >
                            {formatCurrency(formatWithTwoDecimals(parseFloat(income.incomeAmount)), planDetails?.currency)}
                          </Text>
                        </View>
                      ))}
                    </View>
                    
                    <View className="mt-3 pt-3 border-t border-gray-200">
                      <View className="flex-row justify-between">
                        <Text className="font-semibold text-text-primary">{t('budgetPlans.totalIncome')}</Text>
                        <Text 
                          className="text-xl font-bold text-primary-600"
                          numberOfLines={1}
                          ellipsizeMode="tail"
                          style={{ maxWidth: 150 }}
                        >
                          {formatCurrency(formatWithTwoDecimals(totalIncome), planDetails?.currency)}
                        </Text>
                      </View>
                    </View>
                  </View>
                )}
              </View>
            </View>
            
            {/* Budget Health Card */}
            {totalIncome > 0 && (
              <View className="card">
                <View className="card-content">
                  <Text className="text-lg font-semibold text-text-primary mb-4">{t('budgetPlans.budgetHealth')}</Text>
                  
                  <View className="bg-secondary-900 rounded-lg p-4 mb-4">
                    <Text className="text-white font-medium mb-1">{t('budgetPlans.fiftyThirtyTwentyComparison')}</Text>
                    <Text className="text-secondary-300 text-xs mb-3">
                      {t('budgetPlans.idealAllocation')}
                    </Text>
                    
                    <View className="space-y-3">
                      <View>
                        <View className="flex-row justify-between mb-1">
                          <Text className="text-green-400 text-xs">{t('budgetPlans.needs')}</Text>
                          <Text 
                            className="text-green-400 text-xs"
                            numberOfLines={1}
                            ellipsizeMode="tail"
                          >
                            {formatWithTwoDecimals(needsPercentage)}% {t('common.vs')} 50%
                          </Text>
                        </View>
                        <View className="h-2 bg-secondary-800 rounded-full overflow-hidden">
                          <View 
                            className="h-2 bg-green-500 rounded-full" 
                            style={{ width: `${Math.min(100, needsPercentage)}%` }} 
                          />
                        </View>
                      </View>
                      
                      <View>
                        <View className="flex-row justify-between mb-1">
                          <Text className="text-blue-400 text-xs">{t('budgetPlans.wants')}</Text>
                          <Text 
                            className="text-blue-400 text-xs"
                            numberOfLines={1}
                            ellipsizeMode="tail"
                          >
                            {formatWithTwoDecimals(wantsPercentage)}% {t('common.vs')} 30%
                          </Text>
                        </View>
                        <View className="h-2 bg-secondary-800 rounded-full overflow-hidden">
                          <View 
                            className="h-2 bg-blue-500 rounded-full" 
                            style={{ width: `${Math.min(100, wantsPercentage)}%` }} 
                          />
                        </View>
                      </View>
                      
                      <View>
                        <View className="flex-row justify-between mb-1">
                          <Text className="text-amber-400 text-xs">{t('budgetPlans.savings')}</Text>
                          <Text 
                            className="text-amber-400 text-xs"
                            numberOfLines={1}
                            ellipsizeMode="tail"
                          >
                            {formatWithTwoDecimals(savingsPercentage)}% {t('common.vs')} 20%
                          </Text>
                        </View>
                        <View className="h-2 bg-secondary-800 rounded-full overflow-hidden">
                          <View 
                            className="h-2 bg-amber-500 rounded-full" 
                            style={{ width: `${Math.min(100, savingsPercentage)}%` }} 
                          />
                        </View>
                      </View>
                    </View>
                  </View>
                </View>
              </View>
            )}
          </View>
        )}
        
        {activeTab === 'needs' && renderItemList('need', needs)}
        {activeTab === 'wants' && renderItemList('want', wants)}
        {activeTab === 'savings' && renderItemList('saving', savings)}
        {activeTab === 'incomes' && renderItemList('income', incomes)}
      </ScrollView>
      
      {/* Floating Action Button */}
      <View className="absolute bottom-6 right-6">
        <TouchableOpacity
          onPress={() => handleOpenAddModal(activeTab === 'overview' ? 'income' : activeTab.slice(0, -1) as any)}
          className="bg-primary-600 w-14 h-14 rounded-full shadow-lg items-center justify-center"
        >
          <Ionicons name="add" size={28} color="white" />
        </TouchableOpacity>
      </View>
      
      {/* Add Item Modal */}
      {isAddModalOpen && (
        <BudgetItemModal
          isVisible={isAddModalOpen}
          onClose={() => setIsAddModalOpen(false)}
          onSubmit={handleAddItem}
          title={t(`budgetPlans.add${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`)}
          itemType={currentItemType}
          categories={
            currentItemType === 'need'
              ? needsCategories
              : currentItemType === 'want'
                ? wantsCategories
                : currentItemType === 'saving'
                  ? savingsCategories
                  : incomesCategories
          }
        />
      )}
      
      {/* Edit Item Modal */}
      {isEditModalOpen && currentItem && (
        <BudgetItemModal
          isVisible={isEditModalOpen}
          onClose={() => setIsEditModalOpen(false)}
          onSubmit={handleEditItem}
          title={t(`budgetPlans.edit${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`)}
          initialName={currentItem.name}
          initialAmount={currentItem.amount}
          initialCategory={currentItem.category}
          isEdit={true}
          itemType={currentItemType}
          categories={
            currentItemType === 'need'
              ? needsCategories
              : currentItemType === 'want'
                ? wantsCategories
                : currentItemType === 'saving'
                  ? savingsCategories
                  : incomesCategories
          }
        />
      )}
      
      {/* Delete Confirmation Modal */}
      {isDeleteModalOpen && currentItem && (
        <DeleteConfirmationModal
          visible={isDeleteModalOpen}
          onClose={() => setIsDeleteModalOpen(false)}
          onConfirm={handleDeleteItem}
          name={currentItem.name}
          message={t('modals.deleteConfirmation', { name: currentItem.name })}
        />
      )}
      
      {/* Duplicate Budget Plan Modal */}
      <DuplicateBudgetPlanModal
        visible={isDuplicateModalOpen}
        onClose={() => setIsDuplicateModalOpen(false)}
        sourceBudgetPlanId={uuid}
        sourceMonth={sourceMonth}
        sourceYear={sourceYear}
      />
    </View>
    </SwipeBackWrapper>
  );
}