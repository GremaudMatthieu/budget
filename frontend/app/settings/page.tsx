'use client'

import { useState, useEffect } from 'react'
import { useUser } from '../domain/user/userHooks'
import { useTranslation } from '../hooks/useTranslation'
import { Check, X, Mail } from 'lucide-react'
import { useRouter } from 'next/navigation'

export default function SettingsPage() {
  const { user, updateFirstname, updateLastname, changePassword, signOut } = useUser()
  const { t } = useTranslation()
  const [firstname, setFirstname] = useState('')
  const [lastname, setLastname] = useState('')
  const [oldPassword, setOldPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmNewPassword, setConfirmNewPassword] = useState('')
  const [editingFirstname, setEditingFirstname] = useState(false)
  const [editingLastname, setEditingLastname] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  const router = useRouter()

  useEffect(() => {
    if (user) {
      setFirstname(user.firstname || '')
      setLastname(user.lastname || '')
    }
  }, [user])

  const handleUpdateFirstname = async () => {
    setError(null)
    setSuccess(null)
    const result = await updateFirstname(firstname)
    if (result) {
      setSuccess(t('settings.firstnameUpdated'))
      setEditingFirstname(false)
    } else {
      setError(t('settings.updateFailed'))
    }
  }

  const handleUpdateLastname = async () => {
    setError(null)
    setSuccess(null)
    const result = await updateLastname(lastname)
    if (result) {
      setSuccess(t('settings.lastnameUpdated'))
      setEditingLastname(false)
    } else {
      setError(t('settings.updateFailed'))
    }
  }

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault()
    if (newPassword !== confirmNewPassword) {
      setError(t('settings.passwordMismatch'))
      return
    }
    setError(null)
    setSuccess(null)
    const result = await changePassword(oldPassword, newPassword)
    if (result) {
      setSuccess(t('settings.passwordChanged'))
      setOldPassword('')
      setNewPassword('')
      setConfirmNewPassword('')
    } else {
      setError(t('settings.passwordChangeFailed'))
    }
  }

  const handleSignOut = async () => {
    await signOut()
    router.push('/')
  }

  if (!user) {
    return <div className="text-center mt-8">{t('settings.noProfile')}</div>
  }

  return (
      <div className="container mx-auto px-4 py-8 max-w-2xl">
        <h1 className="text-3xl font-bold mb-6">{t('settings.title')}</h1>
        <div className="space-y-6">
          <div className="bg-white shadow-md rounded-lg p-4 neomorphic mx-auto">
            <h2 className="text-2xl font-semibold mb-4">{t('settings.personalInfo')}</h2>
            <div className="space-y-4">
              <div>
                <label htmlFor="firstname" className="block text-sm font-medium text-gray-700 mb-1">{t('settings.firstname')}</label>
                <div className="flex items-center">
                  {editingFirstname ? (
                      <>
                        <input
                            type="text"
                            id="firstname"
                            value={firstname}
                            onChange={(e) => setFirstname(e.target.value)}
                            className="flex-grow mr-2 neomorphic-input"
                            autoFocus
                        />
                        <button
                            onClick={handleUpdateFirstname}
                            className="p-1 neomorphic-button text-green-500 mr-1"
                            disabled={user.pending}
                        >
                          <Check className="h-4 w-4" />
                        </button>
                        <button
                            onClick={() => {
                              setEditingFirstname(false)
                              setFirstname(user.firstname || '')
                            }}
                            className="p-1 neomorphic-button text-red-500"
                            disabled={user.pending}
                        >
                          <X className="h-4 w-4" />
                        </button>
                      </>
                  ) : (
                      <>
                        <span className="flex-grow">{user.firstname}</span>
                        <button
                            onClick={() => setEditingFirstname(true)}
                            className="p-1 neomorphic-button text-primary"
                        >
                          {t('settings.edit')}
                        </button>
                      </>
                  )}
                </div>
              </div>
              <div>
                <label htmlFor="lastname" className="block text-sm font-medium text-gray-700 mb-1">{t('settings.lastname')}</label>
                <div className="flex items-center">
                  {editingLastname ? (
                      <>
                        <input
                            type="text"
                            id="lastname"
                            value={lastname}
                            onChange={(e) => setLastname(e.target.value)}
                            className="flex-grow mr-2 neomorphic-input"
                            autoFocus
                        />
                        <button
                            onClick={handleUpdateLastname}
                            className="p-1 neomorphic-button text-green-500 mr-1"
                            disabled={user.pending}
                        >
                          <Check className="h-4 w-4" />
                        </button>
                        <button
                            onClick={() => {
                              setEditingLastname(false)
                              setLastname(user.lastname || '')
                            }}
                            className="p-1 neomorphic-button text-red-500"
                            disabled={user.pending}
                        >
                          <X className="h-4 w-4" />
                        </button>
                      </>
                  ) : (
                      <>
                        <span className="flex-grow">{user.lastname}</span>
                        <button
                            onClick={() => setEditingLastname(true)}
                            className="p-1 neomorphic-button text-primary"
                        >
                          {t('settings.edit')}
                        </button>
                      </>
                  )}
                </div>
              </div>
              <div>
                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">{t('settings.email')}</label>
                <div className="flex items-center">
                <span className="flex-grow flex items-center">
                  <Mail className="w-4 h-4 mr-2 text-gray-500" />
                  {user.email}
                </span>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white shadow-md rounded-lg p-4 neomorphic mx-auto">
            <h2 className="text-2xl font-semibold mb-4">{t('settings.changePassword')}</h2>
            <form onSubmit={handleChangePassword} className="space-y-3">
              <div>
                <label htmlFor="oldPassword" className="block text-sm font-medium text-gray-700">{t('settings.oldPassword')}</label>
                <input
                    type="password"
                    id="oldPassword"
                    value={oldPassword}
                    onChange={(e) => setOldPassword(e.target.value)}
                    className="mt-1 block w-full neomorphic-input"
                    required
                />
              </div>
              <div>
                <label htmlFor="newPassword" className="block text-sm font-medium text-gray-700">{t('settings.newPassword')}</label>
                <input
                    type="password"
                    id="newPassword"
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                    className="mt-1 block w-full neomorphic-input"
                    required
                />
              </div>
              <div>
                <label htmlFor="confirmNewPassword" className="block text-sm font-medium text-gray-700">{t('settings.confirmNewPassword')}</label>
                <input
                    type="password"
                    id="confirmNewPassword"
                    value={confirmNewPassword}
                    onChange={(e) => setConfirmNewPassword(e.target.value)}
                    className="mt-1 block w-full neomorphic-input"
                    required
                />
              </div>
              <button type="submit" className="neomorphic-button text-primary w-full py-2" disabled={user.pending}>
                {user.pending ? t('common.loading') : t('settings.changePassword')}
              </button>
            </form>
          </div>
        </div>

        {error && <div className="mt-4 text-red-500">{error}</div>}
        {success && <div className="mt-4 text-green-500">{success}</div>}
        <div className="mt-8">
          <button
              onClick={handleSignOut}
              className="w-full py-2 px-4 neomorphic-button text-red-500 hover:text-red-700 transition-colors"
          >
            {t('settings.signOut')}
          </button>
        </div>
      </div>
  )
}
