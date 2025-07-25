import React, { useRef, useEffect, useState } from 'react';
import { View, Text, TouchableOpacity, ActivityIndicator, Platform, ScrollView, Dimensions, Animated, Alert } from 'react-native';
import { useLocalSearchParams, Stack, useRouter, router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { useTranslation } from '@/utils/useTranslation';
import { useLanguage } from '@/contexts/LanguageContext';
import SwipeBackWrapper from '@/components/SwipeBackWrapper';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import BudgetItemCard from '@/components/card/BudgetItemCard';
import BudgetItemPieChart from '@/components/BudgetItemPieChart';
import BudgetItemModal from '@/components/modals/BudgetItemModal';
import DeleteConfirmationModal from '@/components/modals/DeleteConfirmationModal';
import DuplicateBudgetPlanModal from '@/components/modals/DuplicateBudgetPlanModal';
import { normalizeMonthYear } from '@/utils/dateUtils';
import { useBudgetPlanData } from '@/hooks/useBudgetPlanData';
import { formatCurrency } from '@/utils/currencyUtils';
import { useBudget } from '@/contexts/BudgetContext';
// @ts-ignore
import { TabView, TabBar, SceneMap } from 'react-native-tab-view';

export default function BudgetPlanDetailScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const { language } = useLanguage();
  const { t } = useTranslation();
  const uuid = params.uuid as string;
  const [hasAttemptedLoad, setHasAttemptedLoad] = useState(false);
  const {
    setActiveTab,
    refreshing,
    onRefresh,
    isAddModalOpen,
    setIsAddModalOpen,
    isEditModalOpen,
    setIsEditModalOpen,
    isDeleteModalOpen,
    setIsDeleteModalOpen,
    currentItemType,
    currentItem,
    handleOpenAddModal,
    handleOpenEditModal,
    handleOpenDeleteModal,
    handleAddItem,
    handleEditItem,
    handleDeleteItem,
    selectedBudgetPlan,
    loading,
    needsCategories,
    wantsCategories,
    savingsCategories,
    incomesCategories,
  } = useBudgetPlanData(uuid);
  const { refreshBudgetPlans, removeBudgetPlan, clearSelectedBudgetPlan } = useBudget();

  // Clear selected budget plan when UUID changes to prevent showing cached data
  useEffect(() => {
    if (uuid) {
      clearSelectedBudgetPlan();
      setHasAttemptedLoad(false);
    }
  }, [uuid, clearSelectedBudgetPlan]);

  // Helper functions for formatting and extracting data
  const formatWithTwoDecimals = (num: number): number => Math.round(num * 100) / 100;
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString(language === 'fr' ? 'fr-FR' : 'en-US', { year: 'numeric', month: 'long' });
  };
  const getFieldNames = (type: 'need' | 'want' | 'saving' | 'income') => {
    switch (type) {
      case 'need': return { name: 'needName', amount: 'needAmount', category: 'category' };
      case 'want': return { name: 'wantName', amount: 'wantAmount', category: 'category' };
      case 'saving': return { name: 'savingName', amount: 'savingAmount', category: 'category' };
      case 'income': return { name: 'incomeName', amount: 'incomeAmount', category: 'category' };
    }
  };
  const getIconAndColor = (type: 'need' | 'want' | 'saving' | 'income') => {
    switch (type) {
      case 'need': return { icon: 'cash-outline', color: '#16a34a', bgColor: 'bg-green-100', textColor: 'text-green-700' };
      case 'want': return { icon: 'cart-outline', color: '#0284c7', bgColor: 'bg-blue-100', textColor: 'text-blue-700' };
      case 'saving': return { icon: 'save-outline', color: '#ca8a04', bgColor: 'bg-amber-100', textColor: 'text-amber-700' };
      case 'income': return { icon: 'wallet-outline', color: '#9333ea', bgColor: 'bg-purple-100', textColor: 'text-purple-700' };
    }
  };
  const getCategoryName = (type: 'need' | 'want' | 'saving' | 'income', categoryId: string) => {
    let categoryList: any[] = [];
    switch (type) {
      case 'need': categoryList = needsCategories; break;
      case 'want': categoryList = wantsCategories; break;
      case 'saving': categoryList = savingsCategories; break;
      case 'income': categoryList = incomesCategories; break;
    }
    const category = categoryList.find(cat => cat.id === categoryId);
    return category ? category.name : categoryId;
  };

  // Extract budget plan details
  const { budgetPlan: planDetails, needs, wants, savings, incomes } = selectedBudgetPlan || {};
  const getBudgetPlanMonthYear = () => {
    if (!planDetails?.date) return { month: new Date().getMonth() + 1, year: new Date().getFullYear() };
    const date = new Date(planDetails.date);
    return normalizeMonthYear(date);
  };
  const { month: sourceMonth, year: sourceYear } = getBudgetPlanMonthYear();

  // Calculate totals
  const totalIncome = incomes?.reduce((sum, income) => sum + parseFloat(income.incomeAmount), 0) || 0;
  const totalNeeds = needs?.reduce((sum, need) => sum + parseFloat(need.needAmount), 0) || 0;
  const totalWants = wants?.reduce((sum, want) => sum + parseFloat(want.wantAmount), 0) || 0;
  const totalSavings = savings?.reduce((sum, saving) => sum + parseFloat(saving.savingAmount), 0) || 0;
  const needsPercentage = totalIncome > 0 ? (totalNeeds / totalIncome) * 100 : 0;
  const wantsPercentage = totalIncome > 0 ? (totalWants / totalIncome) * 100 : 0;
  const savingsPercentage = totalIncome > 0 ? (totalSavings / totalIncome) * 100 : 0;
  const totalAllocated = totalNeeds + totalWants + totalSavings;
  const remaining = totalIncome - totalAllocated;
  const chartData = [
    { name: t('budgetPlans.needs'), value: totalNeeds, color: '#4CAF50' },
    { name: t('budgetPlans.wants'), value: totalWants, color: '#2196F3' },
    { name: t('budgetPlans.savings'), value: totalSavings, color: '#FFC107' },
  ];

  const getBudgetAdvice = () => {
    if (totalIncome === 0) return t('budgetPlans.advice.noIncome');
    const diffNeeds = Math.abs(needsPercentage - 50);
    const diffWants = Math.abs(wantsPercentage - 30);
    const diffSavings = Math.abs(savingsPercentage - 20);
    if (diffNeeds < 5 && diffWants < 5 && diffSavings < 5) {
      return t('budgetPlans.advice.ideal');
    }
    if (needsPercentage > 55) {
      return t('budgetPlans.advice.needsHigh');
    }
    if (wantsPercentage > 35) {
      return t('budgetPlans.advice.wantsHigh');
    }
    if (savingsPercentage < 15) {
      return t('budgetPlans.advice.savingsLow');
    }
    return t('budgetPlans.advice.general');
  };

  // Helper to format subtitle for header with responsive line breaks if needed
  const getHeaderSubtitle = () => {
    const incomeStr = formatCurrency(formatWithTwoDecimals(totalIncome), planDetails?.currency);
    const remainingStr = formatCurrency(formatWithTwoDecimals(remaining), planDetails?.currency);
    // If either is very long, add a line break
    if (incomeStr.length > 12 || remainingStr.length > 12) {
      return `${incomeStr}\n${t('budgetPlans.remaining')}: ${remainingStr}`;
    }
    return `${incomeStr} • ${t('budgetPlans.remaining')}: ${remainingStr}`;
  };

  // Render a list of budget items using BudgetItemCard
  const renderItemList = (type: 'need' | 'want' | 'saving' | 'income', items: any[]) => {
    if (!items) return null;
    const fields = getFieldNames(type);
    const { icon, color, bgColor, textColor } = getIconAndColor(type);
    return (
      <View className="mb-6">
        <View className="card">
          <View className="card-content">
            <View className="flex-row justify-between items-center mb-4">
              <View className="flex-row items-center">
                <View className={`w-10 h-10 rounded-full ${bgColor} items-center justify-center mr-3`}>
                  <Ionicons name={icon as any} size={20} color={color} />
                </View>
                <View>
                  <Text className="text-lg font-semibold text-text-primary">
                    {type === 'need' ? t('budgetPlans.needs') : type === 'want' ? t('budgetPlans.wants') : type === 'saving' ? t('budgetPlans.savings') : t('budgetPlans.incomes')}
                  </Text>
                  {type !== 'income' && (
                    <Text className={`text-xs ${textColor}`} numberOfLines={1} ellipsizeMode="tail">
                      {formatWithTwoDecimals(type === 'need' ? needsPercentage : type === 'want' ? wantsPercentage : savingsPercentage)}% {t('budgetPlans.ofIncome')}
                    </Text>
                  )}
                </View>
              </View>
              {Platform.OS === 'web' ? (
                <button
                  onClick={() => handleOpenAddModal(type)}
                  className="bg-primary-100 p-3 rounded-full ml-2 hover:bg-primary-200 transition flex items-center justify-center"
                  aria-label={t('common.add')}
                  disabled={loading}
                  type="button"
                >
                  {loading ? (
                    <ActivityIndicator size="small" color="#0c6cf2" />
                  ) : (
                    <Ionicons name="add" size={20} color="#0c6cf2" />
                  )}
                </button>
              ) : (
              <TouchableOpacity
                onPress={() => handleOpenAddModal(type)}
                  className="bg-primary-100 p-3 rounded-full ml-2"
                  accessibilityRole="button"
                  accessibilityLabel={t('common.add')}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator size="small" color="#0c6cf2" />
                ) : (
                  <Ionicons name="add" size={20} color="#0c6cf2" />
                )}
              </TouchableOpacity>
              )}
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
                <Ionicons name={icon as any} size={32} color="#d1d5db" />
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
                  <BudgetItemCard
                    key={item.uuid}
                    name={item[fields.name]}
                    amount={item[fields.amount]}
                    category={getCategoryName(type, item[fields.category])}
                    icon={icon}
                    color={color}
                    bgColor={bgColor}
                    textColor={textColor}
                    currency={planDetails?.currency}
                    onEdit={() => handleOpenEditModal(type, item.uuid, item[fields.name], item[fields.amount], item[fields.category])}
                    onDelete={() => handleOpenDeleteModal(type, item.uuid, item[fields.name])}
                    loading={loading}
                  />
                ))}
              </View>
            )}
            <View className="mt-6 pt-4 border-t border-gray-200">
              <View className="flex-row justify-between items-center">
                <Text className="font-semibold text-text-primary">{t('common.total')}</Text>
                <Text className="text-xl font-bold text-primary-600" numberOfLines={1} ellipsizeMode="tail" style={{ maxWidth: 150 }}>
                  {formatCurrency(
                    formatWithTwoDecimals(
                      type === 'need' ? totalNeeds : type === 'want' ? totalWants : type === 'saving' ? totalSavings : totalIncome
                    ),
                    planDetails?.currency
                  )}
                </Text>
              </View>
              {type !== 'income' && (
                <View className="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <View className={`h-2 ${type === 'need' ? 'bg-green-500' : type === 'want' ? 'bg-blue-500' : 'bg-amber-500'}`}
                    style={{ width: `${Math.min(100, (type === 'need' ? needsPercentage : type === 'want' ? wantsPercentage : savingsPercentage))}%` }}
                  />
                </View>
              )}
            </View>
          </View>
        </View>
      </View>
    );
  };

  // --- TabView setup ---
  const initialLayout = { width: Dimensions.get('window').width };
  const [index, setIndex] = useState(0);
  const [fabLock, setFabLock] = useState(false);
  const [routes] = useState([
    { key: 'overview', title: t('budgetPlans.tabOverview') },
    { key: 'needs', title: t('budgetPlans.tabNeeds') },
    { key: 'wants', title: t('budgetPlans.tabWants') },
    { key: 'savings', title: t('budgetPlans.tabSavings') },
    { key: 'incomes', title: t('budgetPlans.tabIncomes') },
  ]);

  const handleIndexChange = (newIndex: number) => {
    setFabLock(true);
    setIndex(newIndex);
    setTimeout(() => setFabLock(false), 400);
  };

  // Determine FAB type from current index
  const key = routes[index].key;
  let type: 'need' | 'want' | 'saving' | 'income';
  switch (key) {
    case 'needs':
      type = 'need'; break;
    case 'wants':
      type = 'want'; break;
    case 'savings':
      type = 'saving'; break;
    case 'incomes':
      type = 'income'; break;
    default:
      type = 'income';
  }

  // --- Custom TabBar for Tailwind and accessibility ---
  const tabIcons: Record<string, string> = {
    overview: 'pie-chart-outline',
    needs: 'cash-outline',
    wants: 'cart-outline',
    savings: 'save-outline',
    incomes: 'wallet-outline',
  };
  const renderTabBar = (props: any) => (
    <TabBar
      {...props}
      scrollEnabled={true}
      indicatorStyle={{ backgroundColor: '#0c6cf2', height: 4, borderRadius: 2 }}
      style={{ backgroundColor: 'transparent', marginHorizontal: 0, marginTop: -19, elevation: 0, shadowColor: 'transparent', shadowOpacity: 0, shadowRadius: 0, shadowOffset: { width: 0, height: 0 }, borderRadius: 0 }}
      tabStyle={{ minWidth: 90, paddingHorizontal: 8, borderRadius: 24, flexDirection: 'row', alignItems: 'center', justifyContent: 'center' }}
      renderLabel={({ route, focused }: { route: { key: string; title: string }; focused: boolean }) => (
        <View className="flex-row items-center justify-center">
          <Ionicons
            name={tabIcons[route.key] as any}
            size={18}
            color={focused ? '#0c6cf2' : '#64748b'}
            style={{ marginRight: 4 }}
          />
          <Text
            style={{
              fontSize: 13,
              fontWeight: '600',
              color: focused ? '#0c6cf2' : '#64748b',
              maxWidth: 70,
            }}
            numberOfLines={1}
            ellipsizeMode="tail"
          >
            {route.title}
          </Text>
        </View>
      )}
      accessibilityRole="tablist"
      activeColor="#0c6cf2"
      inactiveColor="#64748b"
      pressColor="#e0e7ff"
    />
  );

  // --- Render functions for each tab ---
  const renderOverview = () => (
    <ScrollView className="px-2 pt-2 pb-8 w-full" contentContainerStyle={{ paddingBottom: 32 }}>
      <View className="w-full">
        {/* --- Compact Summary & Allocation Grid --- */}
        <View className="bg-white rounded-xl shadow-md px-2 py-2 mb-2">
          <Text className="text-base font-bold text-text-primary mb-2 text-center min-w-0 break-all" numberOfLines={2} ellipsizeMode="tail">{t('budgetPlans.budgetSummary')}</Text>
          <View className="flex-row gap-x-2 mb-2">
            {/* Needs */}
            <View className="flex-1 items-center justify-center py-1 px-0.5 min-w-0">
              <Ionicons name="cash-outline" size={15} color="#16a34a" className="mb-1" />
              <Text className="text-green-600 font-semibold text-[11px] mb-0.5">{t('budgetPlans.needs')}</Text>
              <Text className="text-sm font-bold text-text-primary text-center min-w-0 break-all" style={{ fontSize: totalNeeds > 9999999 ? 13 : 15 }}>
                {formatCurrency(formatWithTwoDecimals(totalNeeds), planDetails?.currency)}
              </Text>
              <Text className="text-[10px] text-text-secondary text-center mt-0.5">{formatWithTwoDecimals(needsPercentage)}%</Text>
            </View>
            {/* Wants */}
            <View className="flex-1 items-center justify-center py-1 px-0.5 min-w-0">
              <Ionicons name="cart-outline" size={15} color="#0284c7" className="mb-1" />
              <Text className="text-blue-600 font-semibold text-[11px] mb-0.5">{t('budgetPlans.wants')}</Text>
              <Text className="text-sm font-bold text-text-primary text-center min-w-0 break-all" style={{ fontSize: totalWants > 9999999 ? 13 : 15 }}>
                {formatCurrency(formatWithTwoDecimals(totalWants), planDetails?.currency)}
              </Text>
              <Text className="text-[10px] text-text-secondary text-center mt-0.5">{formatWithTwoDecimals(wantsPercentage)}%</Text>
            </View>
            {/* Savings */}
            <View className="flex-1 items-center justify-center py-1 px-0.5 min-w-0">
              <Ionicons name="save-outline" size={15} color="#ca8a04" className="mb-1" />
              <Text className="text-amber-600 font-semibold text-[11px] mb-0.5">{t('budgetPlans.savings')}</Text>
              <Text className="text-sm font-bold text-text-primary text-center min-w-0 break-all" style={{ fontSize: totalSavings > 9999999 ? 13 : 15 }}>
                {formatCurrency(formatWithTwoDecimals(totalSavings), planDetails?.currency)}
              </Text>
              <Text className="text-[10px] text-text-secondary text-center mt-0.5">{formatWithTwoDecimals(savingsPercentage)}%</Text>
            </View>
          </View>
          {/* --- Total/Remaining Row --- */}
          <View className="flex-row justify-between items-center border-t border-gray-200 pt-1 mt-1">
            <Text className="text-primary-700 font-semibold text-[11px]">{t('budgetPlans.totalAllocated')}</Text>
            <Text className="font-bold text-primary-700 text-sm text-right min-w-0 break-all" style={{ fontSize: totalAllocated > 9999999 ? 13 : 15 }}>
              {formatCurrency(formatWithTwoDecimals(totalAllocated), planDetails?.currency)}
            </Text>
          </View>
          <View className="flex-row justify-between items-center mt-0.5">
            <Text className={`font-semibold ${remaining >= 0 ? 'text-primary-700' : 'text-red-600'} text-[11px]`}>{t('budgetPlans.remainingToAllocate')}</Text>
            <Text className={`font-bold ${remaining >= 0 ? 'text-primary-700' : 'text-red-600'} text-sm text-right min-w-0 break-all`} style={{ fontSize: Math.abs(remaining) > 9999999 ? 13 : 15 }}>
              {formatCurrency(formatWithTwoDecimals(remaining), planDetails?.currency)}
            </Text>
          </View>
        </View>
        {/* --- Pie Chart & Legend (not boxed) --- */}
        <View className="w-full items-center justify-center mt-2 mb-2">
          <BudgetItemPieChart data={chartData} size={160} />
        </View>
        {/* --- Budget Health Section (not a card, just a section with divider) --- */}
        <View className="w-full mt-2 pt-4 border-t border-gray-200">
          <Text className="text-lg font-bold text-text-primary mb-2 text-center">{t('budgetPlans.budgetHealth')}</Text>
          <View className="bg-secondary-900 rounded-lg p-4 mb-4">
            <Text className="text-white font-medium mb-1">{t('budgetPlans.fiftyThirtyTwentyComparison')}</Text>
            <Text className="text-secondary-300 text-xs mb-3">{t('budgetPlans.idealAllocation')}</Text>
            <View className="space-y-3">
              <View>
                <View className="flex-row justify-between mb-1">
                  <Text className="text-green-400 text-xs">{t('budgetPlans.needs')}</Text>
                  <Text className="text-green-400 text-xs" numberOfLines={1} ellipsizeMode="tail">{formatWithTwoDecimals(needsPercentage)}% {t('common.vs')} 50%</Text>
                </View>
                <View className="h-2 bg-secondary-800 rounded-full overflow-hidden">
                  <View className="h-2 bg-green-500 rounded-full" style={{ width: `${Math.min(100, needsPercentage)}%` }} />
                </View>
              </View>
              <View>
                <View className="flex-row justify-between mb-1">
                  <Text className="text-blue-400 text-xs">{t('budgetPlans.wants')}</Text>
                  <Text className="text-blue-400 text-xs" numberOfLines={1} ellipsizeMode="tail">{formatWithTwoDecimals(wantsPercentage)}% {t('common.vs')} 30%</Text>
                </View>
                <View className="h-2 bg-secondary-800 rounded-full overflow-hidden">
                  <View className="h-2 bg-blue-500 rounded-full" style={{ width: `${Math.min(100, wantsPercentage)}%` }} />
                </View>
              </View>
              <View>
                <View className="flex-row justify-between mb-1">
                  <Text className="text-amber-400 text-xs">{t('budgetPlans.savings')}</Text>
                  <Text className="text-amber-400 text-xs" numberOfLines={1} ellipsizeMode="tail">{formatWithTwoDecimals(savingsPercentage)}% {t('common.vs')} 20%</Text>
                </View>
                <View className="h-2 bg-secondary-800 rounded-full overflow-hidden">
                  <View className="h-2 bg-amber-500 rounded-full" style={{ width: `${Math.min(100, savingsPercentage)}%` }} />
                </View>
              </View>
            </View>
          </View>
          {/* Budget advice */}
          <View className="bg-primary-50 rounded-lg p-4 mb-4">
            <Text className="text-primary-700 font-semibold mb-1">{t('budgetPlans.advice.title')}</Text>
            <Text className="text-primary-700 text-sm">{getBudgetAdvice()}</Text>
          </View>
        </View>
        {/* Delete Budget Plan Button (mobile only) */}
        {Platform.OS !== 'web' && (
          <View className="flex flex-col items-center w-full">
            <TouchableOpacity
              onPress={() => setIsDeletePlanModalOpen(true)}
              className="bg-red-100 rounded-xl p-4 items-center mt-6 mb-10"
              accessibilityRole="button"
              accessibilityLabel={t('budgetPlans.deletePlan')}
            >
              <Ionicons name="trash-outline" size={20} color="#dc2626" style={{ marginBottom: 2 }} />
              <Text className="text-red-700 font-semibold text-base">{t('budgetPlans.deletePlan')}</Text>
            </TouchableOpacity>
            <DeleteConfirmationModal
              visible={isDeletePlanModalOpen}
              onClose={() => setIsDeletePlanModalOpen(false)}
              onConfirm={handleDeleteBudgetPlan}
              name={planDetails?.date ? formatDate(planDetails.date) : t('budgetPlans.budgetPlan')}
              message={t('modals.deleteConfirmation', { name: planDetails?.date ? formatDate(planDetails.date) : t('budgetPlans.budgetPlan') })}
            />
          </View>
        )}
      </View>
    </ScrollView>
  );

  const renderCategoryTab = (
    type: 'need' | 'want' | 'saving' | 'income',
    items: any[],
    total: number,
    percentage: number,
    color: string,
    bgColor: string,
    textColor: string,
    icon: string,
    description: string,
    categories: any[],
  ) => (
    <ScrollView className="px-2 pt-2 pb-8 w-full" contentContainerStyle={{ paddingBottom: 32 }}>
      {/* --- Compact Summary --- */}
      <View className="bg-white rounded-xl shadow-md px-2 py-2 mb-2">
        <View className="flex-row items-center justify-between mb-2">
          <View className="flex-row items-center">
          <View className={`w-10 h-10 rounded-full ${bgColor} items-center justify-center mr-3`}>
            <Ionicons name={icon as any} size={22} color={color} />
          </View>
          <View>
            <Text className={`text-lg font-bold ${textColor}`}>{t(`budgetPlans.${type === 'saving' ? 'savings' : type + 's'}`)}</Text>
            {type !== 'income' && (
              <Text className={`text-xs ${textColor}`}>{formatWithTwoDecimals(percentage)}% {t('budgetPlans.ofIncome')}</Text>
            )}
          </View>
          </View>
          {/* Add button in card header for each category */}
          {Platform.OS === 'web' ? (
            <button
              onClick={() => handleOpenAddModal(type)}
              className="bg-primary-100 p-3 rounded-full ml-2 hover:bg-primary-200 transition flex items-center justify-center"
              aria-label={t('common.add')}
              disabled={loading}
              type="button"
            >
              {loading ? (
                <ActivityIndicator size="small" color="#0c6cf2" />
              ) : (
                <Ionicons name="add" size={20} color="#0c6cf2" />
              )}
            </button>
          ) : (
            <TouchableOpacity
              onPress={() => handleOpenAddModal(type)}
              className="bg-primary-100 p-3 rounded-full ml-2"
              accessibilityRole="button"
              accessibilityLabel={t('common.add')}
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator size="small" color="#0c6cf2" />
              ) : (
                <Ionicons name="add" size={20} color="#0c6cf2" />
              )}
            </TouchableOpacity>
          )}
        </View>
        <Text className="text-text-secondary text-xs mb-2 text-center">{description}</Text>
        <View className="flex-row justify-between items-center mt-1">
          <Text className="font-semibold text-text-primary text-xs">{t('common.total')}</Text>
          <Text className={`font-bold ${textColor} text-base text-right min-w-0 break-all`} style={{ fontSize: total > 9999999 ? 14 : 16 }}>
            {formatCurrency(formatWithTwoDecimals(total), planDetails?.currency)}
          </Text>
        </View>
        {type !== 'income' && (
          <View className="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
            <View className={`h-2 ${type === 'need' ? 'bg-green-500' : type === 'want' ? 'bg-blue-500' : 'bg-amber-500'}`}
              style={{ width: `${Math.min(100, percentage)}%` }}
            />
          </View>
        )}
      </View>
      {/* --- List of Items --- */}
      <View className="space-y-2 w-full mt-2">
        {items.length === 0 ? (
          <View className="py-6 items-center">
            <Ionicons name={icon as any} size={32} color="#d1d5db" />
            <Text className="text-gray-400 mt-2">{t('budgetPlans.no' + type.charAt(0).toUpperCase() + type.slice(1) + 's')}</Text>
          </View>
        ) : (
          items.map((item) => {
            const fields = getFieldNames(type);
            return (
              <BudgetItemCard
                key={item.uuid}
                name={item[fields.name]}
                amount={item[fields.amount]}
                category={getCategoryName(type, item[fields.category])}
                icon={icon}
                color={color}
                bgColor={bgColor}
                textColor={textColor}
                currency={planDetails?.currency}
                onEdit={() => handleOpenEditModal(type, item.uuid, item[fields.name], item[fields.amount], item[fields.category])}
                onDelete={() => handleOpenDeleteModal(type, item.uuid, item[fields.name])}
                loading={loading}
              />
            );
          })
        )}
      </View>
    </ScrollView>
  );

  const renderNeeds = () => renderCategoryTab(
    'need',
    needs ?? [],
    totalNeeds,
    needsPercentage,
    '#16a34a',
    'bg-green-100',
    'text-green-700',
    'cash-outline',
    t('budgetPlans.needsDescription'),
    needsCategories,
  );
  const renderWants = () => renderCategoryTab(
    'want',
    wants ?? [],
    totalWants,
    wantsPercentage,
    '#0284c7',
    'bg-blue-100',
    'text-blue-700',
    'cart-outline',
    t('budgetPlans.wantsDescription'),
    wantsCategories,
  );
  const renderSavings = () => renderCategoryTab(
    'saving',
    savings ?? [],
    totalSavings,
    savingsPercentage,
    '#ca8a04',
    'bg-amber-100',
    'text-amber-700',
    'save-outline',
    t('budgetPlans.savingsDescription'),
    savingsCategories,
  );
  const renderIncomes = () => renderCategoryTab(
    'income',
    incomes ?? [],
    totalIncome,
    0,
    '#9333ea',
    'bg-purple-100',
    'text-purple-700',
    'wallet-outline',
    t('budgetPlans.incomesDescription'),
    incomesCategories,
  );

  // --- Duplicate modal state ---
  const [isDuplicateModalOpen, setIsDuplicateModalOpen] = useState(false);
  const [isDeletePlanModalOpen, setIsDeletePlanModalOpen] = useState(false);

  // Handler to delete the budget plan
  const handleDeleteBudgetPlan = async () => {
    if (!selectedBudgetPlan?.budgetPlan?.uuid) return;
    try {
      await removeBudgetPlan(selectedBudgetPlan.budgetPlan.uuid);
      setIsDeletePlanModalOpen(false);
      router.push('/budget-plans');
    } catch (err) {
      // setError && setError(t('budgetPlans.failedToDeletePlan'));
    }
  };

  // Track when we've attempted to load data
  useEffect(() => {
    if (!loading && hasAttemptedLoad === false) {
      setHasAttemptedLoad(true);
    }
  }, [loading, hasAttemptedLoad]);

  // Redirect to not-found page if budget plan doesn't exist after loading
  useEffect(() => {
    // Only redirect if we've finished loading, attempted to load, and there's definitely no budget plan
    // Also ensure we have a valid UUID to prevent false positives
    if (!loading && hasAttemptedLoad && !selectedBudgetPlan && uuid && uuid.length > 0) {
      const timer = setTimeout(() => {
        console.log('Redirecting to not-found - no budget plan found after load attempt');
        router.push('/not-found');
      }, 200); // Slightly longer delay to prevent race conditions
      return () => clearTimeout(timer);
    }
  }, [loading, hasAttemptedLoad, selectedBudgetPlan, uuid]);

  if (loading && !selectedBudgetPlan) {
    return (
      <View className="flex-1 justify-center items-center bg-background-light">
        <View className="w-16 h-16 rounded-full bg-primary-100 items-center justify-center">
          <ActivityIndicator size="large" color="#0c6cf2" />
        </View>
        <Text className="text-secondary-600 mt-4 font-medium">{t('budgetPlans.loading')}</Text>
      </View>
    );
  }
  
  if (!selectedBudgetPlan) {
    // This shouldn't render because of the redirect effect above, but keep as fallback
    return null;
  }

  if (Platform.OS === 'web') {
    return (
      <div className="mb-8 mt-2 pb-32">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <div>
            <h1 className="text-3xl font-bold text-slate-900 mb-1">{planDetails?.date ? formatDate(planDetails.date) : t('budgetPlans.budgetPlan')}</h1>
            <p className="text-slate-500">{`${formatCurrency(formatWithTwoDecimals(totalIncome), planDetails?.currency)} • ${t('budgetPlans.remaining')}: ${formatCurrency(formatWithTwoDecimals(remaining), planDetails?.currency)}`}</p>
          </div>
          <button
            onClick={() => setIsDuplicateModalOpen(true)}
            className="flex-row items-center px-3 py-2 bg-primary-100 rounded-xl ml-2 hover:bg-primary-200 transition"
            style={{ display: 'flex' }}
          >
            <Ionicons name="copy-outline" size={18} color="#0284c7" style={{ marginRight: 6 }} />
            <span className="text-primary-700 font-semibold">{t('budgetPlans.duplicate')}</span>
          </button>
        </div>
        <div className="space-y-8">
          {renderOverview()}
          {renderNeeds()}
          {renderWants()}
          {renderSavings()}
          {renderIncomes()}
        </div>
        
        {/* Delete Budget Plan Button - Web (at bottom of page) */}
        <div className="flex flex-col items-center w-full mt-8 mb-4">
          <TouchableOpacity
            onPress={() => setIsDeletePlanModalOpen(true)}
            className="bg-red-100 rounded-xl p-4 items-center"
            accessibilityRole="button"
            accessibilityLabel={t('budgetPlans.deletePlan')}
            style={{ display: 'flex', justifyContent: 'center' }}
          >
            <Ionicons name="trash-outline" size={20} color="#dc2626" style={{ marginBottom: 2 }} />
            <Text className="text-red-700 font-semibold text-base">{t('budgetPlans.deletePlan')}</Text>
          </TouchableOpacity>
        </div>
        
        {/* Delete confirmation modal for web */}
        <DeleteConfirmationModal
          visible={isDeletePlanModalOpen}
          onClose={() => setIsDeletePlanModalOpen(false)}
          onConfirm={handleDeleteBudgetPlan}
          name={planDetails?.date ? formatDate(planDetails.date) : t('budgetPlans.budgetPlan')}
          message={t('modals.deleteConfirmation', { name: planDetails?.date ? formatDate(planDetails.date) : t('budgetPlans.budgetPlan') })}
        />
        {/* Modals */}
        {isAddModalOpen && (
          <BudgetItemModal
            isVisible={isAddModalOpen}
            onClose={() => setIsAddModalOpen(false)}
            onSubmit={async (name, amount, category) => {
              await handleAddItem(name, amount, category);
              await refreshBudgetPlans();
            }}
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
        {isEditModalOpen && currentItem && (
          <BudgetItemModal
            isVisible={isEditModalOpen}
            onClose={() => setIsEditModalOpen(false)}
            onSubmit={async (name, amount, category) => {
              await handleEditItem(name, amount, category);
              await refreshBudgetPlans();
            }}
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
        {isDeleteModalOpen && currentItem && (
          <DeleteConfirmationModal
            visible={isDeleteModalOpen}
            onClose={() => setIsDeleteModalOpen(false)}
            onConfirm={async () => {
              await handleDeleteItem();
              await refreshBudgetPlans();
            }}
            name={currentItem.name}
            message={t('modals.deleteConfirmation', { name: currentItem.name })}
          />
        )}
        <DuplicateBudgetPlanModal
          visible={isDuplicateModalOpen}
          onClose={() => setIsDuplicateModalOpen(false)}
          sourceBudgetPlanId={uuid}
          sourceMonth={sourceMonth}
          sourceYear={sourceYear}
        />
      </div>
    );
  }

  return (
    <SwipeBackWrapper hasScrollView>
      <View className="flex-1 bg-background-light">
        <Stack.Screen options={{ headerShown: false }} />
        <AnimatedHeaderLayout
          title={planDetails?.date ? formatDate(planDetails.date) : t('budgetPlans.budgetPlan')}
          subtitle={getHeaderSubtitle()}
          showBackButton={true}
          rightComponent={
            <TouchableOpacity
              onPress={() => setIsDuplicateModalOpen(true)}
              className="flex-row items-center px-3 py-2 bg-primary-100 rounded-xl ml-2"
              accessibilityRole="button"
              accessibilityLabel={t('budgetPlans.duplicate')}
              activeOpacity={0.8}
            >
              <Ionicons name="copy-outline" size={18} color="#0284c7" style={{ marginRight: 6 }} />
              <Text className="text-primary-700 font-semibold">{t('budgetPlans.duplicate')}</Text>
            </TouchableOpacity>
          }
          onRefresh={onRefresh}
          refreshing={refreshing}
          headerHeight={180}
        >
          {/* --- TabView for native swipeable tabs --- */}
          <TabView
            navigationState={{ index, routes }}
            renderScene={({ route }) => {
              switch (route.key) {
                case 'overview':
                  return renderOverview();
                case 'needs':
                  return renderNeeds();
                case 'wants':
                  return renderWants();
                case 'savings':
                  return renderSavings();
                case 'incomes':
                  return renderIncomes();
                default:
                  return null;
              }
            }}
            onIndexChange={handleIndexChange}
            initialLayout={initialLayout}
            renderTabBar={renderTabBar}
            swipeEnabled={true}
            lazy
            style={{ flex: 1 }}
          />
          {/* Add/Edit/Delete Modals (unchanged) */}
          {isAddModalOpen && (
            <BudgetItemModal
              isVisible={isAddModalOpen}
              onClose={() => setIsAddModalOpen(false)}
              onSubmit={async (name, amount, category) => {
                await handleAddItem(name, amount, category);
                await refreshBudgetPlans();
              }}
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
          {isEditModalOpen && currentItem && (
            <BudgetItemModal
              isVisible={isEditModalOpen}
              onClose={() => setIsEditModalOpen(false)}
              onSubmit={async (name, amount, category) => {
                await handleEditItem(name, amount, category);
                await refreshBudgetPlans();
              }}
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
          {isDeleteModalOpen && currentItem && (
            <DeleteConfirmationModal
              visible={isDeleteModalOpen}
              onClose={() => setIsDeleteModalOpen(false)}
              onConfirm={async () => {
                await handleDeleteItem();
                await refreshBudgetPlans();
              }}
              name={currentItem.name}
              message={t('modals.deleteConfirmation', { name: currentItem.name })}
            />
          )}
          {/* Duplicate Budget Plan Modal (restored) */}
          <DuplicateBudgetPlanModal
            visible={isDuplicateModalOpen}
            onClose={() => setIsDuplicateModalOpen(false)}
            sourceBudgetPlanId={uuid}
            sourceMonth={sourceMonth}
            sourceYear={sourceYear}
          />
        </AnimatedHeaderLayout>
      </View>
    </SwipeBackWrapper>
  );
}