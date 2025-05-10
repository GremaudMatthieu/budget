import { ScrollViewStyleReset } from 'expo-router/html';
import { type PropsWithChildren } from 'react';

export default function RootHtml({ children }: PropsWithChildren) {
  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="theme-color" content="#2563eb" />
        <meta name="description" content="Track your budgets, envelopes, and plans easily. Mobile-first, accessible, and SEO-friendly." />
        <meta property="og:title" content="Budget Management App" />
        <meta property="og:description" content="Track your budgets, envelopes, and plans easily. Mobile-first, accessible, and SEO-friendly." />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="/logo.png" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Budget Management App" />
        <meta name="twitter:description" content="Track your budgets, envelopes, and plans easily. Mobile-first, accessible, and SEO-friendly." />
        <link rel="icon" href="/favicon.ico" />
        <link rel="canonical" href="https://yourdomain.com/" />
        <ScrollViewStyleReset />
      </head>
      <body>{children}</body>
    </html>
  );
} 