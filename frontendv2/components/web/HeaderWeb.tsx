import React, { useState, useRef, useEffect } from 'react';
import { useRouter, useSegments } from 'expo-router';
import { useAuth } from '@/contexts/AuthContext';
import { useTranslation } from '@/utils/useTranslation';
import { Ionicons } from '@expo/vector-icons';

/**
 * Web-only header for the Budget App.
 * Accessible, responsive, and styled for web with smooth animations.
 */

const HeaderWeb: React.FC = () => {
  const router = useRouter();
  const segments = useSegments();
  const current = '/' + segments.join('/');
  const { isAuthenticated } = useAuth();
  const { t } = useTranslation();
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [isAnimating, setIsAnimating] = useState(false);
  const drawerRef = useRef<HTMLDivElement>(null);

  // Navigation links with translation keys
  const NAV_LINKS = [
    { labelKey: 'navigation.dashboard', route: '/(tabs)', icon: 'home-outline' as const },
    { labelKey: 'navigation.envelopes', route: '/(tabs)/envelopes', icon: 'wallet-outline' as const },
    { labelKey: 'navigation.budgetPlans', route: '/(tabs)/budget-plans', icon: 'pie-chart-outline' as const },
    { labelKey: 'navigation.profile', route: '/(tabs)/profile', icon: 'person-outline' as const },
  ];

  const handleLogoClick = () => {
    if (isAuthenticated) {
      router.replace('/(tabs)');
    } else {
      router.replace('/(auth)');
    }
  };

  const openDrawer = () => {
    setIsAnimating(true);
    setDrawerOpen(true);
  };

  const closeDrawer = () => {
    setIsAnimating(true);
    setDrawerOpen(false);
    setTimeout(() => setIsAnimating(false), 300);
  };

  // Close drawer on outside click or ESC
  useEffect(() => {
    if (!drawerOpen) return;
    function handleClick(e: MouseEvent) {
      if (drawerRef.current && !drawerRef.current.contains(e.target as Node)) {
        closeDrawer();
      }
    }
    function handleEsc(e: KeyboardEvent) {
      if (e.key === 'Escape') closeDrawer();
    }
    document.addEventListener('mousedown', handleClick);
    document.addEventListener('keydown', handleEsc);
    return () => {
      document.removeEventListener('mousedown', handleClick);
      document.removeEventListener('keydown', handleEsc);
    };
  }, [drawerOpen]);

  return (
    <header
      className="w-full h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 md:px-8 fixed top-0 left-0 z-50 shadow-sm"
      role="banner"
    >
      {/* Logo */}
      <button
        className="font-bold text-xl text-primary-600 tracking-wide bg-none border-none cursor-pointer p-2 -ml-2 rounded-lg hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-200 transition-all duration-200"
        style={{ background: 'none', border: 'none' }}
        onClick={handleLogoClick}
        aria-label={t('navigation.goToHomepage')}
      >
        GogoBudgeto
      </button>
      
      {isAuthenticated && (
        <>
          {/* Desktop Navigation */}
          <nav aria-label="Main navigation" className="hidden md:flex items-center gap-1">
            {NAV_LINKS.map(link => {
              const isActive = current === link.route || (link.route === '/(tabs)' && current === '/(tabs)/index');
              return (
                <button
                  key={link.route}
                  onClick={() => router.replace(link.route as any)}
                  aria-current={isActive ? 'page' : undefined}
                  className={`relative flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-200 ${
                    isActive 
                      ? 'text-primary-700 bg-primary-100 shadow-sm' 
                      : 'text-slate-600 hover:text-primary-600 hover:bg-primary-50'
                  }`}
                  style={{ background: 'none', border: 'none' }}
                >
                  <Ionicons 
                    name={link.icon} 
                    size={18} 
                    color={isActive ? '#0c6cf2' : '#64748b'} 
                  />
                  <span>{t(link.labelKey)}</span>
                  {isActive && (
                    <div className="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-primary-600 rounded-full" />
                  )}
                </button>
              );
            })}
          </nav>

          {/* Mobile Menu Button */}
          <button
            className={`flex md:hidden items-center justify-center w-10 h-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-200 transition-all duration-200 ${
              drawerOpen ? 'bg-primary-100 text-primary-600' : 'hover:bg-primary-50 text-slate-600'
            }`}
            aria-label={drawerOpen ? t('navigation.closeMenu') : t('navigation.openMenu')}
            onClick={drawerOpen ? closeDrawer : openDrawer}
          >
            <div className="relative w-6 h-6">
              <span
                className={`absolute h-0.5 bg-current rounded-full transition-all duration-300 ${
                  drawerOpen ? 'w-6 top-3 rotate-45' : 'w-6 top-1'
                }`}
              />
              <span
                className={`absolute h-0.5 bg-current rounded-full transition-all duration-300 ${
                  drawerOpen ? 'w-0 top-3 opacity-0' : 'w-6 top-3'
                }`}
              />
              <span
                className={`absolute h-0.5 bg-current rounded-full transition-all duration-300 ${
                  drawerOpen ? 'w-6 top-3 -rotate-45' : 'w-6 top-5'
                }`}
              />
            </div>
          </button>

          {/* Mobile Drawer */}
          {(drawerOpen || isAnimating) && (
            <div className="fixed inset-0 z-50 flex md:hidden">
              {/* Backdrop */}
              <div 
                className={`fixed inset-0 bg-black/40 transition-opacity duration-300 ${
                  drawerOpen ? 'opacity-100' : 'opacity-0'
                }`}
                aria-hidden="true" 
              />
              
              {/* Drawer */}
              <div
                ref={drawerRef}
                className={`relative ml-auto w-80 max-w-full h-full bg-white border-l border-slate-200 shadow-2xl flex flex-col transition-transform duration-300 ease-out overflow-hidden ${
                  drawerOpen ? 'translate-x-0' : 'translate-x-full'
                }`}
                style={{ backgroundColor: '#ffffff' }}
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-label={t('navigation.openMenu')}
              >
                {/* Drawer Header */}
                <div className="flex items-center justify-between p-6 border-b border-slate-200">
                  <h2 className="text-lg font-semibold text-slate-800">Navigation</h2>
                  <button
                    className="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:text-primary-600 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-200 transition-all duration-200"
                    aria-label={t('navigation.closeMenu')}
                    onClick={closeDrawer}
                  >
                    <Ionicons name="close" size={20} />
                  </button>
                </div>

                {/* Navigation Links - Scrollable */}
                <div className="flex-1 overflow-y-auto">
                  <nav className="flex flex-col p-6 gap-2">
                  {NAV_LINKS.map((link, index) => {
                    const isActive = current === link.route || (link.route === '/(tabs)' && current === '/(tabs)/index');
                    return (
                      <button
                        key={link.route}
                        onClick={() => { 
                          closeDrawer(); 
                          setTimeout(() => router.replace(link.route as any), 150);
                        }}
                        aria-current={isActive ? 'page' : undefined}
                        className={`flex items-center gap-4 p-4 rounded-xl text-left font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-200 ${
                          isActive 
                            ? 'text-primary-700 bg-primary-100 shadow-sm scale-[1.02]' 
                            : 'text-slate-700 hover:text-primary-600 hover:bg-primary-50 hover:scale-[1.01]'
                        }`}
                        style={{ 
                          background: 'none', 
                          border: 'none',
                          transform: `translateY(${drawerOpen ? 0 : 20}px)`,
                          opacity: drawerOpen ? 1 : 0,
                          transition: `all 0.3s ease-out ${index * 50}ms`
                        }}
                      >
                        <div className={`p-2 rounded-lg ${isActive ? 'bg-primary-200' : 'bg-slate-100'}`}>
                          <Ionicons 
                            name={link.icon} 
                            size={20} 
                            color={isActive ? '#0c6cf2' : '#64748b'} 
                          />
                        </div>
                        <div className="flex flex-col">
                          <span className="text-base">{t(link.labelKey)}</span>
                          {isActive && (
                            <span className="text-xs text-primary-500 font-normal">{t('navigation.current')}</span>
                          )}
                        </div>
                        {isActive && (
                          <div className="ml-auto w-2 h-2 bg-primary-600 rounded-full" />
                        )}
                      </button>
                    );
                  })}
                  </nav>
                </div>

                {/* Drawer Footer */}
                <div className="mt-auto p-6 border-t border-slate-200">
                  <div className="text-xs text-slate-500 text-center">
                    GogoBudgeto © 2024
                  </div>
                </div>
              </div>
            </div>
          )}
        </>
      )}
    </header>
  );
};

export default HeaderWeb; 