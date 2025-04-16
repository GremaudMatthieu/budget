import React from 'react';
import { View, Text, ScrollView, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { useAuth } from '@/contexts/AuthContext';

export default function ProfileScreen() {
  const router = useRouter();
  const { user, logout } = useAuth();
  
  const handleLogout = async () => {
    await logout();
    router.replace('/');
  };
  
  return (
    <View className="flex-1 bg-background-subtle">
      <StatusBar style="dark" />
      
      {/* Header */}
      <View className="bg-primary-600 px-6 pt-12 pb-6">
        <Text className="text-2xl font-bold text-white">My Profile</Text>
        <Text className="text-primary-200">Manage your account settings</Text>
      </View>
      
      <ScrollView className="flex-1 px-4 pt-6">
        {/* User Profile Card */}
        <View className="bg-white rounded-xl shadow-sm p-5 mb-6">
          <View className="items-center mb-4">
            <View className="w-20 h-20 rounded-full bg-primary-100 items-center justify-center mb-2">
              <Text className="text-primary-600 text-2xl font-bold">
                {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
              </Text>
            </View>
            <Text className="text-lg font-semibold text-text-primary">{user?.name || 'User'}</Text>
            <Text className="text-text-secondary">{user?.email || ''}</Text>
          </View>
        </View>
        
        {/* Settings Menu */}
        <View className="bg-white rounded-xl shadow-sm mb-6">
          <TouchableOpacity 
            className="flex-row items-center justify-between p-4 border-b border-surface-border"
            onPress={() => {
              // Navigate to account settings
            }}
          >
            <View className="flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
                <Ionicons name="person-outline" size={16} color="#0c6cf2" />
              </View>
              <Text className="text-text-primary">Account Settings</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#64748b" />
          </TouchableOpacity>
          
          <TouchableOpacity 
            className="flex-row items-center justify-between p-4 border-b border-surface-border"
            onPress={() => {
              // Navigate to notifications settings
            }}
          >
            <View className="flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
                <Ionicons name="notifications-outline" size={16} color="#0c6cf2" />
              </View>
              <Text className="text-text-primary">Notifications</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#64748b" />
          </TouchableOpacity>
          
          <TouchableOpacity 
            className="flex-row items-center justify-between p-4 border-b border-surface-border"
            onPress={() => {
              // Navigate to security settings
            }}
          >
            <View className="flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
                <Ionicons name="shield-checkmark-outline" size={16} color="#0c6cf2" />
              </View>
              <Text className="text-text-primary">Security</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#64748b" />
          </TouchableOpacity>
          
          <TouchableOpacity 
            className="flex-row items-center justify-between p-4"
            onPress={() => {
              // Navigate to appearance settings
            }}
          >
            <View className="flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-primary-100 items-center justify-center mr-3">
                <Ionicons name="color-palette-outline" size={16} color="#0c6cf2" />
              </View>
              <Text className="text-text-primary">Appearance</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#64748b" />
          </TouchableOpacity>
        </View>
        
        {/* Support and About */}
        <View className="bg-white rounded-xl shadow-sm mb-6">
          <TouchableOpacity 
            className="flex-row items-center justify-between p-4 border-b border-surface-border"
            onPress={() => {
              // Navigate to help & support
            }}
          >
            <View className="flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-accent-100 items-center justify-center mr-3">
                <Ionicons name="help-circle-outline" size={16} color="#4f46e5" />
              </View>
              <Text className="text-text-primary">Help & Support</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#64748b" />
          </TouchableOpacity>
          
          <TouchableOpacity 
            className="flex-row items-center justify-between p-4"
            onPress={() => {
              // Navigate to about page
            }}
          >
            <View className="flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-accent-100 items-center justify-center mr-3">
                <Ionicons name="information-circle-outline" size={16} color="#4f46e5" />
              </View>
              <Text className="text-text-primary">About</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#64748b" />
          </TouchableOpacity>
        </View>
        
        {/* Logout Button */}
        <TouchableOpacity
          className="bg-danger-100 py-4 rounded-xl mb-6"
          onPress={handleLogout}
        >
          <Text className="text-danger-600 text-center font-semibold">Log Out</Text>
        </TouchableOpacity>
        
        <Text className="text-center text-text-muted text-xs mb-6">
          Version 1.0.0
        </Text>
      </ScrollView>
    </View>
  );
}