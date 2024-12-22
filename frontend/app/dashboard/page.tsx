'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useUser } from '../domain/user/userHooks'
import DashboardGraph from '../components/DashboardGraph'
import { useTranslation } from '../hooks/useTranslation'
import { useEnvelopes } from '../domain/envelope/envelopeHooks'
import Link from 'next/link'

export default function Dashboard() {
    const router = useRouter()
    const { user, loading: userLoading } = useUser()
    const { t } = useTranslation()
    const { envelopesData, loading: envelopesLoading, errorEnvelope } = useEnvelopes()

    useEffect(() => {
        if (!userLoading && !user) {
            router.push('/signin')
        }
    }, [user, userLoading, router])

    if (userLoading || envelopesLoading) {
        return <div className="flex justify-center items-center h-screen">
            <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
        </div>
    }

    if (!user) return null

    if (!envelopesData || envelopesData.envelopes.length === 0) {
        return (
            <div className="text-center mt-8">
                {t('dashboard.noEnvelopes')}{' '}
                <Link href="/envelopes" className="text-primary hover:underline">
                    {t('dashboard.create')}
                </Link>{' '}
                {t('dashboard.toSeeChart')}
            </div>
        )
    }

    return (
        <div className="container mx-auto px-4 py-6 sm:py-8">
            <h1 className="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6">{t('dashboard.title')}</h1>
            <p className="mb-4 text-sm sm:text-base">{t('dashboard.welcome', { name: user.name })}</p>
            <DashboardGraph envelopesData={envelopesData} />
        </div>
    )
}
