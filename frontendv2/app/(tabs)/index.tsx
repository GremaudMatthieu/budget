import React, { useEffect } from 'react';
import { View, Text, TouchableOpacity, AppState, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '@/contexts/AuthContext';
import { useTranslation } from '@/utils/useTranslation';
import AnimatedHeaderLayout from '@/components/withAnimatedHeader';
import { useBudget } from '@/contexts/BudgetContext';
import { useEnvelopes } from '@/contexts/EnvelopeContext';

// Content component for the dashboard, which will be wrapped with the animated header
function DashboardContent() {
  const { t } = useTranslation();
  const router = useRouter();
  const { budgetPlansCalendar, loading: budgetLoading, fetchBudgetPlansCalendar } = useBudget();
  const { envelopesData, loading: envelopesLoading, refreshEnvelopes } = useEnvelopes();

  // Get current month/year
  const now = new Date();
  const currentMonth = now.getMonth() + 1;
  const currentYear = now.getFullYear();

  // Fetch budget plans on mount and when app comes to foreground
  useEffect(() => {
    fetchBudgetPlansCalendar(currentYear);
    refreshEnvelopes(true, 3); // Limit to 5 envelopes for dashboard
    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') {
        fetchBudgetPlansCalendar(currentYear);
        refreshEnvelopes(true, 3);
      }
    });
    return () => subscription.remove();
  }, [currentYear, fetchBudgetPlansCalendar, refreshEnvelopes]);

  // Get the current month's budget plan from the year/month map
  const currentBudget = budgetPlansCalendar && budgetPlansCalendar[currentYear]?.[currentMonth];
  const hasCurrentBudget = !!currentBudget && !!currentBudget.uuid;
  const spent = currentBudget?.totalAllocated ?? 0;
  const total = currentBudget?.totalIncome ?? 0;
  const spentPercent = total > 0 ? Math.min(100, (spent / total) * 100) : 0;

  // Get up to 3 most recent envelopes
  const envelopes = envelopesData?.envelopes?.slice(0, 3) ?? [];

  return (
    <View className="flex-1">
      {/* Quick Actions */}
      <View className="mb-6">
        <Text className="text-lg font-semibold text-secondary-800 mb-4">{t('dashboard.quickActions')}</Text>

        <View className="flex-row justify-between mb-4">
          <TouchableOpacity
            className="bg-white rounded-xl p-4 shadow-sm items-center justify-center w-[48%]"
            onPress={() => router.push('/envelopes')}
          >
            <View className="w-12 h-12 rounded-full bg-primary-100 items-center justify-center mb-2">
              <Ionicons name="wallet-outline" size={24} color="#0c6cf2" />
            </View>
            <Text className="text-text-primary font-medium">{t('dashboard.myEnvelopes')}</Text>
          </TouchableOpacity>

          <TouchableOpacity
            className="bg-white rounded-xl p-4 shadow-sm items-center justify-center w-[48%]"
            onPress={() => router.push('/budget-plans')}
          >
            <View className="w-12 h-12 rounded-full bg-primary-100 items-center justify-center mb-2">
              <Ionicons name="pie-chart-outline" size={24} color="#0c6cf2" />
            </View>
            <Text className="text-text-primary font-medium">{t('dashboard.budgetPlans')}</Text>
          </TouchableOpacity>
        </View>

        <View className="flex-row justify-between">
          <TouchableOpacity
            className={`bg-white rounded-xl p-4 shadow-sm items-center justify-center w-[48%]${hasCurrentBudget ? ' opacity-50' : ''}`}
            onPress={() => router.push('/budget-plans/create')}
            disabled={hasCurrentBudget}
          >
            <View className="w-12 h-12 rounded-full bg-accent-100 items-center justify-center mb-2">
              <Ionicons name="add-outline" size={24} color="#4f46e5" />
            </View>
            <Text className="text-text-primary font-medium">{t('dashboard.newBudget')}</Text>
            {!!hasCurrentBudget && (
              <Text className="text-xs text-red-500 mt-1 text-center">{t('dashboard.budgetExistsThisMonth')}</Text>
            )}
          </TouchableOpacity>

          <TouchableOpacity
            className="bg-white rounded-xl p-4 shadow-sm items-center justify-center w-[48%]"
            onPress={() => router.push('/profile')}
          >
            <View className="w-12 h-12 rounded-full bg-accent-100 items-center justify-center mb-2">
              <Ionicons name="settings-outline" size={24} color="#4f46e5" />
            </View>
            <Text className="text-text-primary font-medium">{t('dashboard.settings')}</Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* Budget Status Card */}
      <View className="bg-white rounded-xl shadow-sm p-5 mb-6">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-lg font-semibold text-text-primary">{t('dashboard.thisMonthBudget')}</Text>
          <TouchableOpacity onPress={() => router.push('/budget-plans')}>
            <Text className="text-primary-600 font-medium">{t('dashboard.viewAll')}</Text>
          </TouchableOpacity>
        </View>
        {budgetLoading ? (
          <Text>{t('common.loading')}</Text>
        ) : currentBudget ? (
          <>
            <View className="flex-row justify-between mb-2">
              <Text className="text-text-secondary">{t('dashboard.spentSoFar')}</Text>
              <Text className="font-semibold">${spent.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
            </View>
            <View className="w-full h-2 bg-gray-200 rounded-full mb-1">
              <View className="h-2 bg-primary-600 rounded-full" style={{ width: `${spentPercent}%` }} />
            </View>
            <View className="flex-row justify-between">
              <Text className="text-xs text-text-muted">$0</Text>
              <Text className="text-xs text-text-muted">${total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
            </View>
          </>
        ) : (
          <Text className="text-text-secondary">{t('dashboard.noBudgetThisMonth')}</Text>
        )}
      </View>

      {/* Envelope Summary */}
      <View className="bg-white rounded-xl shadow-sm p-5 mb-6">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-lg font-semibold text-text-primary">{t('dashboard.yourEnvelopes')}</Text>
          <TouchableOpacity onPress={() => router.push('/envelopes')}>
            <Text className="text-primary-600 font-medium">{t('dashboard.viewAll')}</Text>
          </TouchableOpacity>
        </View>
        {envelopesLoading ? (
          <Text>{t('common.loading')}</Text>
        ) : envelopes.length > 0 ? (
          <View className="space-y-4">
            {envelopes.map((env) => (
              <View key={env.uuid} className="flex-row justify-between items-center">
                <View className="flex-row items-center">
                  <View className="w-10 h-10 rounded-full bg-green-100 items-center justify-center mr-3">
                    <Ionicons name="wallet-outline" size={20} color="#16a34a" />
                  </View>
                  <View>
                    <Text className="font-medium text-text-primary">{env.name}</Text>
                    <Text className="text-xs text-text-muted">{env.updatedAt ? new Date(env.updatedAt).toLocaleDateString() : ''}</Text>
                  </View>
                </View>
                <Text className="font-semibold">${parseFloat(env.currentAmount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
              </View>
            ))}
          </View>
        ) : (
          <Text className="text-text-secondary">{t('dashboard.noEnvelopes')}</Text>
        )}
      </View>

      {/* Tip Card */}
      <View className="bg-secondary-900 rounded-xl p-5 mb-6">
        <View className="flex-row items-start">
          <View className="w-10 h-10 rounded-full bg-secondary-800 items-center justify-center mr-3">
            <Ionicons name="bulb-outline" size={20} color="#fbbf24" />
          </View>
          <View className="flex-1">
            <Text className="font-semibold text-white mb-1">{t('dashboard.financialTip')}</Text>
            <Text className="text-secondary-200">{t('dashboard.tipText')}</Text>
          </View>
        </View>
      </View>
      {Platform.OS === 'web' && <div className="h-32" />}
    </View>
  );
}

// Get the user name for the header subtitle
function DashboardHeader() {
  const { t } = useTranslation();
  const { user } = useAuth();
  return (
    <>
      <Text className="text-xl text-white mb-1">{t('dashboard.welcome')}</Text>
      <Text className="text-2xl font-bold text-white">{user ? String(user) : 'User'}</Text>
    </>
  );
}

function DashboardScreen() {
  const { t } = useTranslation();

  if (Platform.OS === 'web') {
    return (
      <div className="mb-8 mt-2">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <div>
            <h1 className="text-3xl font-bold text-slate-900 mb-1">Dashboard</h1>
            <p className="text-slate-500">{t('dashboard.summary')}</p>
          </div>
          {/* Place any important actions/info from the blue header here if needed */}
        </div>
        <DashboardContent />
      </div>
    );
  }

  // Mobile: keep animated header
  return (
    <AnimatedHeaderLayout
      title="Dashboard"
      subtitle={t('dashboard.summary')}
      headerHeight={130}
    >
      <DashboardContent />
    </AnimatedHeaderLayout>
  );
}

// Apply the withAnimatedHeader HOC to wrap the Dashboard content
export default DashboardScreen