import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import uuid from 'react-native-uuid';
import { budgetService } from '../services/budgetService';
import { useErrorContext } from './ErrorContext';
import { 
  BudgetPlansCalendar,
  BudgetPlan,
  Category,
  Income
} from '../types/budgetTypes';

// Define the context shape
interface BudgetContextType {
  budgetPlansCalendar: BudgetPlansCalendar | null;
  selectedBudgetPlan: BudgetPlan | null;
  loading: boolean;
  newlyCreatedBudgetPlanId: string | null;
  needsCategories: Category[];
  wantsCategories: Category[];
  savingsCategories: Category[];
  incomesCategories: Category[];
  fetchBudgetPlansCalendar: (year: number) => Promise<void>;
  fetchBudgetPlan: (budgetPlanId: string) => Promise<void>;
  fetchNeedsCategories: () => Promise<Category[]>;
  fetchWantsCategories: () => Promise<Category[]>;
  fetchSavingsCategories: () => Promise<Category[]>;
  fetchIncomesCategories: () => Promise<Category[]>;
  setSelectedBudgetPlan: (budgetPlan: BudgetPlan | null) => void;
  clearSelectedBudgetPlan: () => void;
  createBudgetPlan: (date: Date | string, currency: string, incomes: Income[]) => Promise<string | null>;
  createBudgetPlanFromExisting: (date: Date, existingBudgetPlanId: string) => Promise<string | null>;
  addBudgetItem: (type: "need" | "want" | "saving" | "income", name: string, amount: string, category: string) => Promise<boolean>;
  adjustBudgetItem: (type: "need" | "want" | "saving" | "income", itemId: string, name: string, amount: string, category: string) => Promise<boolean>;
  removeBudgetItem: (type: "need" | "want" | "saving" | "income", itemId: string) => Promise<boolean>;
  refreshBudgetPlans: () => Promise<void>;
  removeBudgetPlan: (budgetPlanId: string) => Promise<void>;
}

// Create context with default values
const BudgetContext = createContext<BudgetContextType | undefined>(undefined);

