'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { useUser } from '../domain/user/userHooks'
import { useTranslation } from '../hooks/useTranslation'
import { useError } from "../contexts/ErrorContext";
import TextInput from '../components/inputs/formInputs/textInputs'
import PasswordInput from '../components/inputs/passwordInput'
import ActionButton from '../components/buttons/formButton/formButton'
import GoogleSignInButton from '../components/auth/GoogleSignInButton'

export default function SignIn() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const { signIn, isAuthenticated, loading, hasEnvelopes } = useUser()
  const router = useRouter()
  const { t } = useTranslation()
  const { error, setError } = useError()
  
  useEffect(() => {
    const checkAuthAndRedirect = async () => {
      if (isAuthenticated) {
        const userHasEnvelopes = await hasEnvelopes()
        if (userHasEnvelopes) {
          router.push('/envelopes')
        } else {
          router.push('/envelopes')
        }
      }
    }

    checkAuthAndRedirect()
  }, [isAuthenticated, router, hasEnvelopes])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    const success = await signIn(email, password, setError)
    if (success) {
      const userHasEnvelopes = await hasEnvelopes()
      if (userHasEnvelopes) {
        router.push('/envelopes')
      } else {
        router.push('/envelopes')
      }
    }
  }

  if (loading) {
    return <div className="flex justify-center items-center h-screen">
      <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
    </div>
  }

  if (isAuthenticated) {
    return null
  }

  return (
    <div className="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-background">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          <h1 className="text-3xl font-extrabold text-foreground">{t('signin.title')}</h1>
        </div>
        <form onSubmit={handleSubmit} className="mt-8 space-y-6 neomorphic p-8 rounded-lg">
          <div className="rounded-md space-y-4">
            <TextInput
              id="email"
              name="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className=""
              label={t("signup.email")}
              placeholder={t("signup.email")}
            />
            <PasswordInput
              id="password"
              value={password}
              onChange={setPassword}
              placeholder={t("signup.password")}
              label={t("signup.password")}
              required
            />
          </div>
          <div className="flex items-center justify-between">
            <div className="text-sm">
              <Link href="/forgot-password" className="font-medium text-primary hover:text-primary-dark">
                {t('signin.forgotPassword')}
              </Link>
            </div>
          </div>
          <div>
            <ActionButton type="submit" label={t('signin.signIn')} disabled={loading} className="" />
          </div>
          
          <div className="mt-4">
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-300"></div>
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-background text-gray-500">
                  {t('signin.or')}
                </span>
              </div>
            </div>
            
            <div className="mt-4">
              <GoogleSignInButton />
            </div>
          </div>
        </form>
        <p className="mt-2 text-center text-sm text-foreground">
          {t('signin.dontHaveAccount')}{' '}
          <Link href="/signup" className="font-medium text-primary hover:text-primary-dark">
            {t('signin.signUp')}
          </Link>
        </p>
      </div>
    </div>
  )
}
