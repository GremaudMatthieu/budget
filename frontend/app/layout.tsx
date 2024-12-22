import './globals.css'
import { Inter } from 'next/font/google'
import { Providers } from './providers'
import { AppContent } from './components/AppContent'
import { LanguageProvider } from './contexts/LanguageContext'

const inter = Inter({ subsets: ['latin'] })

export const metadata = {
    title: 'GoGoBudgeto App',
    description: 'Manage your budget',
}

export default function RootLayout({
                                       children,
                                   }: {
    children: React.ReactNode
}) {
    return (
        <html lang="en">
        <head>
            <style>{`
          :root {
            --chart-1: 200 100% 50%;
            --chart-2: 150 100% 50%;
            --chart-3: 100 100% 50%;
            --chart-4: 50 100% 50%;
            --chart-5: 0 100% 50%;
          }
        `}</style>
        </head>
        <body className={inter.className}>
        <LanguageProvider>
            <Providers>
                <div className="flex flex-col min-h-screen bg-background">
                    <AppContent>{children}</AppContent>
                </div>
            </Providers>
        </LanguageProvider>
        </body>
        </html>
    )
}
