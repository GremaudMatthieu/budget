import React, { useState, useEffect, useCallback } from 'react';
import { 
  View, 
  Text, 
  ScrollView, 
  TouchableOpacity, 
  ActivityIndicator,
  RefreshControl,
  Dimensions
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { useBudget } from '@/contexts/BudgetContext';
import { useErrorContext } from '@/contexts/ErrorContext';
import { formatCurrency } from '@/utils/currencyUtils';

export default function BudgetPlansScreen() {
  const router = useRouter();
  const { setError } = useErrorContext();
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

  // Handle refresh
  const handleRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadCalendarData();
    setRefreshing(false);
  }, []);

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
    return !!budgetPlansCalendar?.[year]?.[month];
  };

  // Month names
  const months = [
    'January', 'February', 'March', 'April', 'May', 'June', 
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  // Get month data
  const getMonthData = (year: number, month: number) => {
    return budgetPlansCalendar?.[year]?.[month];
  };

  if (loading && !budgetPlansCalendar && !refreshing) {
    return (
      <View className="flex-1 justify-center items-center bg-background-light">
        <View className="w-16 h-16 rounded-full bg-primary-100 items-center justify-center">
          <ActivityIndicator size="large" color="#0c6cf2" />
        </View>
        <Text className="text-secondary-600 mt-4 font-medium">Loading budget plans...</Text>
      </View>
    );
  }

  return (
    <View className="flex-1 bg-background-subtle">
      <StatusBar style="dark" />
      
      {/* Header Section - Similar to Envelopes */}
      <View className="bg-primary-600 px-6 pt-16 pb-12 rounded-b-3xl shadow-lg">
        <View className="flex-row justify-between items-center">
          <Text className="text-3xl font-bold text-white">Budget Plans</Text>
          <TouchableOpacity 
            onPress={() => {
              clearSelectedBudgetPlan();
              router.push({
                pathname: '/budget-plans/create',
                params: { 
                  year: new Date().getFullYear(), 
                  month: new Date().getMonth() + 1 
                }
              });
            }}
            className="bg-white/20 p-3 rounded-full"
          >
            <Ionicons name="add" size={24} color="white" />
          </TouchableOpacity>
        </View>
        <Text className="text-lg text-primary-100 mb-6">
          Plan and track your monthly budget allocations
        </Text>
        
        {/* Year Navigation */}
        <View className="flex-row items-center space-x-4 bg-white/20 p-3 rounded-xl">
          <TouchableOpacity 
            onPress={handlePreviousYear}
            className="bg-white/30 p-2 rounded-full"
          >
            <Ionicons name="chevron-back" size={20} color="white" />
          </TouchableOpacity>
          
          <Text className="flex-1 text-xl font-bold text-white text-center">{currentYear}</Text>
          
          <TouchableOpacity 
            onPress={handleNextYear}
            className="bg-white/30 p-2 rounded-full"
          >
            <Ionicons name="chevron-forward" size={20} color="white" />
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        className="flex-1"
        contentContainerStyle={{ padding: 16, paddingBottom: 80 }}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} />
        }
      >
        {/* Current Month Preview */}
        <View className="mb-6">
          <Text className="text-xl font-semibold text-secondary-800 mb-4">This Month</Text>
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
                      <Text className="text-text-secondary">Current budget plan</Text>
                    </View>
                  </View>
                  <Ionicons name="chevron-forward" size={24} color="#64748b" />
                </View>
                
                {/* Budget progress */}
                <View className="bg-primary-50 p-3 rounded-lg mb-3">
                  <View className="flex-row justify-between mb-2">
                    <Text className="text-text-secondary">Total Income</Text>
                    <Text className="font-semibold">
                      {formatCurrency(
                        getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.totalIncome || 0, 
                        getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.currency || 'USD'
                      )}
                    </Text>
                  </View>
                  
                  <View className="flex-row justify-between mb-1">
                    <Text className="text-text-secondary">Allocated</Text>
                    <Text className="font-semibold">
                      {formatCurrency(
                        getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.totalAllocated || 0,
                        getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.currency || 'USD'
                      )}
                    </Text>
                  </View>
                </View>
                
                <View className="flex-row space-x-2">
                  <View className="flex-1 bg-green-100 p-2 rounded-lg items-center">
                    <Text className="text-xs text-green-700 font-medium">Needs</Text>
                    <Text className="text-sm font-semibold">
                      {getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.needsPercentage || 0}%
                    </Text>
                  </View>
                  <View className="flex-1 bg-blue-100 p-2 rounded-lg items-center">
                    <Text className="text-xs text-blue-700 font-medium">Wants</Text>
                    <Text className="text-sm font-semibold">
                      {getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.wantsPercentage || 0}%
                    </Text>
                  </View>
                  <View className="flex-1 bg-amber-100 p-2 rounded-lg items-center">
                    <Text className="text-xs text-amber-700 font-medium">Savings</Text>
                    <Text className="text-sm font-semibold">
                      {getMonthData(new Date().getFullYear(), new Date().getMonth() + 1)?.savingsPercentage || 0}%
                    </Text>
                  </View>
                </View>
              </View>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity 
              onPress={() => handleMonthSelect(new Date().getFullYear(), new Date().getMonth() + 1)}
              className="card"
            >
              <View className="card-content items-center py-6">
                <View className="w-16 h-16 bg-primary-100 rounded-full items-center justify-center mb-4">
                  <Ionicons name="add-outline" size={32} color="#0c6cf2" />
                </View>
                <Text className="text-lg font-semibold text-text-primary text-center mb-2">
                  Create This Month's Budget
                </Text>
                <Text className="text-text-secondary text-center mb-4">
                  Plan your income and expenses for {months[new Date().getMonth()]}
                </Text>
                <View className="bg-primary-600 px-4 py-2 rounded-xl">
                  <Text className="text-white font-medium">Create Budget Plan</Text>
                </View>
              </View>
            </TouchableOpacity>
          )}
        </View>
        
        {/* All Months Grid */}
        <View className="mb-6">
          <Text className="text-xl font-semibold text-secondary-800 mb-4">All Months</Text>
          
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
                            <Text className="text-xs text-green-700">Created</Text>
                          </View>
                        ) : (
                          <View className="bg-gray-200 px-2 py-1 rounded-full">
                            <Text className="text-xs text-gray-700">Empty</Text>
                          </View>
                        )}
                      </View>
                      
                      {hasData && monthData ? (
                        <View>
                          <Text className="text-lg font-semibold text-text-primary">
                            {formatCurrency(monthData.totalIncome || 0, monthData.currency || 'USD')}
                          </Text>
                          <View className="h-1 bg-gray-200 rounded-full mt-2">
                            <View 
                              className="h-1 bg-primary-600 rounded-full" 
                              style={{ width: `${Math.min(100, monthData.allocatedPercentage || 0)}%` }} 
                            />
                          </View>
                        </View>
                      ) : (
                        <View className="flex-row items-center mt-2">
                          <Ionicons name="add-circle-outline" size={16} color="#64748b" />
                          <Text className="text-text-secondary text-xs ml-1">Create Plan</Text>
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
              <Text className="text-xl font-semibold text-white mb-2">Budget Planning Guide</Text>
              <Text className="text-secondary-200 mb-4">
                Effective budgeting helps you manage your finances and achieve your financial goals.
              </Text>
            </View>
          </View>
          
          <View className="bg-secondary-800 rounded-lg p-4 mb-4">
            <Text className="text-white font-medium mb-1">50/30/20 Rule</Text>
            <Text className="text-secondary-300">
              Allocate 50% to needs, 30% to wants, and 20% to savings for balanced budgeting.
            </Text>
          </View>
          
          <View className="flex-row space-x-2">
            <View className="flex-1 bg-secondary-800 rounded-lg p-3">
              <Text className="text-green-400 font-medium text-center">Needs</Text>
              <Text className="text-white text-center text-xl font-bold">50%</Text>
            </View>
            <View className="flex-1 bg-secondary-800 rounded-lg p-3">
              <Text className="text-blue-400 font-medium text-center">Wants</Text>
              <Text className="text-white text-center text-xl font-bold">30%</Text>
            </View>
            <View className="flex-1 bg-secondary-800 rounded-lg p-3">
              <Text className="text-amber-400 font-medium text-center">Savings</Text>
              <Text className="text-white text-center text-xl font-bold">20%</Text>
            </View>
          </View>
        </View>
        
        {/* Create New Budget Plan Button */}
        <TouchableOpacity
          onPress={() => {
            clearSelectedBudgetPlan();
            router.push({
              pathname: '/budget-plans/create',
              params: { 
                year: new Date().getFullYear(), 
                month: new Date().getMonth() + 1 
              }
            });
          }}
          className="bg-primary-600 py-4 px-6 rounded-xl mb-10 shadow-md items-center"
        >
          <Text className="text-white text-lg font-semibold">Create New Budget Plan</Text>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}