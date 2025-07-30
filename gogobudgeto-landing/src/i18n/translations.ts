export const translations = {
  fr: {
    // Meta
    'meta.title': 'GoGoBudgeto - App Budget Gratuite 50/30/20 & Enveloppes d\'Économies',
    'meta.description': 'GoGoBudgeto : app budget gratuite qui génère automatiquement vos plans budgétaires mensuels 50/30/20. Créez vos enveloppes pour organiser vos économies par objectifs. Comparez votre budget réel vs idéal 50/30/20.',
    'meta.keywords': 'GoGoBudgeto, gogobudgeto gratuit, app budget gratuite, gogobudgeto app, budget gogobudgeto, plans budgétaires mensuels, enveloppes économies, budget réel vs 50/30/20, générer plan budget, créer enveloppes économies, historique mouvements enveloppes, logiciel budget gratuit, application budget gratuite, outil budget français gratuit',
    
    // Header
    'header.login': 'Se connecter',
    'header.lang': 'FR',
    'header.title': 'Budget 50/30/20 et Méthode des Enveloppes pour Partitionner vos Économies',
    'header.subtitle': 'GoGoBudgeto génère vos plans budgétaires mensuels selon la règle 50/30/20 et utilise les enveloppes pour organiser vos économies par catégorie. Gratuit et sans connexion bancaire.',
    'header.cta.primary': 'Commencer Gratuitement',
    'header.cta.secondary': 'Voir la démo',
    
    // Features
    'features.title': 'Nos fonctionnalités',
    'features.subtitle': 'Découvrez comment GoGoBudgeto peut transformer votre gestion financière',
    'features.smart.title': 'Plans Budgétaires 50/30/20',
    'features.smart.description': 'GoGoBudgeto génère automatiquement vos plans budgétaires selon la règle 50/30/20 : 50% besoins, 30% envies, 20% épargne pour optimiser votre budget mensuel.',
    'features.planning.title': 'Système d\'Enveloppes',
    'features.planning.description': 'Partitionnez vos économies avec la méthode des enveloppes. Organisez votre épargne par objectifs et catégories pour un contrôle optimal de vos finances.',
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
    'cta.subtitle': 'Rejoignez des milliers d\'utilisateurs qui ont déjà transformé leur gestion budgétaire avec GoGoBudgeto gratuit.',
    'cta.primary': 'Commencer Gratuitement',
    'cta.disclaimer': 'En vous inscrivant, vous acceptez nos Conditions et notre Politique de confidentialité',
    
    // Footer
    'footer.tagline': 'Une budgétisation intelligente pour votre liberté financière.',
    'footer.copyright': '© 2025 GoGoBudgeto. Tous droits réservés.'
  },
  en: {
    // Meta
    'meta.title': 'GoGoBudgeto - Free Budget App that Generates 50/30/20 Plans & Savings Envelopes',
    'meta.description': 'GoGoBudgeto: free budget app that automatically generates your monthly 50/30/20 budget plans. Create envelopes to organize your savings by goals. Compare your actual budget vs ideal 50/30/20.',
    'meta.keywords': 'GoGoBudgeto, gogobudgeto free, free budget app, gogobudgeto app, budget gogobudgeto, monthly budget plans, savings envelopes, actual budget vs 50/30/20, generate budget plan, create savings envelopes, envelope movement history, free budgeting software, free budget application, free personal finance tool',
    
    // Header
    'header.login': 'Log in',
    'header.lang': 'EN',
    'header.title': '50/30/20 Budget and Envelope Method to Partition Your Savings',
    'header.subtitle': 'GoGoBudgeto generates your monthly budget plans using the 50/30/20 rule and uses envelopes to organize your savings by category. Free and no bank connection required.',
    'header.cta.primary': 'Start Free',
    'header.cta.secondary': 'Watch demo',
    
    // Features
    'features.title': 'Our features',
    'features.subtitle': 'Discover how GoGoBudgeto can transform your financial management',
    'features.smart.title': '50/30/20 Budget Plans',
    'features.smart.description': 'GoGoBudgeto automatically generates your budget plans using the 50/30/20 rule: 50% needs, 30% wants, 20% savings to optimize your monthly budget.',
    'features.planning.title': 'Envelope System',
    'features.planning.description': 'Partition your savings with the envelope method. Organize your savings by goals and categories for optimal control of your finances.',
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
    'cta.subtitle': 'Join thousands of users who have already transformed their budget management with free GoGoBudgeto.',
    'cta.primary': 'Start Free',
    'cta.disclaimer': 'By signing up, you agree to our Terms and Privacy Policy',
    
    // Footer
    'footer.tagline': 'Smart budgeting for your financial freedom.',
    'footer.copyright': '© 2025 GoGoBudgeto. All rights reserved.'
  }
} as const;

export type TranslationKey = keyof typeof translations.fr;
export type Locale = 'fr' | 'en';