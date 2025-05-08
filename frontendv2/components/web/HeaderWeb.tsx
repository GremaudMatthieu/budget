import React, { useState, useRef, useEffect } from 'react';
import { useRouter, useSegments } from 'expo-router';
import { useAuth } from '@/contexts/AuthContext';

/**
 * Web-only header for the Budget App.
 * Accessible, responsive, and styled for web.
 */
const NAV_LINKS = [
  { label: 'Dashboard', route: '/(tabs)' },
  { label: 'Envelopes', route: '/(tabs)/envelopes' },
  { label: 'Budget Plans', route: '/(tabs)/budget-plans' },
  { label: 'Profile', route: '/(tabs)/profile' },
];

const HeaderWeb: React.FC = () => {
  const router = useRouter();
  const segments = useSegments();
  const current = '/' + segments.join('/');
  const { isAuthenticated } = useAuth();
  const [drawerOpen, setDrawerOpen] = useState(false);
  const drawerRef = useRef<HTMLDivElement>(null);

  const handleLogoClick = () => {
    if (isAuthenticated) {
      router.replace('/(tabs)');
    } else {
      router.replace('/(auth)');
    }
  };

  // Close drawer on outside click or ESC
  useEffect(() => {
    if (!drawerOpen) return;
    function handleClick(e: MouseEvent) {
      if (drawerRef.current && !drawerRef.current.contains(e.target as Node)) {
        setDrawerOpen(false);
      }
    }
    function handleEsc(e: KeyboardEvent) {
      if (e.key === 'Escape') setDrawerOpen(false);
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
      className="w-full h-14 bg-white border-b border-slate-200 flex items-center justify-between px-8 fixed top-0 left-0 z-50 shadow-sm"
      role="banner"
    >
      <button
        className="font-bold text-xl text-primary-600 tracking-wide bg-none border-none cursor-pointer p-0 m-0 focus:outline-none"
        style={{ background: 'none', border: 'none' }}
        onClick={handleLogoClick}
        aria-label="Go to homepage"
      >
        GogoBudgeto
      </button>
      {isAuthenticated && (
        <>
          {/* Desktop Nav */}
          <nav aria-label="Main navigation" className="hidden md:flex items-center gap-6">
            {NAV_LINKS.map(link => {
              const isActive = current === link.route || (link.route === '/(tabs)' && current === '/(tabs)/index');
              return (
                <button
                  key={link.route}
                  onClick={() => router.replace(link.route as any)}
                  aria-current={isActive ? 'page' : undefined}
                  className={`bg-none border-none font-medium text-base cursor-pointer px-2 py-1 focus:outline-none transition-colors duration-150 ${isActive ? 'text-primary-600 border-b-2 border-primary-600' : 'text-slate-500 hover:text-primary-600'}`}
                  style={{ background: 'none', border: 'none' }}
                >
                  {link.label}
                </button>
              );
            })}
          </nav>
          {/* Burger for mobile */}
          <button
            className="flex md:hidden items-center justify-center w-10 h-10 rounded focus:outline-none"
            aria-label="Open navigation menu"
            onClick={() => setDrawerOpen(true)}
          >
            <svg width="24" height="24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600"><line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="18" x2="21" y2="18" /></svg>
          </button>
          {/* Side Drawer */}
          {drawerOpen && (
            <div className="fixed inset-0 z-50 flex">
              {/* Overlay */}
              <div className="fixed inset-0 bg-black/30 transition-opacity" aria-hidden="true" />
              {/* Drawer */}
              <div
                ref={drawerRef}
                className="relative ml-auto w-64 max-w-full h-full bg-white shadow-lg flex flex-col py-8 px-6 animate-slide-in-right"
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
              >
                <button
                  className="absolute top-4 right-4 text-slate-500 hover:text-primary-600 focus:outline-none"
                  aria-label="Close navigation menu"
                  onClick={() => setDrawerOpen(false)}
                >
                  <svg width="24" height="24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                </button>
                <nav className="flex flex-col gap-6 mt-8">
                  {NAV_LINKS.map(link => {
                    const isActive = current === link.route || (link.route === '/(tabs)' && current === '/(tabs)/index');
                    return (
                      <button
                        key={link.route}
                        onClick={() => { setDrawerOpen(false); router.replace(link.route as any); }}
                        aria-current={isActive ? 'page' : undefined}
                        className={`text-left font-medium text-lg px-2 py-2 rounded transition-colors duration-150 ${isActive ? 'text-primary-600 bg-primary-50' : 'text-slate-700 hover:text-primary-600 hover:bg-primary-50'}`}
                      >
                        {link.label}
                      </button>
                    );
                  })}
                </nav>
              </div>
            </div>
          )}
        </>
      )}
    </header>
  );
};

export default HeaderWeb;

/* Add this to your global CSS or Tailwind config:
@keyframes slide-in-right {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}
.animate-slide-in-right {
  animation: slide-in-right 0.25s cubic-bezier(0.4,0,0.2,1);
}
*/ 