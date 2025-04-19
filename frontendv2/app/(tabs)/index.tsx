import React from 'react';
import { View, Text, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '@/contexts/AuthContext';
import withAnimatedHeader from '@/components/withAnimatedHeader';

// Content component for the dashboard, which will be wrapped with the animated header
function DashboardContent() {
  const router = useRouter();
  
  return (
    <View className="flex-1">
      {/* Quick Actions */}
      <View className="mb-6">
        <Text className="text-lg font-semibold text-secondary-800 mb-4">Quick Actions</Text>
        
        <View className="flex-row justify-between mb-4">
          <TouchableOpacity 
            className="bg-white rounded-xl p-4 shadow-sm items-center justify-center w-[48%]"
            onPress={() => router.push('/envelopes')}
          >
            <View className="w-12 h-12 rounded-full bg-primary-100 items-center justify-center mb-2">
              <Ionicons name="wallet-outline" size={24} color="#0c6cf2" />
            </View>
            <Text className="text-text-primary font-medium">My Envelopes</Text>
          </TouchableOpacity>
          
          <TouchableOpacity 
            className="bg-white rounded-xl p-4 shadow-sm items-center justify-center w-[48%]"
            onPress={() => router.push('/budget-plans')}
          >
            <View className="w-12 h-12 rounded-full bg-primary-100 items-center justify-center mb-2">
              <Ionicons name="pie-chart-outline" size={24} color="#0c6cf2" />
            </View>
            <Text className="text-text-primary font-medium">Budget Plans</Text>
          </TouchableOpacity>
        </View>
        
        <View className="flex-row justify-between">
          <TouchableOpacity 
            className="bg-white rounded-xl p-4 shadow-sm items-center justify-center w-[48%]"
            onPress={() => router.push('/budget-plans/create')}
          >
            <View className="w-12 h-12 rounded-full bg-accent-100 items-center justify-center mb-2">
              <Ionicons name="add-outline" size={24} color="#4f46e5" />
            </View>
            <Text className="text-text-primary font-medium">New Budget</Text>
          </TouchableOpacity>
          
          <TouchableOpacity 
            className="bg-white rounded-xl p-4 shadow-sm items-center justify-center w-[48%]"
            onPress={() => router.push('/profile')}
          >
            <View className="w-12 h-12 rounded-full bg-accent-100 items-center justify-center mb-2">
              <Ionicons name="settings-outline" size={24} color="#4f46e5" />
            </View>
            <Text className="text-text-primary font-medium">Settings</Text>
          </TouchableOpacity>
        </View>
      </View>
      
      {/* Budget Status Card */}
      <View className="bg-white rounded-xl shadow-sm p-5 mb-6">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-lg font-semibold text-text-primary">This Month's Budget</Text>
          <TouchableOpacity onPress={() => router.push('/budget-plans')}>
            <Text className="text-primary-600 font-medium">View All</Text>
          </TouchableOpacity>
        </View>
        
        <View className="flex-row justify-between mb-2">
          <Text className="text-text-secondary">Spent So Far</Text>
          <Text className="font-semibold">$1,245.00</Text>
        </View>
        
        <View className="w-full h-2 bg-gray-200 rounded-full mb-1">
          <View className="h-2 bg-primary-600 rounded-full" style={{ width: '65%' }} />
        </View>
        
        <View className="flex-row justify-between">
          <Text className="text-xs text-text-muted">$0</Text>
          <Text className="text-xs text-text-muted">$2,500</Text>
        </View>
      </View>
      
      {/* Envelope Summary */}
      <View className="bg-white rounded-xl shadow-sm p-5 mb-6">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-lg font-semibold text-text-primary">Your Envelopes</Text>
          <TouchableOpacity onPress={() => router.push('/envelopes')}>
            <Text className="text-primary-600 font-medium">View All</Text>
          </TouchableOpacity>
        </View>
        
        <View className="space-y-4">
          <View className="flex-row justify-between items-center">
            <View className="flex-row items-center">
              <View className="w-10 h-10 rounded-full bg-green-100 items-center justify-center mr-3">
                <Ionicons name="home-outline" size={20} color="#16a34a" />
              </View>
              <View>
                <Text className="font-medium text-text-primary">Housing</Text>
                <Text className="text-xs text-text-muted">3 days ago</Text>
              </View>
            </View>
            <Text className="font-semibold">$850.00</Text>
          </View>
          
          <View className="flex-row justify-between items-center">
            <View className="flex-row items-center">
              <View className="w-10 h-10 rounded-full bg-blue-100 items-center justify-center mr-3">
                <Ionicons name="restaurant-outline" size={20} color="#2563eb" />
              </View>
              <View>
                <Text className="font-medium text-text-primary">Groceries</Text>
                <Text className="text-xs text-text-muted">Yesterday</Text>
              </View>
            </View>
            <Text className="font-semibold">$125.50</Text>
          </View>
          
          <View className="flex-row justify-between items-center">
            <View className="flex-row items-center">
              <View className="w-10 h-10 rounded-full bg-purple-100 items-center justify-center mr-3">
                <Ionicons name="car-outline" size={20} color="#9333ea" />
              </View>
              <View>
                <Text className="font-medium text-text-primary">Transport</Text>
                <Text className="text-xs text-text-muted">Today</Text>
              </View>
            </View>
            <Text className="font-semibold">$78.25</Text>
          </View>
        </View>
      </View>
      
      {/* Tip Card */}
      <View className="bg-secondary-900 rounded-xl p-5 mb-6">
        <View className="flex-row items-start">
          <View className="w-10 h-10 rounded-full bg-secondary-800 items-center justify-center mr-3">
            <Ionicons name="bulb-outline" size={20} color="#fbbf24" />
          </View>
          <View className="flex-1">
            <Text className="font-semibold text-white mb-1">Financial Tip</Text>
            <Text className="text-secondary-200">Using the 50/30/20 rule can help you balance your budget: 50% for needs, 30% for wants, and 20% for savings.</Text>
          </View>
        </View>
      </View>
    </View>
  );
}

// Get the user name for the header subtitle
function DashboardHeader() {
  const { user } = useAuth();
  return (
    <>
      <Text className="text-xl text-white mb-1">Welcome back,</Text>
      <Text className="text-2xl font-bold text-white">{user?.name || 'User'}</Text>
    </>
  );
}

// Apply the withAnimatedHeader HOC to wrap the Dashboard content
export default withAnimatedHeader(DashboardContent, {
  title: 'Dashboard',
  subtitle: 'Your financial summary',
  headerHeight: 120
});