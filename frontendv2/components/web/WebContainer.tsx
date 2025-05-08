import React from 'react';

/**
 * Responsive container for web screens.
 * Adds max width, centers content, and adds padding for header and bottom tab.
 */
const WebContainer: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <main
    className="mx-auto w-full min-h-screen pt-14 pb-72 px-4 sm:px-8 md:px-12 lg:px-24 max-w-7xl"
    role="main"
  >
    {children}
  </main>
);

export default WebContainer; 