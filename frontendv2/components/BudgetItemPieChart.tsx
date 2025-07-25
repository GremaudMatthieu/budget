import React from 'react';
import { View, Text } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from '@/utils/useTranslation';

interface PieChartData {
  name: string;
  value: number;
  color: string;
}

interface BudgetItemPieChartProps {
  data: PieChartData[];
  size?: number;
}

// Modern card-based visualization that matches the app's design language
const BudgetItemPieChart: React.FC<BudgetItemPieChartProps> = ({ data, size = 200 }) => {
  const { t } = useTranslation();
  
  // Filter out zero values
  const filteredData = data.filter(item => item.value > 0);
  
  // Check if there's any data to display
  if (filteredData.length === 0) {
    return (
      <View className="items-center py-8">
        <View className="w-16 h-16 rounded-full bg-gray-100 items-center justify-center mb-3">
          <Ionicons name="analytics-outline" size={24} color="#64748b" />
        </View>
        <Text className="text-gray-500 text-center">No data to display</Text>
      </View>
    );
  }

  // Calculate total and percentages
  const total = filteredData.reduce((sum, item) => sum + item.value, 0);
  
  // Get icon based on category name
  const getIcon = (name: string) => {
    const nameLower = name.toLowerCase();
    if (nameLower.includes('need') || nameLower.includes('besoin')) return 'home-outline';
    if (nameLower.includes('want') || nameLower.includes('envie')) return 'heart-outline';
    if (nameLower.includes('saving') || nameLower.includes('épargne')) return 'wallet-outline';
    if (nameLower.includes('income') || nameLower.includes('revenu')) return 'trending-up-outline';
    return 'pie-chart-outline';
  };

  // Sort by value for better visual hierarchy
  const sortedData = [...filteredData].sort((a, b) => b.value - a.value);

  return (
    <View className="w-full">
      {/* Header with total */}
      <View className="bg-primary-50 rounded-xl p-4 mb-4">
        <View className="flex-row items-center justify-center">
          <View className="w-12 h-12 rounded-full bg-primary-600 items-center justify-center mr-3">
            <Ionicons name="analytics" size={20} color="#ffffff" />
          </View>
          <View>
            <Text className="text-sm text-primary-600 font-medium">{t('budgetPlans.totalBudget')}</Text>
            <Text className="text-xl font-bold text-primary-800">${total.toLocaleString()}</Text>
          </View>
        </View>
      </View>

      {/* Modern card layout */}
      <View className="space-y-3">
        {sortedData.map((item, index) => {
          const percentage = (item.value / total) * 100;
          
          return (
            <View key={index} className="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
              <View className="flex-row items-center justify-between">
                <View className="flex-row items-center flex-1">
                  <View 
                    className="w-10 h-10 rounded-full items-center justify-center mr-3"
                    style={{ backgroundColor: item.color + '20' }}
                  >
                    <Ionicons 
                      name={getIcon(item.name) as any} 
                      size={18} 
                      color={item.color} 
                    />
                  </View>
                  <View className="flex-1">
                    <Text className="text-base font-semibold text-gray-800 mb-1">
                      {item.name}
                    </Text>
                    <View className="flex-row items-center justify-between mb-2">
                      <Text className="text-sm text-gray-600">
                        ${item.value.toLocaleString()}
                      </Text>
                      <Text 
                        className="text-sm font-bold"
                        style={{ color: item.color }}
                      >
                        {percentage.toFixed(1)}%
                      </Text>
                    </View>
                    {/* Progress bar */}
                    <View className="h-2 bg-gray-200 rounded-full overflow-hidden">
                      <View 
                        className="h-full rounded-full transition-all duration-500"
                        style={{ 
                          backgroundColor: item.color,
                          width: `${percentage}%` 
                        }}
                      />
                    </View>
                  </View>
                </View>
              </View>
            </View>
          );
        })}
      </View>

      {/* Summary stats */}
      <View className="bg-gray-50 rounded-xl p-4 mt-4">
        <Text className="text-sm font-medium text-gray-600 mb-3 text-center">
          {t('budgetPlans.budgetSummary')}
        </Text>
        <View className="flex-row justify-center">
          {sortedData.slice(0, 3).map((item, index) => (
            <View key={index} className="items-center mx-4">
              <View 
                className="w-3 h-3 rounded-full mb-1"
                style={{ backgroundColor: item.color }}
              />
              <Text className="text-xs text-gray-500">
                {((item.value / total) * 100).toFixed(0)}%
              </Text>
            </View>
          ))}
        </View>
      </View>
    </View>
  );
};

export default BudgetItemPieChart;