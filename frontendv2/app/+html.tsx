import { ScrollViewStyleReset } from 'expo-router/html';
import { type PropsWithChildren } from 'react';

export default function RootHtml({ children }: PropsWithChildren) {
  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="theme-color" content="#2563eb" />
        <title>GogoBudgeto - Smart Budgeting & Envelope Method App for Better Money Management</title>
        <meta name="description" content="GogoBudgeto is your smart budgeting companion using the proven envelope method. Track monthly spending, manage budget envelopes, and take control of your financial goals with our mobile-first budgeting app designed for better money management." />
        <meta name="keywords" content="gogobudgeto, smart budgeting, envelope method, monthly spending, budget envelopes, financial planning, money management, expense tracking, budget app, personal finance" />
        <meta name="author" content="GogoBudgeto" />
        <meta name="robots" content="index, follow" />
        <meta name="google-site-verification" content="rl_7wdoVEVDotXZPzDPR9WgT9-gBIJj6cLhpmzH45io" />
        <meta property="og:title" content="GogoBudgeto - Smart Budgeting & Envelope Method App" />
        <meta property="og:description" content="Take control of your finances with GogoBudgeto's smart budgeting tools. Use the proven envelope method to track monthly spending and achieve your financial goals." />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="/logo.png" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GogoBudgeto - Smart Budgeting App" />
        <meta name="twitter:description" content="Smart budgeting made simple. Track spending with envelope method and achieve financial goals with GogoBudgeto." />
        <link rel="icon" href="/favicon.ico" />
        <link rel="canonical" href="https://gogobudgeto.expo.app/" />
        <ScrollViewStyleReset />
      </head>
      <body>{children}</body>
    </html>
  );
} 