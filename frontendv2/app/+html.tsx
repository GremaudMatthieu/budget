import { ScrollViewStyleReset } from 'expo-router/html';
import { type PropsWithChildren } from 'react';

export default function RootHtml({ children }: PropsWithChildren) {
  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="theme-color" content="#2563eb" />
        <title>GogoBudgeto - Budget Management App</title>
        <meta name="description" content="Track your budgets, envelopes, and plans easily with GogoBudgeto. Mobile-first budget management app for better financial control." />
        <meta name="keywords" content="budget, budgeting, envelope method, financial planning, money management, expense tracking" />
        <meta name="author" content="GogoBudgeto" />
        <meta name="robots" content="index, follow" />
        <meta name="google-site-verification" content="rl_7wdoVEVDotXZPzDPR9WgT9-gBIJj6cLhpmzH45io" />
        <meta property="og:title" content="GogoBudgeto" />
        <meta property="og:description" content="Track your budgets, envelopes, and plans easily. Mobile-first and accessible." />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="/logo.png" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GogoBudgeto" />
        <meta name="twitter:description" content="Track your budgets, envelopes, and plans easily. Mobile-first and accessible." />
        <link rel="icon" href="/favicon.ico" />
        <link rel="canonical" href="https://gogobudgeto.expo.app/" />
        <ScrollViewStyleReset />
      </head>
      <body>{children}</body>
    </html>
  );
} 