'use client'

import { useCallback } from 'react'
import { translations } from '../i18n/translations'
import { useLanguage } from '../contexts/LanguageContext'

export function useTranslation() {
    const { language, setLanguage } = useLanguage()

    const t = useCallback((key: string) => {
        const keys = key.split('.')
        let value: any = translations[language]
        for (const k of keys) {
            value = value[k]
            if (value === undefined) {
                return key
            }
        }
        return value
    }, [language])

    return { t, setLanguage, language }
}
