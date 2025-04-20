import { apiClient } from './apiClient';
import { v4 as uuidv4 } from 'uuid';
import { 
  BudgetPlansCalendar, 
  BudgetPlan,
  CreateBudgetPlanPayload,
  CreateFromExistingPayload,
  Category
} from '../types/budgetTypes';

export class BudgetService {
  // Budget plan queries
  async getBudgetPlansCalendar(year: number): Promise<BudgetPlansCalendar> {
    const response = await apiClient.get(`/budget-plans-yearly-calendar?year=${year}`);
    return response;
  }

  async getBudgetPlan(uuid: string): Promise<BudgetPlan> {
    return await apiClient.get(`/budget-plans/${uuid}`);
  }

  async getNeedsCategories(): Promise<Category[]> {
    return await apiClient.get('/needs-categories');
  }

  async getWantsCategories(): Promise<Category[]> {
    return await apiClient.get('/wants-categories');
  }

  async getSavingsCategories(): Promise<Category[]> {
    return await apiClient.get('/savings-categories');
  }

  async getIncomesCategories(): Promise<Category[]> {
    return await apiClient.get('/incomes-categories');
  }

  // Budget plan commands
  async createBudgetPlan(payload: CreateBudgetPlanPayload, requestId: string): Promise<void> {
    const config = { headers: { 'Request-Id': requestId } };
    await apiClient.post('/budget-plans-generate', payload, config);
  }

  async createBudgetPlanFromExisting(payload: CreateFromExistingPayload, requestId: string): Promise<void> {
    const config = { headers: { 'Request-Id': requestId } };
    await apiClient.post('/budget-plans-generate-with-one-that-already-exists', payload, config);
  }

  // Budget item management
  async addBudgetItem(
    budgetPlanId: string, 
    type: 'need' | 'want' | 'saving' | 'income', 
    name: string, 
    amount: string, 
    category: string
  ): Promise<void> {
    const requestId = uuidv4();
    const itemId = uuidv4();
    const config = { headers: { 'Request-Id': requestId } };
    const payload = {
      uuid: itemId,
      name,
      amount,
      category
    };

    let endpoint = '';
    
    switch (type) {
      case 'need':
        endpoint = `/budget-plans/${budgetPlanId}/add-need`;
        break;
      case 'want':
        endpoint = `/budget-plans/${budgetPlanId}/add-want`;
        break;
      case 'saving':
        endpoint = `/budget-plans/${budgetPlanId}/add-saving`;
        break;
      case 'income':
        endpoint = `/budget-plans/${budgetPlanId}/add-income`;
        break;
    }

    await apiClient.post(endpoint, payload, config);
    return;
  }

  async adjustBudgetItem(
    budgetPlanId: string,
    itemId: string,
    type: 'need' | 'want' | 'saving' | 'income', 
    name: string, 
    amount: string, 
    category: string
  ): Promise<void> {
    const requestId = uuidv4();
    const config = { headers: { 'Request-Id': requestId } };
    const payload = {
      name,
      amount,
      category
    };

    let endpoint = '';
    
    switch (type) {
      case 'need':
        endpoint = `/budget-plans/${budgetPlanId}/adjust-need/${itemId}`;
        break;
      case 'want':
        endpoint = `/budget-plans/${budgetPlanId}/adjust-want/${itemId}`;
        break;
      case 'saving':
        endpoint = `/budget-plans/${budgetPlanId}/adjust-saving/${itemId}`;
        break;
      case 'income':
        endpoint = `/budget-plans/${budgetPlanId}/adjust-income/${itemId}`;
        break;
    }

    await apiClient.post(endpoint, payload, config);
    return;
  }

  async removeBudgetItem(
    budgetPlanId: string,
    itemId: string,
    type: 'need' | 'want' | 'saving' | 'income'
  ): Promise<void> {
    const requestId = uuidv4();
    const config = { headers: { 'Request-Id': requestId } };

    let endpoint = '';
    
    switch (type) {
      case 'need':
        endpoint = `/budget-plans/${budgetPlanId}/remove-need/${itemId}`;
        break;
      case 'want':
        endpoint = `/budget-plans/${budgetPlanId}/remove-want/${itemId}`;
        break;
      case 'saving':
        endpoint = `/budget-plans/${budgetPlanId}/remove-saving/${itemId}`;
        break;
      case 'income':
        endpoint = `/budget-plans/${budgetPlanId}/remove-income/${itemId}`;
        break;
    }

    await apiClient.delete(endpoint, config);
    return;
  }
}

export const budgetService = new BudgetService();