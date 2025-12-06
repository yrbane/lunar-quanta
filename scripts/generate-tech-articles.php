<?php
/**
 * Script de génération d'articles sur les tendances tech 2025
 * Génère 100 articles (10 par catégorie) sur les top 10 trends tech
 */

declare(strict_types=1);

$basePath = dirname(__DIR__);

// Top 10 Tech Trends 2025 avec leurs catégories
$techTrends = [
    'ai-agents' => [
        'name' => 'AI Agents',
        'slug' => 'ai-agents',
        'description' => 'Intelligence artificielle autonome et agents IA',
        'color' => '#8b5cf6',
        'icon' => 'smart_toy',
        'articles' => [
            ['title' => 'Introduction aux AI Agents : L\'IA qui agit de manière autonome', 'tags' => ['ai-agents', 'ia', 'automatisation', 'llm']],
            ['title' => 'Comment construire votre premier agent IA avec LangChain', 'tags' => ['ai-agents', 'langchain', 'python', 'tutoriel']],
            ['title' => 'AI Agents vs Chatbots : Quelle est la différence ?', 'tags' => ['ai-agents', 'chatbot', 'comparaison', 'ia']],
            ['title' => 'Les meilleurs frameworks pour créer des agents IA en 2025', 'tags' => ['ai-agents', 'frameworks', 'autogen', 'crewai']],
            ['title' => 'Sécurité des AI Agents : Risques et bonnes pratiques', 'tags' => ['ai-agents', 'securite', 'gouvernance', 'risques']],
            ['title' => 'Multi-Agent Systems : Quand les IA collaborent', 'tags' => ['ai-agents', 'multi-agent', 'collaboration', 'swarm']],
            ['title' => 'AI Agents dans l\'entreprise : Cas d\'usage concrets', 'tags' => ['ai-agents', 'entreprise', 'productivite', 'automatisation']],
            ['title' => 'Orchestration d\'agents IA : Architecture et patterns', 'tags' => ['ai-agents', 'architecture', 'orchestration', 'design-patterns']],
            ['title' => 'L\'avenir des AI Agents : Tendances et prédictions', 'tags' => ['ai-agents', 'futur', 'tendances', 'predictions']],
            ['title' => 'Débugger et monitorer vos AI Agents en production', 'tags' => ['ai-agents', 'debugging', 'monitoring', 'observabilite']],
        ]
    ],
    'generative-ai' => [
        'name' => 'Generative AI',
        'slug' => 'generative-ai',
        'description' => 'IA générative, LLMs et modèles de fondation',
        'color' => '#ec4899',
        'icon' => 'auto_awesome',
        'articles' => [
            ['title' => 'GPT-5, Claude 4, Gemini 2 : Comparatif des LLMs 2025', 'tags' => ['generative-ai', 'llm', 'gpt', 'claude', 'gemini']],
            ['title' => 'Fine-tuning de LLMs : Guide complet pour débutants', 'tags' => ['generative-ai', 'fine-tuning', 'llm', 'tutoriel']],
            ['title' => 'RAG (Retrieval Augmented Generation) : Architecture et implémentation', 'tags' => ['generative-ai', 'rag', 'vector-db', 'embeddings']],
            ['title' => 'Prompt Engineering avancé : Techniques et stratégies', 'tags' => ['generative-ai', 'prompt-engineering', 'techniques', 'optimisation']],
            ['title' => 'Génération d\'images avec Midjourney, DALL-E et Stable Diffusion', 'tags' => ['generative-ai', 'images', 'midjourney', 'dalle', 'stable-diffusion']],
            ['title' => 'Coûts et optimisation des appels API aux LLMs', 'tags' => ['generative-ai', 'api', 'couts', 'optimisation', 'tokens']],
            ['title' => 'Open Source vs Propriétaire : Quel LLM choisir ?', 'tags' => ['generative-ai', 'open-source', 'llama', 'mistral', 'comparaison']],
            ['title' => 'Évaluation des LLMs : Métriques et benchmarks', 'tags' => ['generative-ai', 'evaluation', 'benchmarks', 'metriques']],
            ['title' => 'Multimodal AI : Quand les LLMs comprennent images et vidéos', 'tags' => ['generative-ai', 'multimodal', 'vision', 'video']],
            ['title' => 'Déployer un LLM en local : Ollama, LM Studio et alternatives', 'tags' => ['generative-ai', 'local', 'ollama', 'lm-studio', 'privacy']],
        ]
    ],
    'ai-coding' => [
        'name' => 'AI Coding',
        'slug' => 'ai-coding',
        'description' => 'Assistants de code IA et développement augmenté',
        'color' => '#06b6d4',
        'icon' => 'code',
        'articles' => [
            ['title' => 'GitHub Copilot vs Cursor vs Codeium : Quel assistant choisir ?', 'tags' => ['ai-coding', 'copilot', 'cursor', 'codeium', 'comparaison']],
            ['title' => 'Claude Code : Le terminal IA qui révolutionne le développement', 'tags' => ['ai-coding', 'claude-code', 'terminal', 'cli']],
            ['title' => 'Maximiser votre productivité avec les assistants de code IA', 'tags' => ['ai-coding', 'productivite', 'workflows', 'best-practices']],
            ['title' => 'Tests automatisés générés par IA : Mythe ou réalité ?', 'tags' => ['ai-coding', 'tests', 'automatisation', 'qualite']],
            ['title' => 'Code Review assistée par IA : Outils et intégrations', 'tags' => ['ai-coding', 'code-review', 'qualite', 'ci-cd']],
            ['title' => 'Refactoring intelligent avec l\'aide de l\'IA', 'tags' => ['ai-coding', 'refactoring', 'clean-code', 'maintenance']],
            ['title' => 'Documentation automatique : Générer des docs avec l\'IA', 'tags' => ['ai-coding', 'documentation', 'jsdoc', 'phpdoc']],
            ['title' => 'Sécurité du code : Détection de vulnérabilités par IA', 'tags' => ['ai-coding', 'securite', 'vulnerabilites', 'sast']],
            ['title' => 'L\'IA peut-elle remplacer les développeurs ?', 'tags' => ['ai-coding', 'futur', 'emploi', 'reflexion']],
            ['title' => 'Intégrer l\'IA dans votre IDE : Extensions et configurations', 'tags' => ['ai-coding', 'ide', 'vscode', 'jetbrains', 'extensions']],
        ]
    ],
    'cybersecurity' => [
        'name' => 'Cybersecurity',
        'slug' => 'cybersecurity',
        'description' => 'Sécurité informatique et DevSecOps',
        'color' => '#ef4444',
        'icon' => 'security',
        'articles' => [
            ['title' => 'Zero Trust Architecture : Principes et implémentation', 'tags' => ['cybersecurity', 'zero-trust', 'architecture', 'securite']],
            ['title' => 'DevSecOps : Intégrer la sécurité dans votre pipeline CI/CD', 'tags' => ['cybersecurity', 'devsecops', 'ci-cd', 'automatisation']],
            ['title' => 'OWASP Top 10 2025 : Les nouvelles menaces web', 'tags' => ['cybersecurity', 'owasp', 'vulnerabilites', 'web']],
            ['title' => 'Sécuriser vos APIs : Authentication, Authorization, Rate Limiting', 'tags' => ['cybersecurity', 'api', 'oauth', 'jwt', 'securite']],
            ['title' => 'Ransomware : Prévention et réponse aux incidents', 'tags' => ['cybersecurity', 'ransomware', 'incident-response', 'backup']],
            ['title' => 'Pentest automatisé : Outils et méthodologies', 'tags' => ['cybersecurity', 'pentest', 'outils', 'automatisation']],
            ['title' => 'Sécurité des conteneurs Docker et Kubernetes', 'tags' => ['cybersecurity', 'docker', 'kubernetes', 'containers']],
            ['title' => 'Gestion des secrets : Vault, SOPS et alternatives', 'tags' => ['cybersecurity', 'secrets', 'vault', 'sops', 'encryption']],
            ['title' => 'SOC moderne : SIEM, SOAR et détection des menaces', 'tags' => ['cybersecurity', 'soc', 'siem', 'soar', 'detection']],
            ['title' => 'Compliance et réglementations : GDPR, NIS2, DORA', 'tags' => ['cybersecurity', 'compliance', 'gdpr', 'nis2', 'reglementation']],
        ]
    ],
    'cloud-native' => [
        'name' => 'Cloud Native',
        'slug' => 'cloud-native',
        'description' => 'Kubernetes, containers et architecture cloud',
        'color' => '#3b82f6',
        'icon' => 'cloud',
        'articles' => [
            ['title' => 'Kubernetes en 2025 : Nouveautés et évolutions', 'tags' => ['cloud-native', 'kubernetes', 'k8s', 'containers']],
            ['title' => 'Serverless : AWS Lambda, Google Cloud Functions, Azure Functions', 'tags' => ['cloud-native', 'serverless', 'lambda', 'functions']],
            ['title' => 'GitOps avec ArgoCD et Flux : Guide pratique', 'tags' => ['cloud-native', 'gitops', 'argocd', 'flux', 'deployment']],
            ['title' => 'Service Mesh : Istio vs Linkerd vs Cilium', 'tags' => ['cloud-native', 'service-mesh', 'istio', 'linkerd', 'cilium']],
            ['title' => 'FinOps : Optimiser vos coûts cloud', 'tags' => ['cloud-native', 'finops', 'couts', 'optimisation', 'aws']],
            ['title' => 'Multi-cloud et Hybrid Cloud : Stratégies et outils', 'tags' => ['cloud-native', 'multi-cloud', 'hybrid', 'terraform']],
            ['title' => 'Observabilité : Prometheus, Grafana et OpenTelemetry', 'tags' => ['cloud-native', 'observabilite', 'prometheus', 'grafana', 'otel']],
            ['title' => 'Platform Engineering : Construire une IDP', 'tags' => ['cloud-native', 'platform-engineering', 'idp', 'backstage']],
            ['title' => 'Containers sans Docker : Podman, containerd et alternatives', 'tags' => ['cloud-native', 'containers', 'podman', 'containerd']],
            ['title' => 'Disaster Recovery dans le cloud : Stratégies et implémentation', 'tags' => ['cloud-native', 'disaster-recovery', 'backup', 'resilience']],
        ]
    ],
    'low-code' => [
        'name' => 'Low-Code/No-Code',
        'slug' => 'low-code',
        'description' => 'Développement visuel et citizen development',
        'color' => '#f59e0b',
        'icon' => 'widgets',
        'articles' => [
            ['title' => 'Low-Code en 2025 : État des lieux et perspectives', 'tags' => ['low-code', 'no-code', 'tendances', 'marche']],
            ['title' => 'Retool, Appsmith, Tooljet : Construire des apps internes', 'tags' => ['low-code', 'retool', 'appsmith', 'tooljet', 'internal-tools']],
            ['title' => 'Automatisation avec n8n, Make et Zapier', 'tags' => ['low-code', 'automatisation', 'n8n', 'make', 'zapier']],
            ['title' => 'Bubble, Webflow, Framer : Créer des sites sans coder', 'tags' => ['low-code', 'bubble', 'webflow', 'framer', 'websites']],
            ['title' => 'Low-Code et IA : La combinaison gagnante', 'tags' => ['low-code', 'ia', 'integration', 'automatisation']],
            ['title' => 'Quand choisir le Low-Code vs le développement traditionnel', 'tags' => ['low-code', 'choix', 'comparaison', 'decision']],
            ['title' => 'Sécurité et gouvernance des plateformes Low-Code', 'tags' => ['low-code', 'securite', 'gouvernance', 'compliance']],
            ['title' => 'Citizen Development : Former vos équipes métier', 'tags' => ['low-code', 'citizen-development', 'formation', 'adoption']],
            ['title' => 'APIs et intégrations dans les plateformes Low-Code', 'tags' => ['low-code', 'api', 'integrations', 'connecteurs']],
            ['title' => 'Migrer du Low-Code vers du code : Stratégies et outils', 'tags' => ['low-code', 'migration', 'code', 'export']],
        ]
    ],
    'edge-computing' => [
        'name' => 'Edge Computing',
        'slug' => 'edge-computing',
        'description' => 'IoT, Edge AI et calcul distribué',
        'color' => '#10b981',
        'icon' => 'memory',
        'articles' => [
            ['title' => 'Edge Computing : Concepts et architectures', 'tags' => ['edge-computing', 'architecture', 'iot', 'latence']],
            ['title' => 'Edge AI : Déployer des modèles sur des appareils embarqués', 'tags' => ['edge-computing', 'edge-ai', 'tinyml', 'embedded']],
            ['title' => 'Cloudflare Workers, Vercel Edge, Deno Deploy : Comparatif', 'tags' => ['edge-computing', 'workers', 'vercel', 'deno', 'serverless']],
            ['title' => 'WebAssembly at the Edge : Performances et cas d\'usage', 'tags' => ['edge-computing', 'wasm', 'webassembly', 'performances']],
            ['title' => 'IoT et Edge : Architectures de collecte de données', 'tags' => ['edge-computing', 'iot', 'mqtt', 'data-collection']],
            ['title' => 'Sécurité de l\'Edge : Défis et solutions', 'tags' => ['edge-computing', 'securite', 'iot-security', 'encryption']],
            ['title' => 'Real-time Processing à l\'Edge avec Apache Kafka', 'tags' => ['edge-computing', 'kafka', 'streaming', 'real-time']],
            ['title' => 'CDN nouvelle génération : Edge Functions et personnalisation', 'tags' => ['edge-computing', 'cdn', 'edge-functions', 'caching']],
            ['title' => '5G et Edge Computing : Nouvelles opportunités', 'tags' => ['edge-computing', '5g', 'telecom', 'latence']],
            ['title' => 'Kubernetes at the Edge : K3s, MicroK8s et KubeEdge', 'tags' => ['edge-computing', 'kubernetes', 'k3s', 'kubeedge']],
        ]
    ],
    'python-data' => [
        'name' => 'Python & Data',
        'slug' => 'python-data',
        'description' => 'Python, Data Science et Machine Learning',
        'color' => '#fbbf24',
        'icon' => 'analytics',
        'articles' => [
            ['title' => 'Python 3.13 : Nouveautés et fonctionnalités', 'tags' => ['python-data', 'python', 'nouveautes', 'langage']],
            ['title' => 'Polars vs Pandas : Le nouveau standard pour la data ?', 'tags' => ['python-data', 'polars', 'pandas', 'dataframe', 'performances']],
            ['title' => 'MLOps : De l\'expérimentation à la production', 'tags' => ['python-data', 'mlops', 'mlflow', 'production', 'deployment']],
            ['title' => 'Data Engineering avec dbt, Airflow et Dagster', 'tags' => ['python-data', 'data-engineering', 'dbt', 'airflow', 'dagster']],
            ['title' => 'Feature Stores : Feast, Tecton et alternatives', 'tags' => ['python-data', 'feature-store', 'feast', 'ml', 'features']],
            ['title' => 'Visualisation de données : Plotly, Bokeh et Altair', 'tags' => ['python-data', 'visualisation', 'plotly', 'bokeh', 'charts']],
            ['title' => 'Jupyter dans le cloud : Notebooks collaboratifs', 'tags' => ['python-data', 'jupyter', 'notebooks', 'collaboration', 'cloud']],
            ['title' => 'Time Series Analysis avec Python', 'tags' => ['python-data', 'time-series', 'forecasting', 'analyse']],
            ['title' => 'Python asynchrone : asyncio et concurrence', 'tags' => ['python-data', 'asyncio', 'concurrence', 'performances']],
            ['title' => 'Testing en Data Science : pytest et hypothesis', 'tags' => ['python-data', 'testing', 'pytest', 'hypothesis', 'qualite']],
        ]
    ],
    'blockchain' => [
        'name' => 'Blockchain & Web3',
        'slug' => 'blockchain',
        'description' => 'Blockchain, crypto et applications décentralisées',
        'color' => '#a855f7',
        'icon' => 'token',
        'articles' => [
            ['title' => 'Blockchain en entreprise : Cas d\'usage réels en 2025', 'tags' => ['blockchain', 'entreprise', 'use-cases', 'adoption']],
            ['title' => 'Smart Contracts : Solidity et les alternatives', 'tags' => ['blockchain', 'smart-contracts', 'solidity', 'ethereum']],
            ['title' => 'DeFi : Protocoles et innovations', 'tags' => ['blockchain', 'defi', 'finance', 'protocoles']],
            ['title' => 'NFTs au-delà de l\'art : Applications pratiques', 'tags' => ['blockchain', 'nft', 'applications', 'tokenisation']],
            ['title' => 'Layer 2 : Scaling Ethereum avec Arbitrum et Optimism', 'tags' => ['blockchain', 'layer2', 'arbitrum', 'optimism', 'scaling']],
            ['title' => 'Audit de Smart Contracts : Outils et méthodologies', 'tags' => ['blockchain', 'audit', 'securite', 'smart-contracts']],
            ['title' => 'DAOs : Gouvernance décentralisée', 'tags' => ['blockchain', 'dao', 'gouvernance', 'decentralisation']],
            ['title' => 'Identité décentralisée (DID) et Verifiable Credentials', 'tags' => ['blockchain', 'did', 'identite', 'credentials']],
            ['title' => 'Interopérabilité blockchain : Bridges et protocoles cross-chain', 'tags' => ['blockchain', 'interoperabilite', 'bridges', 'cross-chain']],
            ['title' => 'Régulation crypto : MICA et impacts sur le développement', 'tags' => ['blockchain', 'regulation', 'mica', 'compliance']],
        ]
    ],
    'spatial-computing' => [
        'name' => 'Spatial Computing',
        'slug' => 'spatial-computing',
        'description' => 'AR/VR, métavers et réalité étendue',
        'color' => '#f472b6',
        'icon' => 'view_in_ar',
        'articles' => [
            ['title' => 'Apple Vision Pro : Développer pour visionOS', 'tags' => ['spatial-computing', 'vision-pro', 'visionos', 'apple']],
            ['title' => 'WebXR : Créer des expériences AR/VR dans le navigateur', 'tags' => ['spatial-computing', 'webxr', 'ar', 'vr', 'web']],
            ['title' => 'Unity vs Unreal pour le développement XR', 'tags' => ['spatial-computing', 'unity', 'unreal', 'xr', 'comparaison']],
            ['title' => 'AR Cloud : Persistance et partage d\'expériences', 'tags' => ['spatial-computing', 'ar-cloud', 'persistance', 'collaboration']],
            ['title' => 'Métavers d\'entreprise : Collaboration et formation', 'tags' => ['spatial-computing', 'metavers', 'entreprise', 'collaboration']],
            ['title' => 'Hand Tracking et Eye Tracking : Interactions naturelles', 'tags' => ['spatial-computing', 'hand-tracking', 'eye-tracking', 'ux']],
            ['title' => 'Spatial Audio : Immersion sonore en XR', 'tags' => ['spatial-computing', 'spatial-audio', 'audio', 'immersion']],
            ['title' => 'Digital Twins : Modélisation 3D et IoT', 'tags' => ['spatial-computing', 'digital-twin', '3d', 'iot']],
            ['title' => 'Performance et optimisation en VR/AR', 'tags' => ['spatial-computing', 'performance', 'optimisation', 'fps']],
            ['title' => 'Accessibilité en réalité virtuelle et augmentée', 'tags' => ['spatial-computing', 'accessibilite', 'inclusive-design', 'a11y']],
        ]
    ],
];

