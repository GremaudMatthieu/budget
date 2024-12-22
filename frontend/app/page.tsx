'use client'

import Link from 'next/link'
import { ArrowRight, DollarSign, PieChart, TrendingUp, BarChart2, Calendar } from 'lucide-react'
import { useTranslation } from './hooks/useTranslation'
import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useUser } from './domain/user/userHooks'

export default function Home() {
  const { t } = useTranslation()
  const router = useRouter()
  const { user, loading } = useUser()

  useEffect(() => {
    if (!loading && user) {
      router.push('/dashboard')
    }
  }, [user, loading, router])

  if (loading) {
    return <div className="flex justify-center items-center h-screen">
      <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
    </div>
  }

  if (user) return null

  return (
      <div className="min-h-screen bg-background">
        <main className="container mx-auto px-4 py-8 md:py-16">
          <section className="text-center mb-8 md:mb-16">
            <h1 className="text-3xl md:text-4xl lg:text-6xl font-bold mb-4">
              {t('home.title')}
            </h1>
            <p className="text-lg md:text-xl lg:text-2xl text-muted-foreground mb-6 md:mb-8">
              {t('home.subtitle')}
            </p>
            <div>
              <Link
                  href="/signup"
                  className="inline-flex items-center px-4 py-2 md:px-6 md:py-3 text-base md:text-lg font-semibold text-primary bg-white border-2 border-primary rounded-full neomorphic-button hover:bg-primary hover:text-white transition-colors"
              >
                {t('home.getStarted')} <ArrowRight className="ml-2 h-4 w-4 md:h-5 md:w-5" />
              </Link>
            </div>
          </section>

          <section className="grid gap-6 mb-8 md:mb-16 md:grid-cols-3">
            <div className="neomorphic p-4 md:p-6 rounded-lg text-center">
              <DollarSign className="mx-auto h-10 w-10 md:h-12 md:w-12 text-primary mb-3 md:mb-4" />
              <h2 className="text-lg md:text-xl font-semibold mb-2">{t('home.smartBudgeting')}</h2>
              <p className="text-sm md:text-base text-muted-foreground">
                {t('home.smartBudgetingDesc')}
              </p>
            </div>
            <div className="neomorphic p-4 md:p-6 rounded-lg text-center">
              <PieChart className="mx-auto h-10 w-10 md:h-12 md:w-12 text-primary mb-3 md:mb-4" />
              <h2 className="text-lg md:text-xl font-semibold mb-2">{t('home.visualTracking')}</h2>
              <p className="text-sm md:text-base text-muted-foreground">
                {t('home.visualTrackingDesc')}
              </p>
            </div>
            <div className="neomorphic p-4 md:p-6 rounded-lg text-center">
              <BarChart2 className="mx-auto h-10 w-10 md:h-12 md:w-12 text-primary mb-3 md:mb-4" />
              <h2 className="text-lg md:text-xl font-semibold mb-2">{t('home.advancedAnalytics')}</h2>
              <p className="text-sm md:text-base text-muted-foreground">
                {t('home.advancedAnalyticsDesc')}
              </p>
            </div>
          </section>

          <section className="text-center mb-8 md:mb-16">
            <h2 className="text-2xl md:text-3xl font-bold mb-6">{t('home.howItWorks')}</h2>
            <div className="grid gap-6 md:grid-cols-3">
              <div>
                <div className="neomorphic-circle w-12 h-12 md:w-16 md:h-16 flex items-center justify-center text-xl md:text-2xl font-bold mx-auto mb-3 md:mb-4">
                  1
                </div>
                <h3 className="text-lg md:text-xl font-semibold mb-2">{t('home.createBudget')}</h3>
                <p className="text-sm md:text-base text-muted-foreground">
                  {t('home.createBudgetDesc')}
                </p>
              </div>
              <div>
                <div className="neomorphic-circle w-12 h-12 md:w-16 md:h-16 flex items-center justify-center text-xl md:text-2xl font-bold mx-auto mb-3 md:mb-4">
                  2
                </div>
                <h3 className="text-lg md:text-xl font-semibold mb-2">{t('home.trackExpenses')}</h3>
                <p className="text-sm md:text-base text-muted-foreground">
                  {t('home.trackExpensesDesc')}
                </p>
              </div>
              <div>
                <div className="neomorphic-circle w-12 h-12 md:w-16 md:h-16 flex items-center justify-center text-xl md:text-2xl font-bold mx-auto mb-3 md:mb-4">
                  3
                </div>
                <h3 className="text-lg md:text-xl font-semibold mb-2">{t('home.analyzeProgress')}</h3>
                <p className="text-sm md:text-base text-muted-foreground">
                  {t('home.analyzeProgressDesc')}
                </p>
              </div>
            </div>
          </section>

          <section className="mb-8 md:mb-16">
            <h2 className="text-2xl md:text-3xl font-bold mb-4 text-center">{t('home.whyChooseUs')}</h2>
            <div className="neomorphic p-4 md:p-8 rounded-lg">
              <div className="flex flex-col items-center mb-6 md:mb-8 md:flex-row">
                <TrendingUp className="h-12 w-12 md:h-16 md:w-16 text-primary mb-3 md:mb-0 md:mr-6" />
                <div>
                  <h3 className="text-xl md:text-2xl font-semibold mb-2 text-center md:text-left">{t('home.comprehensiveBudgeting')}</h3>
                  <p className="text-sm md:text-base text-muted-foreground">
                    {t('home.comprehensiveBudgetingDesc')}
                  </p>
                  <p className="text-sm md:text-base text-muted-foreground mt-2 md:mt-4">
                    {t('home.futureFeaturesHint')}
                  </p>
                </div>
              </div>
              <div className="grid gap-4 md:gap-6 md:grid-cols-2">
                <div className="neomorphic-inset p-3 md:p-4 rounded-lg">
                  <h4 className="text-base md:text-lg font-semibold mb-2">{t('home.flexibleCustomization')}</h4>
                  <p className="text-sm md:text-base text-muted-foreground">
                    {t('home.flexibleCustomizationDesc')}
                  </p>
                </div>
                <div className="neomorphic-inset p-3 md:p-4 rounded-lg">
                  <h4 className="text-base md:text-lg font-semibold mb-2">{t('home.insightfulReporting')}</h4>
                  <p className="text-sm md:text-base text-muted-foreground">
                    {t('home.insightfulReportingDesc')}
                  </p>
                </div>
              </div>
            </div>
          </section>
          <section className="text-center mt-8 md:mt-16">
            <Link
                href="/signup"
                className="inline-flex items-center px-6 py-3 md:px-8 md:py-4 text-lg md:text-xl font-semibold text-primary bg-white border-2 border-primary rounded-full neomorphic-button hover:bg-primary hover:text-white transition-colors"
            >
              {t('home.startBudgeting')}
            </Link>
          </section>
        </main>
      </div>
  )
}
