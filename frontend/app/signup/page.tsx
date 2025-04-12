"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"
import Link from "next/link"
import { useUser } from "../domain/user/userHooks"
import { useTranslation } from "../hooks/useTranslation"
import { TermsModal } from "../components/TermsModal"
import { v4 as uuidv4 } from "uuid"
import { useError } from "../contexts/ErrorContext"
import { useValidMessage } from "../contexts/ValidContext"
import InputText from "../components/inputs/envelopeInput/textInput"
import TextInput from "../components/inputs/formInputs/textInputs"
import PasswordInput from "../components/inputs/passwordInput"
import CustomSelect from "../components/inputs/customSelect"
import { languageOptions } from "../constants/languageOptions"
import ActionButton from "../components/buttons/formButton/formButton"
import GoogleSignInButton from '../components/auth/GoogleSignInButton'

export default function SignUp() {
    const [firstname, setFirstname] = useState("")
    const [lastname, setLastname] = useState("")
    const [email, setEmail] = useState("")
    const [password, setPassword] = useState("")
    const [confirmPassword, setConfirmPassword] = useState("")
    const [consentGiven, setConsentGiven] = useState(false)
    const [isTermsModalOpen, setIsTermsModalOpen] = useState(false)
    const [successMessage, setSuccessMessage] = useState("")
    const [languagePreference, setLanguagePreference] = useState("en")
    const { createUser, loading } = useUser()
    const router = useRouter()
    const { t, setLanguage } = useTranslation()
    const { error, setError } = useError()
    const { setValidMessage } = useValidMessage()

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault()
        if (password !== confirmPassword) {
            alert(t("signup.passwordMismatch"))
            return
        }
        const uuid = uuidv4()
        const success = await createUser(
            {
                uuid,
                firstname,
                lastname,
                email,
                password,
                languagePreference,
                consentGiven,
            },
            setError,
        )
        if (success) {
            setSuccessMessage(t("signup.successMessage"))
            setLanguage(languagePreference) // Set the site's locale
            setTimeout(() => {
                router.push("/signin")
            }, 3000)
        }
    }

    return (
        <div className="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-background">
            <div className="max-w-md w-full space-y-8">
                <div className="text-center">
                    <h1 className="text-3xl font-extrabold text-foreground">{t("signup.title")}</h1>
                </div>
                {successMessage ? (
                    <div className="mb-4 p-4 bg-green-100 text-green-700 rounded-md">{successMessage}</div>
                ) : (
                    <>
                        <div className="mt-6">
                            <GoogleSignInButton />
                            
                            <div className="mt-6 relative">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-gray-300"></div>
                                </div>
                                <div className="relative flex justify-center text-sm">
                                    <span className="px-2 bg-background text-gray-500">
                                        {t('signin.or')}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <form onSubmit={handleSubmit} className="mt-6 space-y-6 neomorphic p-8 rounded-lg">
                            <div className="rounded-md space-y-4">
                                <div>
                                    <TextInput
                                        id="firstname"
                                        name="firstname"
                                        type="text"
                                        value={firstname}
                                        onChange={(e) => setFirstname(e.target.value)}
                                        className=""
                                        label={t("signup.firstname")}
                                        placeholder={t("signup.firstname")}
                                    />
                                </div>
                                <div>
                                    <label htmlFor="lastname" className="sr-only">
                                    </label>
                                    <TextInput
                                        id="lastname"
                                        name="lastname"
                                        type="text"
                                        value={lastname}
                                        onChange={(e) => setLastname(e.target.value)}
                                        className=""
                                        label={t("signup.lastname")}

                                        placeholder={t("signup.lastname")}
                                    />

                                </div>
                                <div>
                                    <label htmlFor="email" className="sr-only">
                                    </label>
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
                                </div>
                                <div>
                                    <PasswordInput
                                        id="password"
                                        value={password}
                                        onChange={setPassword}
                                        placeholder={t("signup.password")}
                                        label={t("signup.password")}
                                        required
                                    />
                                </div>
                                <div>
                                    <PasswordInput
                                        id="confirmPassword"
                                        value={confirmPassword}
                                        onChange={setConfirmPassword}
                                        placeholder={t("signup.confirmPassword")}
                                        label={t("signup.confirmPassword")}
                                        required
                                    />
                                </div>
                                <div>
                                    <label htmlFor="languagePreference" className="block text-sm font-medium text-gray-700 mb-1">
                                        {t("signup.languagePreference")}
                                    </label>
                                    <CustomSelect
                                        options={languageOptions}
                                        onChange={(e) => setLanguagePreference(e.target.value)}
                                        value={languagePreference}
                                        className="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                                        t={t}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center">
                                <input
                                    id="consentGiven"
                                    name="consentGiven"
                                    type="checkbox"
                                    checked={consentGiven}
                                    onChange={(e) => setConsentGiven(e.target.checked)}
                                    className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                    required
                                />
                                <label htmlFor="consentGiven" className="ml-2 block text-sm text-foreground">
                                    {t("signup.consentPart1")}{" "}
                                    <button
                                        type="button"
                                        onClick={() => setIsTermsModalOpen(true)}
                                        className="font-medium text-primary hover:text-primary-dark"
                                    >
                                        {t("signup.termsAndConditions")}
                                    </button>
                                </label>
                            </div>
                            {error && <p className="text-red-500 text-sm mt-2">{error}</p>}
                            <div>
                                <ActionButton type="submit" label={loading ? t("signup.signingUp") : t("signup.signUp")} disabled={loading} className="" />
                            </div>
                        </form>
                    </>
                )}
                <p className="mt-2 text-center text-sm text-foreground">
                    {t("signup.alreadyHaveAccount")}{" "}
                    <Link href="/signin" className="font-medium text-primary hover:text-primary-dark">
                        {t("signup.signIn")}
                    </Link>
                </p>
            </div>
            <TermsModal isOpen={isTermsModalOpen} onClose={() => setIsTermsModalOpen(false)} />
        </div>
    )
}
