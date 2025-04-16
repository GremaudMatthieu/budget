"use client"

import React, { useEffect, useState, useRef, useMemo, useCallback } from "react"
import { useRouter } from "next/navigation"
import { useUser } from "../../domain/user/userHooks"
import { useBudgetPlans } from "../../domain/budget/budgetHooks"
import BudgetPlanDetails from "../../components/budget/BudgetPlanDetails"
import { useTranslation } from "../../hooks/useTranslation"
import { api } from "../../infrastructure/api"
import { ArrowLeft, Calendar, PieChart } from "lucide-react"
import type { Category } from "../../domain/budget/budgetTypes"

export default function BudgetPlanPage({ params }: { params: Promise<{ uuid: string }> }) {
    const router = useRouter()
    const { user, loading: userLoading } = useUser()
    const { t } = useTranslation()
    const [categories, setCategories] = useState<{
        needs: Category[]
        wants: Category[]
        savings: Category[]
        incomes: Category[]
    }>({
        needs: [],
        wants: [],
        savings: [],
        incomes: [],
    })
    const [categoriesLoading, setCategoriesLoading] = useState(true)

    const { selectedBudgetPlan, loading, fetchBudgetPlan } = useBudgetPlans(categories)

    // Unwrap the params Promise
    const unwrappedParams = React.use(params)

    // Use a ref to track if the initial fetch has been made
    const initialFetchMade = useRef(false)

    const fetchCategories = useCallback(async () => {
        if (initialFetchMade.current) return
        try {
            const [needs, wants, savings, incomes] = await Promise.all([
                api.budgetQueries.getNeedsCategories(),
                api.budgetQueries.getWantsCategories(),
                api.budgetQueries.getSavingsCategories(),
                api.budgetQueries.getIncomesCategories(),
            ])
            setCategories({ needs, wants, savings, incomes })
        } catch (err) {
            console.error("Failed to fetch categories:", err)
        } finally {
            setCategoriesLoading(false)
            initialFetchMade.current = true
        }
    }, [])

    useEffect(() => {
        fetchCategories()
    }, [fetchCategories])

    useEffect(() => {
        if (!userLoading && !user) {
            router.push("/signin")
        } else if (user && unwrappedParams.uuid && !initialFetchMade.current) {
            fetchBudgetPlan(unwrappedParams.uuid)
            initialFetchMade.current = true
        }
    }, [user, userLoading, router, unwrappedParams.uuid, fetchBudgetPlan])

    const memoizedBudgetPlan = useMemo(() => selectedBudgetPlan, [selectedBudgetPlan])
    const memoizedCategories = useMemo(() => categories, [categories])

    if (userLoading || loading || categoriesLoading) {
        return (
            <div className="flex justify-center items-center h-screen">
                <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
            </div>
        )
    }

    if (!user) return null

    return (
        <div className="min-h-screen bg-background">
            <main className="container mx-auto px-4 py-8 md:py-16">
                <section className="mb-8">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-3xl md:text-4xl font-bold">{t("budgetTracker.planDetails")}</h1>
                        <button
                            onClick={() => router.push("/budget-tracker")}
                            className="inline-flex items-center px-4 py-2 text-base font-semibold text-primary bg-white border-2 border-primary rounded-full neomorphic-button hover:bg-primary hover:text-white transition-colors"
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" /> {t("budgetTracker.backToCalendar")}
                        </button>
                    </div>
                    
                    {memoizedBudgetPlan ? (
                        <BudgetPlanDetails budgetPlan={memoizedBudgetPlan} categories={memoizedCategories} />
                    ) : (
                        <div className="neomorphic p-8 rounded-lg text-center">
                            <PieChart className="h-16 w-16 text-primary mx-auto mb-6" />
                            <h2 className="text-2xl font-bold mb-4">{t("budgetTracker.planNotFound")}</h2>
                            <p className="text-lg text-muted-foreground mb-6">{t("budgetTracker.planNotFoundDesc")}</p>
                            <button
                                onClick={() => router.push("/budget-tracker")}
                                className="inline-flex items-center px-6 py-3 text-lg font-semibold text-primary bg-white border-2 border-primary rounded-full neomorphic-button hover:bg-primary hover:text-white transition-colors"
                            >
                                <Calendar className="mr-2 h-5 w-5" /> {t("budgetTracker.backToCalendar")}
                            </button>
                        </div>
                    )}
                </section>
            </main>
        </div>
    )
}
