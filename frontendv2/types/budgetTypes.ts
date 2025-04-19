export interface BudgetPlansCalendar {
  [year: string]: {
    [month: string]: {
      uuid: string | null
      totalIncome?: number
      totalAllocated?: number
      allocatedPercentage?: number
      needsPercentage?: number
      wantsPercentage?: number
      savingsPercentage?: number
      currency?: string
    }
  }
  incomeCategoriesRatio?: {[category: string]: string}
  incomeCategoriesTotal?: {[category: string]: string}
  needCategoriesRatio?: {[category: string]: string}
  needCategoriesTotal?: {[category: string]: string}
  savingCategoriesRatio?: {[category: string]: string}
  savingCategoriesTotal?: {[category: string]: string}
  wantCategoriesRatio?: {[category: string]: string}
  wantCategoriesTotal?: {[category: string]: string}
}

export interface BudgetPlan {
  budgetPlan: {
    uuid: string
    userId: string
    currency: string
    date: string
    createdAt: string
    updatedAt: string
  }
  needs: Array<{
    uuid: string
    budgetPlanUuid: string
    needName: string
    needAmount: string
    category: string
  }>
  savings: Array<{
    uuid: string
    budgetPlanUuid: string
    savingName: string
    savingAmount: string
    category: string
  }>
  wants: Array<{
    uuid: string
    budgetPlanUuid: string
    wantName: string
    wantAmount: string
    category: string
  }>
  incomes: Array<{
    uuid: string
    budgetPlanUuid: string
    incomeName: string
    incomeAmount: string
    category: string
  }>
}

export interface Income {
  name: string
  amount: string
  category: string
}

export interface CreateBudgetPlanPayload {
  uuid: string
  currency: string
  date: string
  incomes: Array<{
    uuid: string
    incomeName: string
    amount: string
    category: string
  }>
}

export interface CreateFromExistingPayload {
  uuid: string
  budgetPlanUuidThatAlreadyExists: string
  date: string
}

export interface Category {
  id: string
  name: string
}