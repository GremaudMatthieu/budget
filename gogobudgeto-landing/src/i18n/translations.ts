export const translations = {
  fr: {
    // Meta
    'meta.title': 'GoGoBudgeto - App Budget & Finances Personnelles Sans Connexion Bancaire',
    'meta.description': 'GoGoBudgeto : outil de finances personnelles sécurisé sans connexion à vos comptes bancaires. Budgétisation intelligente avec système d\'enveloppes et analyses personnalisées.',
    'meta.keywords': 'GoGoBudgeto, gogobudgeto, Gogobudgeto, gogo budgeto, budget, finances personnelles, épargne, planification budgétaire, gestion financière, économies, app budget, application budget, outil budget, logiciel budget, budget mensuel, enveloppes budget, budget familial, gérer ses finances, contrôler ses dépenses, planifier son budget, économiser de l\'argent, suivi des dépenses, budget personnel, finances familiales, gestion de budget, budgétisation, outil financier',
    
    // Header
    'header.login': 'Se connecter',
    'header.lang': 'FR',
    'header.title': 'Outil de finances personnelles sans connexion bancaire',
    'header.subtitle': 'Créez vos plans budgétaires mensuels en toute simplicité et gérez vos enveloppes de budget. Aucune connexion à vos comptes bancaires requise.',
    'header.cta.primary': 'Commencer',
    'header.cta.secondary': 'Voir la démo',
    
    // Features
    'features.title': 'Nos fonctionnalités',
    'features.subtitle': 'Découvrez comment GoGoBudgeto peut transformer votre gestion financière',
    'features.smart.title': 'Plans budgétaires mensuels',
    'features.smart.description': 'Créez facilement vos plans budgétaires mensuels pour savoir exactement combien vous économisez chaque mois.',
    'features.planning.title': 'Enveloppes de budget',
    'features.planning.description': 'Gérez vos enveloppes de budget basées sur vos plans budgétaires pour contrôler vos dépenses par catégorie.',
    'features.analysis.title': 'Analyses intelligentes',
    'features.analysis.description': 'Conseils financiers et analyses personnalisés pour optimiser votre épargne et réduire vos dépenses.',
    
    // Savings
    'savings.title': 'Économisez jusqu\'à 20%',
    'savings.subtitle': 'de vos revenus grâce à nos techniques de budgétisation avancées.',
    'savings.needs': 'Besoins',
    'savings.wants': 'Envies',
    'savings.savings': 'Épargne',
    
    
    // CTA
    'cta.title': 'Prêt à prendre le contrôle de vos finances?',
    'cta.subtitle': 'Rejoignez des milliers d\'utilisateurs qui ont déjà transformé leur gestion budgétaire.',
    'cta.primary': 'Commencer',
    'cta.disclaimer': 'En vous inscrivant, vous acceptez nos Conditions et notre Politique de confidentialité',
    
    // Footer
    'footer.tagline': 'Une budgétisation intelligente pour votre liberté financière.',
    'footer.copyright': '© 2025 GoGoBudgeto. Tous droits réservés.'
  },
  en: {
    // Meta
    'meta.title': 'GoGoBudgeto - Budget App & Personal Finance Tool Without Bank Connection',
    'meta.description': 'GoGoBudgeto: secure personal finance tool without connecting to your bank accounts. Smart budgeting with envelope system and personalized analysis.',
    'meta.keywords': 'GoGoBudgeto, gogobudgeto, Gogobudgeto, gogo budgeto, budget, personal finance, savings, budget planning, financial management, money saving, budget app, budgeting app, personal budget, budget tool, financial tool, budget software, monthly budget, envelope budgeting, family budget, manage finances, track expenses, budget planner, save money, expense tracker, financial planning, money management, budgeting system, finance app',
    
    // Header
    'header.login': 'Log in',
    'header.lang': 'EN',
    'header.title': 'Personal finance tool without bank connection',
    'header.subtitle': 'Create your monthly budget plans with simplicity and manage your budget envelopes. No bank account connection required.',
    'header.cta.primary': 'Get started',
    'header.cta.secondary': 'Watch demo',
    
    // Features
    'features.title': 'Our features',
    'features.subtitle': 'Discover how GoGoBudgeto can transform your financial management',
    'features.smart.title': 'Monthly budget plans',
    'features.smart.description': 'Easily create your monthly budget plans to know exactly how much you save each month.',
    'features.planning.title': 'Budget envelopes',
    'features.planning.description': 'Manage your budget envelopes based on your budget plans to control spending by category.',
    'features.analysis.title': 'Smart analysis',
    'features.analysis.description': 'Personalized financial advice and analysis to optimize your savings and reduce expenses.',
    
    // Savings
    'savings.title': 'Save up to 20%',
    'savings.subtitle': 'of your income with our advanced budgeting techniques.',
    'savings.needs': 'Needs',
    'savings.wants': 'Wants',
    'savings.savings': 'Savings',
    
    
    // CTA
    'cta.title': 'Ready to take control of your finances?',
    'cta.subtitle': 'Join thousands of users who have already transformed their budget management.',
    'cta.primary': 'Get started',
    'cta.disclaimer': 'By signing up, you agree to our Terms and Privacy Policy',
    
    // Footer
    'footer.tagline': 'Smart budgeting for your financial freedom.',
    'footer.copyright': '© 2025 GoGoBudgeto. All rights reserved.'
  }
} as const;

export type TranslationKey = keyof typeof translations.fr;
export type Locale = 'fr' | 'en';