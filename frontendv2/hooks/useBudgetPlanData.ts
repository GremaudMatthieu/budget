import { useState, useCallback, useRef, useEffect } from 'react';
import { useBudget } from '@/contexts/BudgetContext';
import { useErrorContext } from '@/contexts/ErrorContext';
import { useTranslation } from '@/utils/useTranslation';

export type TabType = 'overview' | 'needs' | 'wants' | 'savings' | 'incomes';

/**
 * Custom hook for managing budget plan detail data and UI state.
 */
export function useBudgetPlanData(uuid: string) {
  const { t } = useTranslation();
  const { setError } = useErrorContext();
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

  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [refreshing, setRefreshing] = useState(false);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItemType, setCurrentItemType] = useState<'need' | 'want' | 'saving' | 'income'>('need');
  const [currentItem, setCurrentItem] = useState<{ id: string; name: string; amount: string; category: string } | null>(null);
  const isMounted = useRef(true);

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
    return () => { isMounted.current = false; };
  }, [uuid, fetchBudgetPlan, setError, t]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await fetchBudgetPlan(uuid);
    } catch (err) {
      setError(t('errors.failedToRefreshBudgetPlan'));
    } finally {
      setRefreshing(false);
    }
  }, [uuid, fetchBudgetPlan, setError, t]);

  const handleOpenAddModal = async (type: 'need' | 'want' | 'saving' | 'income') => {
    setCurrentItemType(type);
    try {
      switch (type) {
        case 'need': await fetchNeedsCategories(); break;
        case 'want': await fetchWantsCategories(); break;
        case 'saving': await fetchSavingsCategories(); break;
        case 'income': await fetchIncomesCategories(); break;
      }
    } catch (err) {
      setError(t(`errors.failedToFetch${type.charAt(0).toUpperCase() + type.slice(1)}Categories`));
    }
    setIsAddModalOpen(true);
  };

  const handleOpenEditModal = async (
    type: 'need' | 'want' | 'saving' | 'income',
    id: string,
    name: string,
    amount: string,
    category: string,
  ) => {
    setCurrentItemType(type);
    setCurrentItem({ id, name, amount, category });
    try {
      switch (type) {
        case 'need': await fetchNeedsCategories(); break;
        case 'want': await fetchWantsCategories(); break;
        case 'saving': await fetchSavingsCategories(); break;
        case 'income': await fetchIncomesCategories(); break;
      }
    } catch (err) {
      setError(t(`errors.failedToFetch${type.charAt(0).toUpperCase() + type.slice(1)}Categories`));
    }
    setIsEditModalOpen(true);
  };

  const handleOpenDeleteModal = (type: 'need' | 'want' | 'saving' | 'income', id: string, name: string) => {
    setCurrentItemType(type);
    setCurrentItem({ id, name, amount: '', category: '' });
    setIsDeleteModalOpen(true);
  };

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
  }, [addBudgetItem, currentItemType, fetchBudgetPlan, uuid, setError, t]);

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
  }, [adjustBudgetItem, currentItem, currentItemType, fetchBudgetPlan, uuid, setError, t]);

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
  }, [removeBudgetItem, currentItem, currentItemType, fetchBudgetPlan, uuid, setError, t]);

  return {
    activeTab,
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
    setCurrentItemType,
    currentItem,
    setCurrentItem,
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
  };
} 