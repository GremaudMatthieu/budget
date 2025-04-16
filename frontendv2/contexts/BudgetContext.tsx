import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { v4 as uuidv4 } from 'uuid';
import { budgetService } from '../services/budgetService';
import { useErrorContext } from './ErrorContext';
import { useSocket } from './SocketContext';
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
  setSelectedBudgetPlan: (budgetPlan: BudgetPlan | null) => void;
  clearSelectedBudgetPlan: () => void;
  createBudgetPlan: (date: Date | string, currency: string, incomes: Income[]) => Promise<string | null>;
  createBudgetPlanFromExisting: (date: Date, existingBudgetPlanId: string) => Promise<string | null>;
  addBudgetItem: (type: "need" | "want" | "saving" | "income", name: string, amount: string, category: string) => Promise<boolean>;
  adjustBudgetItem: (type: "need" | "want" | "saving" | "income", itemId: string, name: string, amount: string, category: string) => Promise<boolean>;
  removeBudgetItem: (type: "need" | "want" | "saving" | "income", itemId: string) => Promise<boolean>;
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
  
  const { socket, connected } = useSocket();
  const { setError } = useErrorContext();
  
  const fetchCategories = useCallback(async () => {
    try {
      const [needs, wants, savings, incomes] = await Promise.all([
        budgetService.getNeedsCategories(),
        budgetService.getWantsCategories(),
        budgetService.getSavingsCategories(),
        budgetService.getIncomesCategories(),
      ]);
      setNeedsCategories(needs);
      setWantsCategories(wants);
      setSavingsCategories(savings);
      setIncomesCategories(incomes);
    } catch (err) {
      console.error("Failed to fetch categories:", err);
      setError('Failed to load budget categories');
    }
  }, [setError]);

  useEffect(() => {
    fetchCategories();
  }, [fetchCategories]);

  const fetchBudgetPlansCalendar = useCallback(async (year: number) => {
    setLoading(true);
    try {
      const data = await budgetService.getBudgetPlansCalendar(year);
      setBudgetPlansCalendar(data);
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
    } catch (err) {
      console.error("Failed to fetch budget plan:", err);
      setError('Failed to load budget plan details');
    } finally {
      setLoading(false);
    }
  }, [setError]);

  const clearSelectedBudgetPlan = useCallback(() => {
    setSelectedBudgetPlan(null);
  }, []);

  const createBudgetPlan = useCallback(async (date: Date | string, currency: string, incomes: Income[]) => {
    setLoading(true);
    const requestId = uuidv4();

    try {
      const formattedDate = typeof date === "string" ? date : date.toISOString();

      const payload = {
        uuid: requestId,
        currency,
        date: formattedDate,
        incomes: incomes.map((income) => ({
          uuid: uuidv4(),
          incomeName: income.name,
          amount: income.amount,
          category: income.category,
        })),
      };

      await budgetService.createBudgetPlan(payload, requestId);
      setNewlyCreatedBudgetPlanId(requestId);
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
    const requestId = uuidv4();

    try {
      const payload = {
        uuid: requestId,
        budgetPlanUuidThatAlreadyExists: existingBudgetPlanId,
        date: date.toISOString(),
      };

      await budgetService.createBudgetPlanFromExisting(payload, requestId);
      setNewlyCreatedBudgetPlanId(requestId);
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
    
    try {
      await budgetService.addBudgetItem(
        selectedBudgetPlan.budgetPlan.uuid,
        type,
        name,
        amount,
        category
      );
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

    try {
      await budgetService.adjustBudgetItem(
        selectedBudgetPlan.budgetPlan.uuid,
        itemId,
        type,
        name,
        amount,
        category
      );
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

    try {
      await budgetService.removeBudgetItem(
        selectedBudgetPlan.budgetPlan.uuid,
        itemId,
        type
      );
      return true;
    } catch (err) {
      console.error(`Failed to remove ${type}:`, err);
      setError(`Failed to remove ${type}`);
      return false;
    } finally {
      setLoading(false);
    }
  }, [selectedBudgetPlan, setError]);

  // Listen for budget plan events
  useEffect(() => {
    if (!socket || !connected) return;

    const handleBudgetPlanEvent = (event: {
      aggregateId: string;
      type: string;
    }) => {
      console.log("Received WebSocket event:", event);

      // Refresh calendar after budget plan events
      if (budgetPlansCalendar) {
        const year = new Date().getFullYear();
        fetchBudgetPlansCalendar(year);
      }

      // If the event is for the currently selected budget plan, refresh it
      if (selectedBudgetPlan && event.aggregateId === selectedBudgetPlan.budgetPlan.uuid) {
        fetchBudgetPlan(event.aggregateId);
      }

      // If this is a newly created budget plan, set it as the selected plan
      if (event.type === "BudgetPlanGenerated" && event.aggregateId === newlyCreatedBudgetPlanId) {
        fetchBudgetPlan(event.aggregateId);
        setNewlyCreatedBudgetPlanId(null);
      }
    };

    const eventTypes = [
      "BudgetPlanCurrencyChanged",
      "BudgetPlanGenerated",
      "BudgetPlanGeneratedWithOneThatAlreadyExists",
      "BudgetPlanIncomeAdded",
      "BudgetPlanIncomeAdjusted",
      "BudgetPlanIncomeRemoved",
      "BudgetPlanNeedAdded",
      "BudgetPlanNeedAdjusted",
      "BudgetPlanNeedRemoved",
      "BudgetPlanRemoved",
      "BudgetPlanSavingAdded",
      "BudgetPlanSavingAdjusted",
      "BudgetPlanSavingRemoved",
      "BudgetPlanWantAdded",
      "BudgetPlanWantAdjusted",
      "BudgetPlanWantRemoved",
    ];

    eventTypes.forEach((eventType) => {
      socket.on(eventType, handleBudgetPlanEvent);
    });

    return () => {
      eventTypes.forEach((eventType) => {
        socket.off(eventType, handleBudgetPlanEvent);
      });
    };
  }, [
    socket,
    connected,
    fetchBudgetPlansCalendar,
    fetchBudgetPlan,
    selectedBudgetPlan,
    newlyCreatedBudgetPlanId,
    budgetPlansCalendar,
  ]);

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
    setSelectedBudgetPlan,
    clearSelectedBudgetPlan,
    createBudgetPlan,
    createBudgetPlanFromExisting,
    addBudgetItem,
    adjustBudgetItem,
    removeBudgetItem,
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