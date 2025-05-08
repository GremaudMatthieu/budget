import React from 'react';
import { useRouter, useSegments } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

const TABS = [
  {
    route: '/(tabs)',
    label: 'Dashboard',
    icon: 'home-outline',
  },
  {
    route: '/(tabs)/envelopes',
    label: 'Envelopes',
    icon: 'wallet-outline',
  },
  {
    route: '/(tabs)/budget-plans',
    label: 'Budget Plans',
    icon: 'pie-chart-outline',
  },
  {
    route: '/(tabs)/profile',
    label: 'Profile',
    icon: 'person-outline',
  },
];

const BottomTabWeb: React.FC = () => {
  const router = useRouter();
  const segments = useSegments();
  const current = '/' + segments.join('/');

  return (
    <nav
      aria-label="Main navigation"
      style={{
        width: '100%',
        height: 64,
        background: '#fff',
        borderTop: '1px solid #e2e8f0',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        position: 'fixed',
        bottom: 0,
        left: 0,
        zIndex: 1000,
        boxShadow: '0 -1px 4px rgba(0,0,0,0.03)',
      }}
    >
      <ul style={{ display: 'flex', gap: 48, margin: 0, padding: 0, listStyle: 'none' }}>
        {TABS.map(tab => {
          const isActive = current === tab.route || (tab.route === '/(tabs)' && current === '/(tabs)/index');
          return (
            <li key={tab.route}>
              <button
                aria-current={isActive ? 'page' : undefined}
                onClick={() => router.replace(tab.route as any)}
                style={{
                  background: 'none',
                  border: 'none',
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  color: isActive ? '#0c6cf2' : '#64748b',
                  fontWeight: isActive ? 600 : 500,
                  fontSize: 13,
                  cursor: 'pointer',
                  outline: 'none',
                }}
              >
                <Ionicons name={tab.icon as any} size={24} color={isActive ? '#0c6cf2' : '#64748b'} />
                <span>{tab.label}</span>
              </button>
            </li>
          );
        })}
      </ul>
    </nav>
  );
};

export default BottomTabWeb; 