// Provider component
export const BudgetProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [budgetPlansCalendar, setBudgetPlansCalendar] = useState<BudgetPlansCalendar | null>(null);
  const [selectedBudgetPlan, setSelectedBudgetPlan] = useState<BudgetPlan | null>(null);
  const [loading, setLoading] = useState(false);
  const [newlyCreatedBudgetPlanId, setNewlyCreatedBudgetPlanId] = useState<string | null>(null);
  const [needsCategories, setNeedsCategories] = useState<Category[]>([]);
  const [wantsCategories, setWantsCategories] = useState<Category[]>([]);
  const [savingsCategories, setSavingsCategories] = useState<Category[]>([]);
  const [incomesCategories, setIncomesCategories] = useState<Category[]>([]);
  
  const { setError } = useErrorContext();
  
  // Individual category fetch methods
  const fetchNeedsCategories = useCallback(async () => {
    if (needsCategories.length > 0) return needsCategories;
    
    try {
      const categories = await budgetService.getNeedsCategories();
      setNeedsCategories(categories);
      return categories;
    } catch (err) {
      console.error("Failed to fetch needs categories:", err);
      setError('Failed to load needs categories');
      return [];
    }
  }, [needsCategories, setError]);
  
  const fetchWantsCategories = useCallback(async () => {
    if (wantsCategories.length > 0) return wantsCategories;
    
    try {
      const categories = await budgetService.getWantsCategories();
      setWantsCategories(categories);
      return categories;
    } catch (err) {
      console.error("Failed to fetch wants categories:", err);
      setError('Failed to load wants categories');
      return [];
    }
  }, [wantsCategories, setError]);
  
  const fetchSavingsCategories = useCallback(async () => {
    if (savingsCategories.length > 0) return savingsCategories;
    
    try {
      const categories = await budgetService.getSavingsCategories();
      setSavingsCategories(categories);
      return categories;
    } catch (err) {
      console.error("Failed to fetch savings categories:", err);
      setError('Failed to load savings categories');
      return [];
    }
  }, [savingsCategories, setError]);
  
  const fetchIncomesCategories = useCallback(async () => {
    if (incomesCategories.length > 0) return incomesCategories;
    
    try {
      const categories = await budgetService.getIncomesCategories();
      setIncomesCategories(categories);
      return categories;
    } catch (err) {
      console.error("Failed to fetch incomes categories:", err);
      setError('Failed to load incomes categories');
      return [];
    }
  }, [incomesCategories, setError]);

  const fetchBudgetPlansCalendar = useCallback(async (year: number) => {
    setLoading(true);
    try {
      const data = await budgetService.getBudgetPlansCalendar(year);
      setBudgetPlansCalendar(prev => {
        const merged: BudgetPlansCalendar = {
          ...(prev || {}),
          [year]: data[year],
          ...(data.budgetSummary ? { budgetSummary: data.budgetSummary } : {}),
          ...(data.incomeCategoriesRatio ? { incomeCategoriesRatio: data.incomeCategoriesRatio } : {}),
          ...(data.incomeCategoriesTotal ? { incomeCategoriesTotal: data.incomeCategoriesTotal } : {}),
          ...(data.needCategoriesRatio ? { needCategoriesRatio: data.needCategoriesRatio } : {}),
          ...(data.needCategoriesTotal ? { needCategoriesTotal: data.needCategoriesTotal } : {}),
          ...(data.savingCategoriesRatio ? { savingCategoriesRatio: data.savingCategoriesRatio } : {}),
          ...(data.savingCategoriesTotal ? { savingCategoriesTotal: data.savingCategoriesTotal } : {}),
          ...(data.wantCategoriesRatio ? { wantCategoriesRatio: data.wantCategoriesRatio } : {}),
          ...(data.wantCategoriesTotal ? { wantCategoriesTotal: data.wantCategoriesTotal } : {}),
        };
        return merged;
      });
    } catch (err) {
      console.error("Failed to fetch budget plans calendar:", err);
      setError('Failed to load budget plans calendar');
    } finally {
      setLoading(false);
    }
  }, [setError]);

  const fetchBudgetPlan = useCallback(async (budgetPlanId: string) => {
    setLoading(true);
    try {
      const data = await budgetService.getBudgetPlan(budgetPlanId);
      setSelectedBudgetPlan(data);
    } catch (err: any) {
      console.error("Failed to fetch budget plan:", err);
      if (err.name === 'NotFoundError') {
        // Only clear selectedBudgetPlan for confirmed 404 errors
        console.log(`Budget plan ${budgetPlanId} not found - clearing selectedBudgetPlan`);
        setSelectedBudgetPlan(null);
        // Don't set error for 404 - let the component handle the not found state
      } else {
        setError('Failed to load budget plan details');
        // Don't clear selectedBudgetPlan for other errors to prevent false 404s
      }
    } finally {
      setLoading(false);
    }
  }, [setError]);

  const clearSelectedBudgetPlan = useCallback(() => {
    setSelectedBudgetPlan(null);
  }, []);

  const createBudgetPlan = useCallback(async (date: Date | string, currency: string, incomes: Income[]) => {
    setLoading(true);
    const requestId = uuid.v4();

    try {
      // If date is a string, parse it; otherwise use as is
      const dateObj = typeof date === "string" ? new Date(date) : date;
      
      // Create a UTC-safe date to ensure correct month representation across timezones
      // Use the 15th day of the month to avoid timezone boundary issues
      const utcSafeDate = new Date(Date.UTC(
        dateObj.getFullYear(),
        dateObj.getMonth(),
        15, // Use the 15th day to avoid timezone edge cases
        12, // Noon UTC
        0, 0, 0
      ));
      
      const formattedDate = utcSafeDate.toISOString();

      const payload = {
        uuid: requestId,
        currency,
        date: formattedDate,
        incomes: incomes.map((income) => ({
          uuid: uuid.v4(),
          incomeName: income.name,
          amount: income.amount,
          category: income.category,
        })),
      };

      await budgetService.createBudgetPlan(payload, requestId);
      setNewlyCreatedBudgetPlanId(requestId);
      
      // Safety fallback: clear newly created budget plan ID after 5 seconds if no WebSocket event is received
      setTimeout(() => {
        setNewlyCreatedBudgetPlanId(currentId => {
          if (currentId === requestId) {
            console.log(`Fallback timeout for create budget plan ${requestId}: clearing pending state`);
            return null;
          }
          return currentId;
        });
      }, 5000);
      
      return requestId;
    } catch (err) {
      console.error("Failed to create budget plan:", err);
      setError('Failed to create budget plan');
      return null;
    } finally {
      setLoading(false);
    }
  }, [setError]);

  const createBudgetPlanFromExisting = useCallback(async (date: Date, existingBudgetPlanId: string) => {
    setLoading(true);
    const requestId = uuid.v4();

    try {
      // Create a UTC-safe date to ensure correct month representation across timezones
      const utcSafeDate = new Date(Date.UTC(
        date.getFullYear(),
        date.getMonth(),
        15, // Use the 15th day to avoid timezone edge cases
        12, // Noon UTC
        0, 0, 0
      ));
      
      const payload = {
        uuid: requestId,
        budgetPlanUuidThatAlreadyExists: existingBudgetPlanId,
        date: utcSafeDate.toISOString(),
      };

      await budgetService.createBudgetPlanFromExisting(payload, requestId);
      setNewlyCreatedBudgetPlanId(requestId);
      
      // Safety fallback: clear newly created budget plan ID after 5 seconds if no WebSocket event is received
      setTimeout(() => {
        setNewlyCreatedBudgetPlanId(currentId => {
          if (currentId === requestId) {
            console.log(`Fallback timeout for create budget plan from existing ${requestId}: clearing pending state`);
            return null;
          }
          return currentId;
        });
      }, 5000);
      
      return requestId;
    } catch (err) {
      console.error("Failed to create budget plan from existing:", err);
      setError('Failed to create budget plan from existing one');
      return null;
    } finally {
      setLoading(false);
    }
  }, [setError]);

  const addBudgetItem = useCallback(async (type: "need" | "want" | "saving" | "income", name: string, amount: string, category: string) => {
    if (!selectedBudgetPlan) return false;

    setLoading(true);
    const requestId = uuid.v4(); // Generate a unique request ID
    
    try {
      await budgetService.addBudgetItem(
        selectedBudgetPlan.budgetPlan.uuid,
        type,
        name,
        amount,
        category
      );
      
      // Safety fallback: clear loading state after 5 seconds if no WebSocket event is received
      const timeoutId = setTimeout(() => {
        console.log(`Fallback timeout for add ${type} item: clearing loading state`);
        setLoading(false);
      }, 5000);
      
      return true;
    } catch (err) {
      console.error(`Failed to add ${type}:`, err);
      setError(`Failed to add ${type}`);
      return false;
    } finally {
      setLoading(false);
    }
  }, [selectedBudgetPlan, setError]);

  const adjustBudgetItem = useCallback(async (
    type: "need" | "want" | "saving" | "income",
    itemId: string,
    name: string,
    amount: string,
    category: string
  ) => {
    if (!selectedBudgetPlan) return false;

    setLoading(true);
    const requestId = uuid.v4(); // Generate a unique request ID

    try {
      await budgetService.adjustBudgetItem(
        selectedBudgetPlan.budgetPlan.uuid,
        itemId,
        type,
        name,
        amount,
        category
      );
      
      // Safety fallback: clear loading state after 5 seconds if no WebSocket event is received
      const timeoutId = setTimeout(() => {
        console.log(`Fallback timeout for adjust ${type} item ${itemId}: clearing loading state`);
        setLoading(false);
      }, 5000);
      
      return true;
    } catch (err) {
      console.error(`Failed to adjust ${type}:`, err);
      setError(`Failed to update ${type}`);
      return false;
    } finally {
      setLoading(false);
    }
  }, [selectedBudgetPlan, setError]);

  const removeBudgetItem = useCallback(async (type: "need" | "want" | "saving" | "income", itemId: string) => {
    if (!selectedBudgetPlan) return false;

    setLoading(true);
    const requestId = uuid.v4(); // Generate a unique request ID

    try {
      await budgetService.removeBudgetItem(
        selectedBudgetPlan.budgetPlan.uuid,
        itemId,
        type
      );
      
      // Safety fallback: clear loading state after 5 seconds if no WebSocket event is received
      const timeoutId = setTimeout(() => {
        console.log(`Fallback timeout for remove ${type} item ${itemId}: clearing loading state`);
        setLoading(false);
      }, 5000);
      
      return true;
    } catch (err) {
      console.error(`Failed to remove ${type}:`, err);
      setError(`Failed to remove ${type}`);
      return false;
    } finally {
      setLoading(false);
    }
  }, [selectedBudgetPlan, setError]);

  // Add a method to refresh budget plans for the current year
  const refreshBudgetPlans = useCallback(async () => {
        const year = new Date().getFullYear();
    await fetchBudgetPlansCalendar(year);
  }, [fetchBudgetPlansCalendar]);

  // Add this method to delete a budget plan
  const removeBudgetPlan = useCallback(async (budgetPlanId: string) => {
    setLoading(true);
    try {
      await budgetService.deleteBudgetPlan(budgetPlanId);
      await fetchBudgetPlansCalendar(new Date().getFullYear());
      setSelectedBudgetPlan(null);
    } catch (err) {
      console.error('Failed to delete budget plan:', err);
      setError('Failed to delete budget plan');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [fetchBudgetPlansCalendar, setError]);

  const value = {
    budgetPlansCalendar,
    selectedBudgetPlan,
    loading,
    newlyCreatedBudgetPlanId,
    needsCategories,
    wantsCategories,
    savingsCategories,
    incomesCategories,
    fetchBudgetPlansCalendar,
    fetchBudgetPlan,
    fetchNeedsCategories,
    fetchWantsCategories,
    fetchSavingsCategories,
    fetchIncomesCategories,
    setSelectedBudgetPlan,
    clearSelectedBudgetPlan,
    createBudgetPlan,
    createBudgetPlanFromExisting,
    addBudgetItem,
    adjustBudgetItem,
    removeBudgetItem,
    refreshBudgetPlans,
    removeBudgetPlan,
  };

  return <BudgetContext.Provider value={value}>{children}</BudgetContext.Provider>;
};

// Custom hook to use the budget context
export const useBudget = () => {
  const context = useContext(BudgetContext);
  if (context === undefined) {
    throw new Error('useBudget must be used within a BudgetProvider');
  }
  return context;
};