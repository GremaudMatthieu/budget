'use client'

import {useEffect, useState} from 'react'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { useUser } from '../domain/user/userHooks'
import { useTranslation } from '../hooks/useTranslation'
import { Home, User, Menu, X, PieChart, Mail } from 'lucide-react'
import { useAppContext } from '../providers'
import { LanguageSelector } from './LanguageSelector'
import {useEnvelopes} from "../domain/envelope/envelopeHooks";
import ErrorModal from "./ErrorModal";

export default function Header() {
    const pathname = usePathname()

    const { t } = useTranslation()
    const [isMenuOpen, setIsMenuOpen] = useState(false)
    const { state: { isAuthenticated: appIsAuthenticated, user: appUser, loading: appLoading } } = useAppContext()


    const toggleMenu = () => setIsMenuOpen(!isMenuOpen)

    if (appLoading) {
        return null // Don't render the header while loading
    }

    const NavLink = ({ href, children, onClick }: { href: string; children: React.ReactNode; onClick?: (e: React.MouseEvent<HTMLAnchorElement>) => void }) => (
        <Link
            href={href}
            className={`block py-2 px-0 md:px-2 ${pathname === href ? 'text-primary font-bold' : 'text-gray-600'}`}
            onClick={(e) => {
                if (onClick) {
                    onClick(e)
                } else {
                    setIsMenuOpen(false)
                }
            }}
        >
            {children}
        </Link>
    )
    return (
        <header className="bg-white shadow-md">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center py-4">
                    <Link href="/" className="flex items-center text-xl font-bold text-primary">
                        <Home className="w-6 h-6 mr-2" />
                        <span className="hidden sm:inline">{t('header.title')}</span>
                    </Link>
                    <div className="flex items-center">
                        <button
                            className="sm:hidden text-gray-500 hover:text-gray-900"
                            onClick={toggleMenu}
                        >
                            {isMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                        </button>
                        <nav className={`${isMenuOpen ? 'block' : 'hidden'} sm:block absolute sm:relative top-16 sm:top-0 left-0 right-0 bg-white sm:bg-transparent shadow-md sm:shadow-none z-10 sm:z-auto`}>
                            <ul className="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4 p-4 sm:p-0">
                                {appIsAuthenticated ? (
                                    <>
                                        <li>
                                            <NavLink href="/dashboard">
                                                <PieChart className="inline-block mr-1 h-4 w-4" />
                                                {t('nav.dashboard')}
                                            </NavLink>
                                        </li>
                                        <li>
                                            <NavLink href="/envelopes" onClick={(e) => {
                                                if (pathname === '/envelopes') {
                                                    e.preventDefault();
                                                }
                                                setIsMenuOpen(false);
                                            }}>
                                                <Mail className="inline-block mr-1 h-4 w-4" />
                                                {t('nav.envelopes')}
                                            </NavLink>
                                        </li>
                                        <li>
                                            <NavLink href="/settings">
                                                <User className="inline-block mr-1 h-4 w-4" />
                                                {t('nav.settings')}
                                            </NavLink>
                                        </li>
                                        <li>
                                            <LanguageSelector />
                                        </li>
                                    </>
                                ) : (
                                    <>
                                        <li>
                                            <NavLink href="/signin">{t('header.signIn')}</NavLink>
                                        </li>
                                        <li>
                                            <NavLink href="/signup">{t('header.signUp')}</NavLink>
                                        </li>
                                        <li>
                                            <LanguageSelector />
                                        </li>
                                    </>
                                )}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </header>
    )
}
