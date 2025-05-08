import React from 'react';

const FooterWeb: React.FC = () => (
  <footer
    style={{
      width: '100%',
      height: 48,
      background: '#f1f5f9',
      borderTop: '1px solid #e2e8f0',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      position: 'fixed',
      bottom: 0,
      left: 0,
      zIndex: 1000,
      fontSize: 14,
      color: '#64748b',
    }}
    role="contentinfo"
  >
    © {new Date().getFullYear()} GogoBudgeto. All rights reserved.
  </footer>
);

export default FooterWeb; 