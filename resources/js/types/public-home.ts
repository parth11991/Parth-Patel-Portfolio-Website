export type ThemeChoice = 'light' | 'dark' | 'system';

export type PublicSettings = {
    siteName: string;
    defaultTheme: ThemeChoice;
    accentColor: string;
    metaTitle: string;
    metaDescription: string;
    canonicalUrl: string;
    ogImageUrl: string | null;
};

export type PublicProfile = {
    fullName: string;
    professionalTitle: string;
    heroIntro: string;
    aboutText: string;
    location: string;
    publicEmail: string;
    publicPhone: string | null;
    availabilityText: string | null;
    profileImageUrl: string | null;
    cvUrl: string | null;
    yearsExperience: number | null;
    heroCodeLines: string[];
    aboutQuestion: string | null;
    aboutAnswer: string | null;
};

export type SocialLink = {
    label: string;
    url: string;
    iconKey: string;
};

export type Skill = {
    name: string;
    category: string;
    iconKey: string | null;
    proficiency: number | null;
};

export type Experience = {
    id: number;
    companyName: string;
    roleTitle: string;
    employmentType: string | null;
    location: string | null;
    summary: string;
    startedAt: string | null;
    endedAt: string | null;
    isCurrent: boolean;
    companyUrl: string | null;
};

export type Education = {
    institution: string;
    qualification: string;
    fieldOfStudy: string | null;
    startedAt: string | null;
    endedAt: string | null;
    description: string | null;
};

export type PortfolioMedia = {
    url: string | null;
    type: 'logo' | 'screenshot';
    fit: 'contain' | 'cover';
    altText: string | null;
    isPrimary: boolean;
};

export type Portfolio = {
    title: string;
    slug: string;
    shortDescription: string;
    projectUrl: string | null;
    repositoryUrl: string | null;
    startedAt: string | null;
    completedAt: string | null;
    isOngoing: boolean;
    technologies: string[];
    role: string | null;
    outcome: string | null;
    publishedAt: string | null;
    experience: { companyName: string } | null;
    media: PortfolioMedia[];
};

export type MorePortfolio = {
    title: string;
    projectUrl: string | null;
    technologies: string[];
};
