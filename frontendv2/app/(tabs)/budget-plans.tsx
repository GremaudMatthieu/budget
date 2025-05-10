import React, { useState, useEffect, useCallback, useRef } from 'react';
import { 
  View, 
  Text, 
  ScrollView, 
  TouchableOpacity, 
  ActivityIndicator,
  RefreshControl,
  Dimensions,
  Animated,
  Platform
} from 'react-native';
import { useRouter } from 'expo-router';
import { useFocusEffect } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { LinearGradient } from 'expo-linear-gradient';
import { useBudget } from '@/contexts/BudgetContext';
import { useErrorContext } from '@/contexts/ErrorContext';
import { formatCurrency } from '@/utils/currencyUtils';
import { normalizeMonthYear, createUtcSafeDate, getMonthYearKey, formatMonthYear } from '@/utils/dateUtils';
import DuplicateBudgetPlanModal from '@/components/modals/DuplicateBudgetPlanModal';
import { useTranslation } from '@/utils/useTranslation';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';

export default function BudgetPlansScreen() {
  const router = useRouter();
  const { setError } = useErrorContext();
  const { t } = useTranslation();
  const { 
    budgetPlansCalendar,
    loading,
    fetchBudgetPlansCalendar,
    clearSelectedBudgetPlan
  } = useBudget();

  const [currentYear, setCurrentYear] = useState(new Date().getFullYear());
  const [refreshing, setRefreshing] = useState(false);
  const screenWidth = Dimensions.get('window').width;
  const isTablet = screenWidth > 768;
  
  // Load calendar data on initial mount
  useEffect(() => {
    loadCalendarData();
  }, [currentYear]);

  // Function to load calendar data
  const loadCalendarData = async () => {
    try {
      await fetchBudgetPlansCalendar(currentYear);
    } catch (err) {
      console.error('Error loading budget plans calendar:', err);
      setError('Failed to load budget plans calendar');
    }
  };

  // Always fetch latest data when page is focused
  useFocusEffect(
    React.useCallback(() => {
      loadCalendarData();
    }, [currentYear])
  );

  // Handle refresh
  const handleRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadCalendarData();
    setRefreshing(false);
  }, [currentYear]);

  // Month selection handler
  const handleMonthSelect = (year: number, month: number) => {
    const budgetPlanId = budgetPlansCalendar?.[year]?.[month]?.uuid;
    
    if (budgetPlanId) {
      // Navigate to budget plan details if exists
      router.push(`/budget-plans/${budgetPlanId}`);
    } else {
      // Navigate to create budget plan screen
      router.push({
        pathname: '/budget-plans/create',
        params: { year, month }
      });
    }
  };

  // Year navigation handlers
  const handlePreviousYear = () => {
    setCurrentYear(prev => prev - 1);
  };

  const handleNextYear = () => {
    setCurrentYear(prev => prev + 1);
  };

  // Check if a month has a budget plan
  const hasBudgetPlan = (year: number, month: number): boolean => {
    return !!budgetPlansCalendar?.[year]?.[month]?.uuid;
  };

  // Month names
  const months = [
    t('months.january'), t('months.february'), t('months.march'), 
    t('months.april'), t('months.may'), t('months.june'), 
    t('months.july'), t('months.august'), t('months.september'), 
    t('months.october'), t('months.november'), t('months.december')
  ];

  // Get month data
  const getMonthData = (year: number, month: number) => {
    return budgetPlansCalendar?.[year]?.[month];
  };

  // Get budget summary data from new API response
  const budgetSummary = budgetPlansCalendar?.budgetSummary;
  const rule5030 = budgetSummary?.['50/30/20Rule'];
  const yearlyTotals = budgetSummary?.yearlyTotals;

  // Format number to have exactly 2 decimal places
  const formatWithTwoDecimals = (num: number): number => {
    return Math.round(num * 100) / 100;
  };

  // Render 50/30/20 rule comparison if data available
  const renderBudgetSummary = () => {
    if (!budgetSummary || !rule5030 || !yearlyTotals || yearlyTotals.income <= 0) return null;
    
    return (
      <View className="mb-6">
        <Text className="text-xl font-semibold text-secondary-800 mb-4">{t('budgetPlans.yearlySummary')}</Text>
        <View className="card">
          <View className="card-content">
            <Text className="text-lg font-semibold text-text-primary mb-2">{currentYear} {t('budgetPlans.overview')}</Text>
            
            <View className="flex-row justify-between items-center mb-2">
              <Text className="text-sm text-secondary-600">{t('budgetPlans.totalIncome')}</Text>
              <Text 
                className="font-semibold text-text-primary break-all" 
                style={{ fontSize: yearlyTotals.income > 9999999 ? 14 : 16 }}
              >
                {formatCurrency(formatWithTwoDecimals(yearlyTotals.income))}
              </Text>
            </View>
            
            <View className="flex-row justify-between items-center mb-4">
              <Text className="text-sm text-secondary-600">{t('budgetPlans.totalAllocated')}</Text>
              <View className="flex-row items-center break-all">
                <Text 
                  className="font-semibold text-text-primary break-all" 
                  style={{ fontSize: yearlyTotals.allocated > 9999999 ? 14 : 16 }}
                >
                  {formatCurrency(formatWithTwoDecimals(yearlyTotals.allocated))}
                </Text>
                <Text className="text-xs text-secondary-500 ml-1">
                  ({formatWithTwoDecimals((yearlyTotals.allocated / yearlyTotals.income) * 100)}%)
                </Text>
              </View>
            </View>
            
            <Text className="text-sm font-medium text-secondary-700 mb-2">
              {t('budgetPlans.ruleVsBudget')}
            </Text>
            
            <View className="mb-1">
              <View className="flex-row justify-between">
                <Text className="text-xs text-secondary-600">{t('budgetPlans.needs')}</Text>
                <Text className="text-xs text-secondary-600">
                  {formatWithTwoDecimals(rule5030.current.needs)}% vs {rule5030.recommended.needs}%
                </Text>
              </View>
              <View className="flex-row h-2 mt-1 bg-gray-200 rounded-full overflow-hidden">
                <View 
                  style={{ width: `${rule5030.current.needs}%` }} 
                  className="bg-green-500 rounded-full"
                />
                <View 
                  style={{ width: `${Math.max(0, rule5030.recommended.needs - rule5030.current.needs)}%` }} 
                  className="bg-green-300 opacity-40"
                />
              </View>
            </View>
            
            <View className="mb-1">
              <View className="flex-row justify-between">
                <Text className="text-xs text-secondary-600">{t('budgetPlans.wants')}</Text>
                <Text className="text-xs text-secondary-600">
                  {formatWithTwoDecimals(rule5030.current.wants)}% vs {rule5030.recommended.wants}%
                </Text>
              </View>
              <View className="flex-row h-2 mt-1 bg-gray-200 rounded-full overflow-hidden">
                <View 
                  style={{ width: `${rule5030.current.wants}%` }} 
                  className="bg-blue-500 rounded-full"
                />
                <View 
                  style={{ width: `${Math.max(0, rule5030.recommended.wants - rule5030.current.wants)}%` }} 
                  className="bg-blue-300 opacity-40"
                />
              </View>
            </View>
            
            <View className="mb-1">
              <View className="flex-row justify-between">
                <Text className="text-xs text-secondary-600">{t('budgetPlans.savings')}</Text>
                <Text className="text-xs text-secondary-600">
                  {formatWithTwoDecimals(rule5030.current.savings)}% vs {rule5030.recommended.savings}%
                </Text>
              </View>
              <View className="flex-row h-2 mt-1 bg-gray-200 rounded-full overflow-hidden">
                <View 
                  style={{ width: `${rule5030.current.savings}%` }} 
                  className="bg-amber-500 rounded-full"
                />
                <View 
                  style={{ width: `${Math.max(0, rule5030.recommended.savings - rule5030.current.savings)}%` }} 
                  className="bg-amber-300 opacity-40"
                />
              </View>
            </View>
          </View>
        </View>
      </View>
    );
  };

  // --- Dynamic 50/30/20 advice based on annual summary ---
  const getAnnualBudgetAdvice = () => {
    if (!budgetSummary || !rule5030 || !yearlyTotals || yearlyTotals.income <= 0) {
      return t('budgetPlans.advice.ideal');
    }
    const needs = rule5030.current.needs;
    const wants = rule5030.current.wants;
    const savings = rule5030.current.savings;
    const diffNeeds = Math.abs(needs - rule5030.recommended.needs);
    const diffWants = Math.abs(wants - rule5030.recommended.wants);
    const diffSavings = Math.abs(savings - rule5030.recommended.savings);
    if (diffNeeds < 5 && diffWants < 5 && diffSavings < 5) {
      return t('budgetPlans.advice.ideal');
    }
    if (needs > rule5030.recommended.needs + 5) {
      return t('budgetPlans.advice.needsHigh');
    }
    if (wants > rule5030.recommended.wants + 5) {
      return t('budgetPlans.advice.wantsHigh');
    }
    if (savings < rule5030.recommended.savings - 5) {
      return t('budgetPlans.advice.savingsLow');
    }
    return t('budgetPlans.advice.general');
  };

  // Helper: check if any budget plan exists for the current year
  const hasAnyBudgetPlanForYear = (year: number): boolean => {
    if (!budgetPlansCalendar || !budgetPlansCalendar[year]) return false;
    // Check if any month in the year has a budget plan (uuid present)
    return Object.values(budgetPlansCalendar[year]).some(
      (monthData: any) => monthData && monthData.uuid
    );
  };

  if (loading && !budgetPlansCalendar && !refreshing) {
    return (
      <View className="flex-1 justify-center items-center bg-background-light">
        <View className="w-16 h-16 rounded-full bg-primary-100 items-center justify-center">
          <ActivityIndicator size="large" color="#0c6cf2" />
        </View>
        <Text className="text-secondary-600 mt-4 font-medium">{t('budgetPlans.loading')}</Text>
      </View>
    );
  }
  
  const renderContent = () => {
    return (
      <View className="flex-1">
        {/* Year Navigation - Moved from header to main content */}
        <View className="flex-row items-center justify-center space-x-4 bg-primary-100 py-3 px-4 rounded-xl mb-6">
          <TouchableOpacity 
            onPress={handlePreviousYear}
            className="bg-white p-1.5 rounded-full shadow-sm"
          >
            <Ionicons name="chevron-back" size={18} color="#0284c7" />
          </TouchableOpacity>
          
          <Text className="text-lg font-bold text-primary-700">{currentYear}</Text>
          
          <TouchableOpacity 
            onPress={handleNextYear}
            className="bg-white p-1.5 rounded-full shadow-sm"
          >
            <Ionicons name="chevron-forward" size={18} color="#0284c7" />
          </TouchableOpacity>
        </View>
        
        {/* Current Month Preview */}
        <View className="mb-6">
          <Text className="text-xl font-semibold text-secondary-800 mb-4">{t('budgetPlans.thisMonth')}</Text>
          {hasBudgetPlan(new Date().getFullYear(), new Date().getMonth() + 1) ? (
            <TouchableOpacity 
              onPress={() => handleMonthSelect(new Date().getFullYear(), new Date().getMonth() + 1)}
              className="card"
            >
              <View className="card-content">
                <View className="flex-row justify-between items-center mb-3">
                  <View className="flex-row items-center">
                    <View className="w-12 h-12 rounded-full bg-primary-100 items-center justify-center mr-3">
                      <Ionicons name="calendar" size={24} color="#0c6cf2" />
                    </View>
                    <View>
                      <Text className="text-lg font-semibold text-text-primary">
                        {months[new Date().getMonth()]} {new Date().getFullYear()}
                      </Text>
                      <Text className="text-text-secondary">{t('budgetPlans.currentBudgetPlan')}</Text>
                    </View>
                  </View>
                  <Ionicons name="chevron-forward" size={24} color="#64748b" />
                </View>
                
                {/* Budget progress */}
                <View className="bg-primary-50 p-3 rounded-lg mb-3">
                  <View className="flex-row justify-between mb-2">
                    <Text className="text-text-secondary">{t('budgetPlans.totalIncome')}</Text>
                    <Text 
                      className="font-semibold break-all" 
                      style={{ fontSize: (getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.totalIncome || 0) > 9999999 ? 14 : 16 }}
                    >
                      {formatCurrency(
                        formatWithTwoDecimals(getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.totalIncome || 0), 
                        getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.currency || 'USD'
                      )}
                    </Text>
                  </View>
                  
                  <View className="flex-row justify-between mb-1">
                    <Text className="text-text-secondary">{t('budgetPlans.allocated')}</Text>
                    <Text 
                      className="font-semibold break-all" 
                      style={{ fontSize: (getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.totalAllocated || 0) > 9999999 ? 14 : 16 }}
                    >
                      {formatCurrency(
                        formatWithTwoDecimals(getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.totalAllocated || 0),
                        getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.currency || 'USD'
                      )}
                    </Text>
                  </View>
                </View>
                
                <View className="flex-row space-x-2">
                  <View className="flex-1 bg-green-100 p-2 rounded-lg items-center">
                    <Text className="text-xs text-green-700 font-medium">{t('budgetPlans.needs')}</Text>
                    <Text 
                      className="text-sm font-semibold"
                      numberOfLines={1}
                      ellipsizeMode="tail"
                    >
                      {formatWithTwoDecimals(getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.needsPercentage ?? 0)}%
                    </Text>
                  </View>
                  <View className="flex-1 bg-blue-100 p-2 rounded-lg items-center">
                    <Text className="text-xs text-blue-700 font-medium">{t('budgetPlans.wants')}</Text>
                    <Text 
                      className="text-sm font-semibold"
                      numberOfLines={1}
                      ellipsizeMode="tail"
                    >
                      {formatWithTwoDecimals(getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.wantsPercentage ?? 0)}%
                    </Text>
                  </View>
                  <View className="flex-1 bg-amber-100 p-2 rounded-lg items-center">
                    <Text className="text-xs text-amber-700 font-medium">{t('budgetPlans.savings')}</Text>
                    <Text 
                      className="text-sm font-semibold"
                      numberOfLines={1}
                      ellipsizeMode="tail"
                    >
                      {formatWithTwoDecimals(getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.savingsPercentage ?? 0)}%
                    </Text>
                  </View>
                </View>
              </View>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity 
              onPress={() => handleMonthSelect(new Date().getFullYear(), new Date().getMonth() + 1)}
              className="card"
              disabled={currentYear !== new Date().getFullYear()}
              style={currentYear !== new Date().getFullYear() ? { opacity: 0.5 } : {}}
            >
              <View className="card-content items-center py-6">
                <View className="w-16 h-16 bg-primary-100 rounded-full items-center justify-center mb-4">
                  <Ionicons name="add-outline" size={32} color="#0c6cf2" />
                </View>
                <Text className="text-lg font-semibold text-text-primary text-center mb-2">
                  {t('budgetPlans.createThisMonth')}
                </Text>
                <Text className="text-text-secondary text-center mb-4">
                  {t('budgetPlans.planIncomeExpenses', { month: months[new Date().getMonth()] })}
                </Text>
                {currentYear !== new Date().getFullYear() && (
                  <Text className="text-xs text-red-500 mt-1 text-center">{t('budgetPlans.createThisMonthDisabled')}</Text>
                )}
                <View className="bg-primary-600 px-4 py-2 rounded-xl">
                  <Text className="text-white font-medium">{t('budgetPlans.createBudgetPlan')}</Text>
                </View>
              </View>
            </TouchableOpacity>
          )}
        </View>
        
        {/* Yearly Budget Summary - New section using the new API data */}
        {renderBudgetSummary()}
        
        {/* 50/30/20 Rule Advice Card */}
        {hasAnyBudgetPlanForYear(currentYear) && (
          <View className="bg-primary-50 rounded-lg p-4 mb-4">
            <Text className="text-primary-700 font-semibold mb-1">{t('budgetPlans.advice.title')}</Text>
            <Text className="text-primary-700 text-sm">{getAnnualBudgetAdvice()}</Text>
          </View>
        )}
        
        {/* All Months Grid */}
        <View className="mb-6">
          <Text className="text-xl font-semibold text-secondary-800 mb-4">{t('budgetPlans.allMonths')}</Text>
          
          <View className={isTablet ? "grid-cols-4 flex-row flex-wrap" : "grid-cols-2 flex-row flex-wrap"}>
            {months.map((month, index) => {
              const monthNumber = index + 1;
              const hasData = hasBudgetPlan(currentYear, monthNumber);
              const isCurrentMonth = currentYear === new Date().getFullYear() && index === new Date().getMonth();
              const monthData = getMonthData(currentYear, monthNumber);
              
              return (
                <TouchableOpacity
                  key={month}
                  onPress={() => handleMonthSelect(currentYear, monthNumber)}
                  className={`${isTablet ? 'w-1/4 p-2' : 'w-1/2 p-2'}`}
                >
                  <View className={`rounded-xl overflow-hidden shadow-sm ${
                    isCurrentMonth ? 'border-2 border-primary-600' : ''
                  }`}>
                    <View className={`p-4 ${hasData ? 'bg-white' : 'bg-gray-50'}`}>
                      <View className="flex-row justify-between items-center mb-2">
                        <Text className={`font-medium ${hasData ? 'text-primary-600' : 'text-text-secondary'}`}>
                          {month.substring(0, 3)}
                        </Text>
                        {hasData ? (
                          <View className="bg-green-100 px-2 py-1 rounded-full">
                            <Text className="text-xs text-green-700">{t('budgetPlans.created')}</Text>
                          </View>
                        ) : (
                          <View className="bg-gray-200 px-2 py-1 rounded-full">
                            <Text className="text-xs text-gray-700">{t('budgetPlans.empty')}</Text>
                          </View>
                        )}
                      </View>
                      
                      {hasData && monthData ? (
                        <View>
                          <Text 
                            className="text-lg font-semibold text-text-primary break-all"
                            style={{ fontSize: (monthData.totalIncome || 0) > 9999999 ? 14 : 16 }}
                          >
                            {formatCurrency(
                              formatWithTwoDecimals(monthData.totalIncome || 0), 
                              monthData.currency || 'USD'
                            )}
                          </Text>
                          
                          {/* Add total allocated amount with color indicators */}
                          <View className="flex-row justify-between items-center mt-1">
                            <Text className="text-xs text-secondary-600">{t('budgetPlans.allocated')}:</Text>
                            <Text 
                              className={`text-xs font-medium break-all ${
                                (monthData.allocatedPercentage ?? 0) > 100 
                                  ? 'text-danger-600' 
                                  : (monthData.allocatedPercentage ?? 0) < 80 
                                    ? 'text-amber-600' 
                                    : 'text-success-600'
                              }`}
                              style={{ fontSize: (monthData.totalAllocated || 0) > 9999999 ? 13 : 14 }}
                            >
                              {formatCurrency(
                                formatWithTwoDecimals(monthData.totalAllocated || 0), 
                                monthData.currency || 'USD'
                              )}
                            </Text>
                          </View>
                          
                          {/* Progress bar with coloring based on allocation percentage */}
                          <View className="h-1 bg-gray-200 rounded-full mt-2">
                            <View 
                              className={`h-1 rounded-full ${
                                (monthData.allocatedPercentage ?? 0) > 100 
                                  ? 'bg-danger-500' 
                                  : (monthData.allocatedPercentage ?? 0) < 80 
                                    ? 'bg-amber-500' 
                                    : 'bg-success-500'
                              }`}
                              style={{ width: `${Math.min(100, (monthData.allocatedPercentage ?? 0))}%` }} 
                            />
                          </View>
                        </View>
                      ) : (
                        <View className="flex-row items-center mt-2">
                          <Ionicons name="add-circle-outline" size={16} color="#64748b" />
                          <Text className="text-text-secondary text-xs ml-1">{t('budgetPlans.createPlan')}</Text>
                        </View>
                      )}
                    </View>
                  </View>
                </TouchableOpacity>
              );
            })}
          </View>
        </View>
        
        {/* Guide Section */}
        <View className="bg-secondary-900 rounded-xl p-6 mb-6">
          <View className="flex-row items-start mb-4">
            <View className="w-10 h-10 rounded-full bg-secondary-800 items-center justify-center mr-3">
              <Ionicons name="information-circle" size={24} color="#fbbf24" />
            </View>
            <View className="flex-1">
              <Text className="text-xl font-semibold text-white mb-2">{t('budgetPlans.budgetGuide')}</Text>
              <Text className="text-secondary-200 mb-4">
                {t('budgetPlans.budgetGuideDescription')}
              </Text>
            </View>
          </View>
          
          <View className="bg-secondary-800 rounded-lg p-4 mb-4">
            <Text className="text-white font-medium mb-1">{t('budgetPlans.fiftyThirtyTwenty')}</Text>
            <Text className="text-secondary-300">
              {t('budgetPlans.fiftyThirtyTwentyDescription')}
            </Text>
          </View>
          
          <View className="flex-row space-x-2">
            <View className="flex-1 bg-secondary-800 rounded-lg p-3">
              <Text className="text-green-400 font-medium text-center">{t('budgetPlans.needs')}</Text>
              <Text className="text-white text-center text-xl font-bold">50%</Text>
            </View>
            <View className="flex-1 bg-secondary-800 rounded-lg p-3">
              <Text className="text-blue-400 font-medium text-center">{t('budgetPlans.wants')}</Text>
              <Text className="text-white text-center text-xl font-bold">30%</Text>
            </View>
            <View className="flex-1 bg-secondary-800 rounded-lg p-3">
              <Text className="text-amber-400 font-medium text-center">{t('budgetPlans.savings')}</Text>
              <Text className="text-white text-center text-xl font-bold">20%</Text>
            </View>
          </View>
        </View>
      </View>
    );
  };

  if (Platform.OS === 'web') {
    return (
      <div className="mb-8 mt-2">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <div>
            <h1 className="text-3xl font-bold text-slate-900 mb-1">{t('budgetPlans.title')}</h1>
            <p className="text-slate-500">{t('budgetPlans.subtitle')}</p>
          </div>
          {/* Place any important actions/info from the blue header here if needed */}
        </div>
        {renderContent()}
        <div className="h-32" />
      </div>
    );
  }

  return (
    <View className="flex-1">
      <AnimatedHeaderLayout
        title={t('budgetPlans.title')}
        subtitle={t('budgetPlans.subtitle')}
        headerHeight={130}
        onRefresh={handleRefresh}
        refreshing={refreshing}
      >
        {renderContent()}
      </AnimatedHeaderLayout>
    </View>
  );
}