// Contenu des articles (templates)
$articleTemplates = [
    'intro' => "Découvrez tout ce que vous devez savoir sur ce sujet passionnant dans le domaine de la technologie en 2025.",
    'why' => "## Pourquoi c'est important ?\n\nCette technologie transforme la façon dont les entreprises et les développeurs abordent leurs projets. Voici les raisons principales :",
    'howto' => "## Comment commencer ?\n\n1. **Comprendre les fondamentaux** - Avant de plonger dans l'implémentation\n2. **Choisir les bons outils** - Sélectionner la stack adaptée à vos besoins\n3. **Pratiquer** - Rien ne remplace l'expérience pratique\n4. **Itérer** - Améliorer continuellement votre approche",
    'conclusion' => "## Conclusion\n\nCette technologie continue d'évoluer rapidement. Restez informé des dernières tendances et n'hésitez pas à expérimenter pour rester compétitif dans ce domaine en constante évolution.",
];

function generateSlug(string $title): string {
    $slug = strtolower($title);
    $slug = preg_replace('/[àáâãäå]/u', 'a', $slug);
    $slug = preg_replace('/[èéêë]/u', 'e', $slug);
    $slug = preg_replace('/[ìíîï]/u', 'i', $slug);
    $slug = preg_replace('/[òóôõö]/u', 'o', $slug);
    $slug = preg_replace('/[ùúûü]/u', 'u', $slug);
    $slug = preg_replace('/[ç]/u', 'c', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function generateContent(string $title, array $tags): string {
    global $articleTemplates;

    $tagList = implode(', ', array_map(fn($t) => "**{$t}**", $tags));

    $content = "# {$title}\n\n";
    $content .= "{$articleTemplates['intro']}\n\n";
    $content .= "**Tags** : {$tagList}\n\n";
    $content .= "{$articleTemplates['why']}\n\n";
    $content .= "- Amélioration de la productivité des équipes\n";
    $content .= "- Réduction des coûts opérationnels\n";
    $content .= "- Innovation et avantage concurrentiel\n";
    $content .= "- Meilleure expérience utilisateur\n\n";
    $content .= "{$articleTemplates['howto']}\n\n";
    $content .= "```bash\n# Exemple de commande pour démarrer\nnpm install @example/package\n# ou\npip install example-package\n```\n\n";
    $content .= "## Ressources complémentaires\n\n";
    $content .= "- Documentation officielle\n";
    $content .= "- Tutoriels et guides\n";
    $content .= "- Communauté et forums\n\n";
    $content .= "{$articleTemplates['conclusion']}\n";

    return $content;
}

function generateExcerpt(string $title): string {
    return "Découvrez les concepts clés, meilleures pratiques et tendances actuelles : {$title}";
}

// Images par catégorie (Unsplash)
$categoryImages = [
    'ai-agents' => [
        'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1531746790731-6c087fecd65a?w=1200&h=630&fit=crop',
    ],
    'generative-ai' => [
        'https://images.unsplash.com/photo-1679083216051-aa510a1a2c0e?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1676299081847-5c7d8fba1182?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1675271591211-930cfdcb0d2d?w=1200&h=630&fit=crop',
    ],
    'ai-coding' => [
        'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=1200&h=630&fit=crop',
    ],
    'cybersecurity' => [
        'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1510511459019-5dda7724fd87?w=1200&h=630&fit=crop',
    ],
    'cloud-native' => [
        'https://images.unsplash.com/photo-1667372393119-3d4c48d07fc9?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=1200&h=630&fit=crop',
    ],
    'low-code' => [
        'https://images.unsplash.com/photo-1559028012-481c04fa702d?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=630&fit=crop',
    ],
    'edge-computing' => [
        'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1200&h=630&fit=crop',
    ],
    'python-data' => [
        'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
    ],
    'blockchain' => [
        'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1622630998477-20aa696ecb05?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1644143379190-08a5f055de1d?w=1200&h=630&fit=crop',
    ],
    'spatial-computing' => [
        'https://images.unsplash.com/photo-1617802690992-15d93263d3a9?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1592478411213-6153e4ebc07d?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1626379953822-baec19c3accd?w=1200&h=630&fit=crop',
    ],
];

// Créer les catégories
echo "=== Création des catégories ===\n";
$categoriesDir = $basePath . '/data/blog/categories';
$sortOrder = 10;

foreach ($techTrends as $categoryId => $category) {
    $categoryData = [
        'id' => $categoryId,
        'name' => $category['name'],
        'slug' => $category['slug'],
        'description' => $category['description'],
        'color' => $category['color'],
        'sortOrder' => $sortOrder,
        'createdAt' => date('c'),
        'updatedAt' => date('c'),
    ];

    $filePath = $categoriesDir . '/' . $categoryId . '.json';
    file_put_contents($filePath, json_encode($categoryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "✓ Catégorie créée : {$category['name']}\n";
    $sortOrder += 10;
}

// Créer les articles
echo "\n=== Création des articles ===\n";
$postsDir = $basePath . '/data/blog/posts';
$articleCount = 0;
$baseDate = new DateTime('2025-12-01');

foreach ($techTrends as $categoryId => $category) {
    echo "\n--- {$category['name']} ---\n";
    $images = $categoryImages[$categoryId] ?? $categoryImages['ai-agents'];

    foreach ($category['articles'] as $index => $article) {
        $postId = $categoryId . '-' . sprintf('%02d', $index + 1);
        $slug = generateSlug($article['title']);

        // Date de publication échelonnée
        $publishDate = clone $baseDate;
        $publishDate->add(new DateInterval('P' . $articleCount . 'D'));
        $publishDate->add(new DateInterval('PT' . rand(0, 12) . 'H'));

        $postData = [
            'id' => $postId,
            'title' => $article['title'],
            'slug' => $slug,
            'content' => generateContent($article['title'], $article['tags']),
            'excerpt' => generateExcerpt($article['title']),
            'author' => 'Lunar Tech',
            'categoryId' => $categoryId,
            'featuredImage' => $images[$index % count($images)],
            'status' => 'published',
            'tags' => $article['tags'],
            'createdAt' => $publishDate->format('c'),
            'updatedAt' => $publishDate->format('c'),
            'publishedAt' => $publishDate->format('c'),
        ];

        $filePath = $postsDir . '/' . $postId . '.json';
        file_put_contents($filePath, json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "  ✓ {$article['title']}\n";
        $articleCount++;
    }
}

echo "\n=== Résumé ===\n";
echo "Catégories créées : " . count($techTrends) . "\n";
echo "Articles créés : {$articleCount}\n";
echo "\nPour régénérer le blog statique, exécutez :\n";
echo "  ./bin/console blog:regenerate\n";
