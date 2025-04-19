import React, { useState, useEffect, useRef } from 'react';
import { View, Text, Modal, TouchableOpacity, ScrollView, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useBudget } from '@/contexts/BudgetContext';
import { createUtcSafeDate, formatMonthYear } from '@/utils/dateUtils';
import { useRouter } from 'expo-router';

interface DuplicateBudgetPlanModalProps {
  visible: boolean;
  onClose: () => void;
  sourceBudgetPlanId: string;
  sourceMonth: number;
  sourceYear: number;
}

const DuplicateBudgetPlanModal: React.FC<DuplicateBudgetPlanModalProps> = ({
  visible,
  onClose,
  sourceBudgetPlanId,
  sourceMonth,
  sourceYear
}) => {
  const router = useRouter();
  const [targetMonth, setTargetMonth] = useState(new Date().getMonth() + 1);
  const [targetYear, setTargetYear] = useState(new Date().getFullYear());
  const [loading, setLoading] = useState(false);
  const { budgetPlansCalendar, createBudgetPlanFromExisting, newlyCreatedBudgetPlanId } = useBudget();
  
  // Track if the component is mounted
  const isMounted = useRef(true);
  
  // Track if we've already handled this new budget plan ID
  const handledBudgetPlanId = useRef<string | null>(null);
  
  // Years to show in the selector (current year and next 2 years)
  const years = [new Date().getFullYear(), new Date().getFullYear() + 1, new Date().getFullYear() + 2];
  
  // Month names
  const months = [
    'January', 'February', 'March', 'April', 'May', 'June', 
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  
  // Set up cleanup on unmount
  useEffect(() => {
    return () => {
      isMounted.current = false;
    };
  }, []);
  
  // Check if the selected month already has a budget plan
  const isMonthOccupied = (year: number, month: number): boolean => {
    return !!budgetPlansCalendar?.[year]?.[month]?.uuid;
  };

  // Navigate to newly created budget plan when it's ready
  useEffect(() => {
    if (newlyCreatedBudgetPlanId && 
        visible && 
        loading && 
        handledBudgetPlanId.current !== newlyCreatedBudgetPlanId) {
      // Save this ID as handled to prevent repeated processing
      handledBudgetPlanId.current = newlyCreatedBudgetPlanId;
      
      // Only update state if the component is still mounted
      if (isMounted.current) {
        setLoading(false);
        onClose();
        
        // Use a short timeout to allow the state changes to settle
        setTimeout(() => {
          if (isMounted.current) {
            router.push(`/budget-plans/${newlyCreatedBudgetPlanId}`);
          }
        }, 50);
      }
    }
  }, [newlyCreatedBudgetPlanId, router, onClose, visible, loading]);
  
  // Reset state when modal becomes visible
  useEffect(() => {
    if (visible) {
      // Initialize with current date by default
      setTargetMonth(new Date().getMonth() + 1);
      setTargetYear(new Date().getFullYear());
      setLoading(false);
      handledBudgetPlanId.current = null;
    }
  }, [visible]);
  
  // Handle duplicate action
  const handleDuplicate = async () => {
    // Don't allow duplicating to the same month
    if (targetYear === sourceYear && targetMonth === sourceMonth) {
      alert('Cannot duplicate to the same month. Please select a different month.');
      return;
    }
    
    // Warn if the target month already has a budget plan
    if (isMonthOccupied(targetYear, targetMonth)) {
      alert('The selected month already has a budget plan. Please select a different month.');
      return;
    }
    
    setLoading(true);
    
    try {
      // Create UTC-safe date for the target month
      const targetDate = createUtcSafeDate(targetYear, targetMonth);
      await createBudgetPlanFromExisting(targetDate, sourceBudgetPlanId);
      
      // Update will happen through the useEffect when newlyCreatedBudgetPlanId changes
    } catch (error) {
      console.error('Failed to duplicate budget plan:', error);
      if (isMounted.current) {
        setLoading(false);
        alert('Failed to duplicate budget plan');
      }
    }
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
    >
      <View className="flex-1 justify-center items-center bg-black/50">
        <View className="w-[90%] max-w-md bg-background-light p-6 rounded-2xl shadow-lg">
          <View className="flex-row justify-between items-center mb-5">
            <View className="flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-2">
                <Ionicons name="copy-outline" size={18} color="#0284c7" />
              </View>
              <Text className="text-xl font-bold text-text-primary">
                Duplicate Budget Plan
              </Text>
            </View>
            <TouchableOpacity
              onPress={onClose}
              disabled={loading}
              className="w-8 h-8 rounded-full bg-surface-subtle items-center justify-center"
              hitSlop={{ top: 10, right: 10, bottom: 10, left: 10 }}
            >
              <Ionicons name="close" size={20} color="#64748b" />
            </TouchableOpacity>
          </View>

          <Text className="text-text-secondary mb-5">
            Duplicate your budget plan from {formatMonthYear(sourceYear, sourceMonth)} to a new month. This will copy all incomes, needs, wants, and savings.
          </Text>

          {/* Source Month Display */}
          <View className="mb-5">
            <Text className="mb-1 text-sm font-medium text-text-secondary">Source Month</Text>
            <View className="border border-surface-border rounded-xl p-3 bg-surface-subtle flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-green-100 items-center justify-center mr-2">
                <Ionicons name="calendar" size={16} color="#16a34a" />
              </View>
              <Text className="font-medium text-text-primary">
                {formatMonthYear(sourceYear, sourceMonth)}
              </Text>
            </View>
          </View>

          {/* Target Year Selector */}
          <View className="mb-5">
            <Text className="mb-1 text-sm font-medium text-text-secondary">Target Year</Text>
            <View className="flex-row space-x-2">
              {years.map(year => (
                <TouchableOpacity
                  key={year}
                  onPress={() => setTargetYear(year)}
                  disabled={loading}
                  className={`flex-1 border rounded-xl p-3 items-center justify-center ${
                    targetYear === year 
                      ? 'bg-primary-100 border-primary-300'
                      : 'bg-white border-surface-border'
                  }`}
                >
                  <Text className={`font-medium ${
                    targetYear === year ? 'text-primary-700' : 'text-text-secondary'
                  }`}>
                    {year}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
          
          {/* Target Month Selector */}
          <View className="mb-5">
            <Text className="mb-1 text-sm font-medium text-text-secondary">Target Month</Text>
            <ScrollView 
              horizontal 
              showsHorizontalScrollIndicator={false}
              className="pb-2"
            >
              <View className="flex-row space-x-2">
                {months.map((month, index) => {
                  const monthNumber = index + 1;
                  const isOccupied = isMonthOccupied(targetYear, monthNumber);
                  const isCurrentSource = targetYear === sourceYear && monthNumber === sourceMonth;
                  
                  return (
                    <TouchableOpacity
                      key={month}
                      onPress={() => setTargetMonth(monthNumber)}
                      disabled={loading || isCurrentSource}
                      className={`border rounded-xl p-3 min-w-[80px] items-center justify-center ${
                        targetMonth === monthNumber && !isCurrentSource
                          ? 'bg-primary-100 border-primary-300'
                          : isOccupied || isCurrentSource
                            ? 'bg-gray-100 border-gray-200 opacity-50'
                            : 'bg-white border-surface-border'
                      }`}
                    >
                      <Text className={`font-medium ${
                        targetMonth === monthNumber && !isCurrentSource
                          ? 'text-primary-700'
                          : isOccupied || isCurrentSource
                            ? 'text-gray-400'
                            : 'text-text-secondary'
                      }`}>
                        {month.substring(0, 3)}
                      </Text>
                      
                      {isOccupied && (
                        <View className="bg-red-100 px-1.5 py-0.5 rounded-full mt-1">
                          <Text className="text-xs text-red-700">Exists</Text>
                        </View>
                      )}
                      
                      {isCurrentSource && (
                        <View className="bg-gray-200 px-1.5 py-0.5 rounded-full mt-1">
                          <Text className="text-xs text-gray-700">Source</Text>
                        </View>
                      )}
                    </TouchableOpacity>
                  );
                })}
              </View>
            </ScrollView>
          </View>

          {/* Action Buttons */}
          <View className="flex-row space-x-3 mt-2">
            <TouchableOpacity
              onPress={onClose}
              disabled={loading}
              className="flex-1 py-3 px-4 border-2 border-gray-300 rounded-xl items-center"
            >
              <Text className="font-medium text-text-secondary">Cancel</Text>
            </TouchableOpacity>
            
            <TouchableOpacity
              onPress={handleDuplicate}
              disabled={loading || isMonthOccupied(targetYear, targetMonth) || (targetYear === sourceYear && targetMonth === sourceMonth)}
              className={`flex-1 py-3 px-4 rounded-xl items-center ${
                loading || isMonthOccupied(targetYear, targetMonth) || (targetYear === sourceYear && targetMonth === sourceMonth)
                  ? 'bg-gray-300'
                  : 'bg-primary-600'
              }`}
            >
              {loading ? (
                <ActivityIndicator size="small" color="#ffffff" />
              ) : (
                <Text className="font-medium text-white">Duplicate</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

export default DuplicateBudgetPlanModal;