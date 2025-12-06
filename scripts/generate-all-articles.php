<?php
/**
 * Script de génération d'articles sur les tendances 2025
 * - ~100 articles Biologie/Biotechnologie
 * - ~100 articles IA additionnels
 * - ~100 articles Informatique Quantique
 * + Mise à jour des images pour tous les articles existants
 */

declare(strict_types=1);

$basePath = dirname(__DIR__);

// Collection d'images Unsplash uniques par thème
$imageCollections = [
    // Images IA / Tech
    'ai' => [
        'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1531746790731-6c087fecd65a?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1679083216051-aa510a1a2c0e?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1676299081847-5c7d8fba1182?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1675271591211-930cfdcb0d2d?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1507146153580-69a1fe6d8aa1?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1515378960530-7c0da6231fb1?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1535378917042-10a22c95931a?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1655720828018-edd2daec9349?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1696446700704-ec4f81f42a21?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1677756119517-756a188d2d94?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1684369175833-4b445ad6bfb5?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1686191128892-3b37add62d79?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1694903089438-bf28d4697a1a?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1712002641088-9d76f9080889?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1718027808460-7069cf0ca9ae?w=1200&h=630&fit=crop',
    ],
    // Images Biotechnologie / Science
    'biology' => [
        'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1576086213369-97a306d36557?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1530026405186-ed1f139313f8?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1581093458791-9d42e3c7e117?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1614935151651-0bea6508db6b?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1582719471384-894fbb16e074?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1579154204601-01588f351e67?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1518152006812-edab29b069ac?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1578496479914-7ef3b0193be3?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1628595351029-c2bf17511435?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1584553421349-3557471bed79?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1559757175-5700dde675bc?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1607619056574-7b8d3ee536b2?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1603126857599-f6e157fa2fe6?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1612531386530-97286d97c2d2?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1560807707-8cc77767d783?w=1200&h=630&fit=crop',
    ],
    // Images Quantum Computing
    'quantum' => [
        'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1509228627152-72ae9ae6848d?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1563089145-599997674d42?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1610563166150-b34df4f3bcd6?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1559028012-481c04fa702d?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1639322537228-f710d846310a?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1614064641938-3bbee52942c7?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1597733336794-12d05021d510?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1516110833967-0b5716ca1387?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1488229297570-58520851e868?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1606159068539-43f36b99d1b2?w=1200&h=630&fit=crop',
    ],
    // Images Tech générales
    'tech' => [
        'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1484417894907-623942c8ee29?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&h=630&fit=crop',
    ],
    // Images cybersécurité
    'security' => [
        'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1510511459019-5dda7724fd87?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1614064641938-3bbee52942c7?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1555949963-ff9fe0c870eb?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200&h=630&fit=crop',
    ],
    // Images Cloud
    'cloud' => [
        'https://images.unsplash.com/photo-1667372393119-3d4c48d07fc9?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1603695762547-fba8b88ac8ad?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1560732488-6b0df240254a?w=1200&h=630&fit=crop',
    ],
    // Images Blockchain
    'blockchain' => [
        'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1622630998477-20aa696ecb05?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1644143379190-08a5f055de1d?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1621761191319-c6fb62004040?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1643488564985-cbf59e7824e2?w=1200&h=630&fit=crop',
    ],
    // Images VR/AR
    'spatial' => [
        'https://images.unsplash.com/photo-1617802690992-15d93263d3a9?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1592478411213-6153e4ebc07d?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1626379953822-baec19c3accd?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1593508512255-86ab42a8e620?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?w=1200&h=630&fit=crop',
    ],
];

// Compteur global pour images uniques
$imageIndex = [];

function getUniqueImage(string $category): string {
    global $imageCollections, $imageIndex;

    if (!isset($imageIndex[$category])) {
        $imageIndex[$category] = 0;
    }

    $images = $imageCollections[$category] ?? $imageCollections['tech'];
    $image = $images[$imageIndex[$category] % count($images)];
    $imageIndex[$category]++;

    // Ajouter un paramètre unique pour éviter le cache
    return $image . '&sig=' . uniqid();
}

function generateSlug(string $title): string {
    $slug = strtolower($title);
    $slug = preg_replace('/[àáâãäå]/u', 'a', $slug);
    $slug = preg_replace('/[èéêë]/u', 'e', $slug);
    $slug = preg_replace('/[ìíîï]/u', 'i', $slug);
    $slug = preg_replace('/[òóôõö]/u', 'o', $slug);
    $slug = preg_replace('/[ùúûü]/u', 'u', $slug);
    $slug = preg_replace('/[ç]/u', 'c', $slug);
    $slug = preg_replace('/[ñ]/u', 'n', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function generateContent(string $title, array $tags, string $intro = ''): string {
    $tagList = implode(', ', array_map(fn($t) => "**{$t}**", array_slice($tags, 0, 5)));

    $content = "# {$title}\n\n";
    $content .= $intro ?: "Découvrez les dernières avancées et innovations dans ce domaine passionnant.\n\n";
    $content .= "**Mots-clés** : {$tagList}\n\n";
    $content .= "## Contexte et enjeux\n\n";
    $content .= "Ce sujet représente une avancée majeure dans son domaine. Les implications sont nombreuses et touchent de multiples secteurs d'activité.\n\n";
    $content .= "## Points clés\n\n";
    $content .= "- Innovation technologique de pointe\n";
    $content .= "- Applications industrielles et commerciales\n";
    $content .= "- Impact sur la recherche et développement\n";
    $content .= "- Perspectives d'évolution à court et moyen terme\n\n";
    $content .= "## Conclusion\n\n";
    $content .= "Les développements récents montrent que ce domaine continue d'évoluer rapidement, ouvrant de nouvelles possibilités pour l'avenir.\n";

    return $content;
}

// ============================================================================
// BIOLOGIE / BIOTECHNOLOGIE - 100 articles
// ============================================================================

$biologyTrends = [
    'crispr' => [
        'name' => 'CRISPR & Gene Editing',
        'slug' => 'crispr-gene-editing',
        'description' => 'Édition génétique CRISPR-Cas9 et biotechnologies',
        'color' => '#22c55e',
        'articles' => [
            ['title' => 'CRISPR-Cas9 : La révolution de l\'édition génétique', 'tags' => ['crispr', 'gene-editing', 'biotechnologie', 'genetique']],
            ['title' => 'Prime Editing : L\'évolution de CRISPR sans coupure d\'ADN', 'tags' => ['crispr', 'prime-editing', 'adn', 'precision']],
            ['title' => 'CRISPR-GPT : L\'IA au service de l\'édition génétique', 'tags' => ['crispr', 'ia', 'crispr-gpt', 'automatisation']],
            ['title' => 'Thérapies géniques CRISPR : De Casgevy aux futures applications', 'tags' => ['crispr', 'therapie-genique', 'casgevy', 'sickle-cell']],
            ['title' => 'CRISPR et cancer : Nouvelles approches thérapeutiques', 'tags' => ['crispr', 'cancer', 'immunotherapie', 'oncologie']],
            ['title' => 'Édition de base : ABE et CBE pour des modifications précises', 'tags' => ['crispr', 'base-editing', 'abe', 'cbe']],
            ['title' => 'CRISPR in vivo : Défis et avancées de la livraison', 'tags' => ['crispr', 'in-vivo', 'delivery', 'nanoparticules']],
            ['title' => 'Off-target effects : Minimiser les risques de CRISPR', 'tags' => ['crispr', 'off-target', 'securite', 'precision']],
            ['title' => 'CRISPR et VIH : Vers une cure fonctionnelle', 'tags' => ['crispr', 'vih', 'hiv', 'cure']],
            ['title' => 'Régulation de CRISPR : Éthique et cadre légal', 'tags' => ['crispr', 'ethique', 'regulation', 'bioethique']],
        ]
    ],
    'synthetic-biology' => [
        'name' => 'Synthetic Biology',
        'slug' => 'synthetic-biology',
        'description' => 'Biologie synthétique et ingénierie du vivant',
        'color' => '#06b6d4',
        'articles' => [
            ['title' => 'Biologie synthétique : Programmer le vivant', 'tags' => ['synthetic-biology', 'biologie', 'programmation', 'dna']],
            ['title' => 'Cellules synthétiques : Créer la vie en laboratoire', 'tags' => ['synthetic-biology', 'cellules', 'minimal-genome', 'syn3']],
            ['title' => 'Biofabrication : Produire des matériaux avec des microbes', 'tags' => ['synthetic-biology', 'biofabrication', 'materiaux', 'microbes']],
            ['title' => 'Circuits génétiques : La logique dans les cellules', 'tags' => ['synthetic-biology', 'circuits', 'logique', 'toggle-switch']],
            ['title' => 'XNA : Au-delà de l\'ADN naturel', 'tags' => ['synthetic-biology', 'xna', 'nucleotides', 'innovation']],
            ['title' => 'Metabolic engineering : Optimiser les voies métaboliques', 'tags' => ['synthetic-biology', 'metabolic', 'engineering', 'pathways']],
            ['title' => 'Cell-free systems : Synthèse sans cellules vivantes', 'tags' => ['synthetic-biology', 'cell-free', 'in-vitro', 'prototypage']],
            ['title' => 'Biocomputing : Ordinateurs biologiques', 'tags' => ['synthetic-biology', 'biocomputing', 'calcul', 'dna-computing']],
            ['title' => 'Standardisation en biologie synthétique : BioBricks', 'tags' => ['synthetic-biology', 'biobricks', 'standardisation', 'igem']],
            ['title' => 'Biosécurité en biologie synthétique', 'tags' => ['synthetic-biology', 'biosecurite', 'dual-use', 'regulation']],
        ]
    ],
    'genomics' => [
        'name' => 'Genomics & Sequencing',
        'slug' => 'genomics-sequencing',
        'description' => 'Génomique et séquençage nouvelle génération',
        'color' => '#8b5cf6',
        'articles' => [
            ['title' => 'Séquençage nouvelle génération : Technologies et applications', 'tags' => ['genomics', 'ngs', 'sequencing', 'illumina']],
            ['title' => 'Nanopore sequencing : Séquençage en temps réel', 'tags' => ['genomics', 'nanopore', 'oxford-nanopore', 'long-reads']],
            ['title' => 'Single-cell sequencing : L\'analyse cellule par cellule', 'tags' => ['genomics', 'single-cell', 'scrna-seq', 'heterogeneite']],
            ['title' => 'Spatial transcriptomics : Cartographier l\'expression génique', 'tags' => ['genomics', 'spatial', 'transcriptomics', '10x-visium']],
            ['title' => 'Epigenomics : Au-delà de la séquence ADN', 'tags' => ['genomics', 'epigenomics', 'methylation', 'histone']],
            ['title' => 'Metagenomics : Explorer les communautés microbiennes', 'tags' => ['genomics', 'metagenomics', 'microbiome', '16s']],
            ['title' => 'Long-read sequencing : PacBio vs Nanopore', 'tags' => ['genomics', 'long-read', 'pacbio', 'hifi']],
            ['title' => 'Genome assembly : Reconstruire des génomes complets', 'tags' => ['genomics', 'assembly', 'bioinformatics', 't2t']],
            ['title' => 'Pharmacogenomics : Médecine personnalisée par le génome', 'tags' => ['genomics', 'pharmacogenomics', 'precision-medicine', 'pgx']],
            ['title' => 'Ancient DNA : Séquencer le passé', 'tags' => ['genomics', 'ancient-dna', 'paleogenomics', 'evolution']],
        ]
    ],
    'cell-therapy' => [
        'name' => 'Cell & Gene Therapy',
        'slug' => 'cell-gene-therapy',
        'description' => 'Thérapies cellulaires et géniques',
        'color' => '#ec4899',
        'articles' => [
            ['title' => 'CAR-T cells : Reprogrammer le système immunitaire', 'tags' => ['cell-therapy', 'car-t', 'immunotherapy', 'cancer']],
            ['title' => 'iPSC : Cellules souches pluripotentes induites', 'tags' => ['cell-therapy', 'ipsc', 'stem-cells', 'regeneration']],
            ['title' => 'Thérapie génique AAV : Vecteurs viraux adéno-associés', 'tags' => ['cell-therapy', 'aav', 'gene-therapy', 'vectors']],
            ['title' => 'Allogeneic CAR-T : Thérapies off-the-shelf', 'tags' => ['cell-therapy', 'allogeneic', 'car-t', 'universal']],
            ['title' => 'NK cells thérapeutiques : Alternative aux CAR-T', 'tags' => ['cell-therapy', 'nk-cells', 'immunotherapy', 'innate']],
            ['title' => 'Exosomes thérapeutiques : Vésicules extracellulaires', 'tags' => ['cell-therapy', 'exosomes', 'vesicles', 'delivery']],
            ['title' => 'Organoïdes : Mini-organes pour la recherche', 'tags' => ['cell-therapy', 'organoids', '3d-culture', 'disease-modeling']],
            ['title' => 'Manufacturing GMP : Production de thérapies cellulaires', 'tags' => ['cell-therapy', 'gmp', 'manufacturing', 'scale-up']],
            ['title' => 'TCR therapy : Récepteurs T modifiés', 'tags' => ['cell-therapy', 'tcr', 't-cells', 'antigens']],
            ['title' => 'In vivo gene therapy : Thérapies sans ex vivo', 'tags' => ['cell-therapy', 'in-vivo', 'liver', 'muscle']],
        ]
    ],
    'drug-discovery' => [
        'name' => 'Drug Discovery',
        'slug' => 'drug-discovery',
        'description' => 'Découverte de médicaments et développement pharmaceutique',
        'color' => '#f59e0b',
        'articles' => [
            ['title' => 'AI Drug Discovery : L\'IA révolutionne la pharma', 'tags' => ['drug-discovery', 'ai', 'machine-learning', 'pharma']],
            ['title' => 'AlphaFold : Prédiction des structures protéiques', 'tags' => ['drug-discovery', 'alphafold', 'protein', 'structure']],
            ['title' => 'High-throughput screening : Cribler des millions de composés', 'tags' => ['drug-discovery', 'hts', 'screening', 'automation']],
            ['title' => 'PROTAC : Dégradation ciblée des protéines', 'tags' => ['drug-discovery', 'protac', 'degradation', 'ubiquitin']],
            ['title' => 'Antibody drug conjugates : ADC nouvelle génération', 'tags' => ['drug-discovery', 'adc', 'antibodies', 'payloads']],
            ['title' => 'mRNA therapeutics : Au-delà des vaccins', 'tags' => ['drug-discovery', 'mrna', 'therapeutics', 'moderna']],
            ['title' => 'Fragment-based drug design', 'tags' => ['drug-discovery', 'fbdd', 'fragments', 'x-ray']],
            ['title' => 'Organ-on-chip : Modèles précliniques avancés', 'tags' => ['drug-discovery', 'organ-chip', 'microfluidics', 'toxicology']],
            ['title' => 'CRISPR screening : Identifier de nouvelles cibles', 'tags' => ['drug-discovery', 'crispr-screen', 'targets', 'functional']],
            ['title' => 'Phenotypic screening : Retour au drug discovery classique', 'tags' => ['drug-discovery', 'phenotypic', 'cell-based', 'mechanism']],
        ]
    ],
    'bioinformatics' => [
        'name' => 'Bioinformatics',
        'slug' => 'bioinformatics',
        'description' => 'Bioinformatique et analyse de données biologiques',
        'color' => '#0ea5e9',
        'articles' => [
            ['title' => 'Bioinformatique : Outils essentiels pour biologistes', 'tags' => ['bioinformatics', 'tools', 'analysis', 'software']],
            ['title' => 'Machine Learning en biologie : Applications pratiques', 'tags' => ['bioinformatics', 'ml', 'prediction', 'classification']],
            ['title' => 'Protein Language Models : pLMs pour la biologie', 'tags' => ['bioinformatics', 'plm', 'esm', 'embeddings']],
            ['title' => 'RNA-seq analysis : Pipeline et bonnes pratiques', 'tags' => ['bioinformatics', 'rna-seq', 'deseq2', 'differential']],
            ['title' => 'Molecular dynamics : Simuler les biomolécules', 'tags' => ['bioinformatics', 'md', 'gromacs', 'simulation']],
            ['title' => 'Network biology : Analyser les réseaux biologiques', 'tags' => ['bioinformatics', 'networks', 'cytoscape', 'pathways']],
            ['title' => 'Variant calling : Identifier les mutations', 'tags' => ['bioinformatics', 'variant', 'gatk', 'snp']],
            ['title' => 'Structural bioinformatics : De la séquence à la structure', 'tags' => ['bioinformatics', 'structure', 'modeling', 'docking']],
            ['title' => 'Multi-omics integration : Combiner les données', 'tags' => ['bioinformatics', 'multi-omics', 'integration', 'mofa']],
            ['title' => 'Cloud computing en bioinformatique', 'tags' => ['bioinformatics', 'cloud', 'aws', 'nextflow']],
        ]
    ],
    'agricultural-biotech' => [
        'name' => 'Agricultural Biotech',
        'slug' => 'agricultural-biotech',
        'description' => 'Biotechnologies agricoles et sécurité alimentaire',
        'color' => '#84cc16',
        'articles' => [
            ['title' => 'Gene editing agricole : CRISPR dans les champs', 'tags' => ['agricultural-biotech', 'crispr', 'crops', 'gene-editing']],
            ['title' => 'Cultures résistantes à la sécheresse', 'tags' => ['agricultural-biotech', 'drought', 'resilience', 'climate']],
            ['title' => 'Golden Rice : OGM humanitaire', 'tags' => ['agricultural-biotech', 'golden-rice', 'vitamin-a', 'nutrition']],
            ['title' => 'Biopesticides : Alternatives aux pesticides chimiques', 'tags' => ['agricultural-biotech', 'biopesticides', 'biocontrol', 'sustainable']],
            ['title' => 'Vertical farming : Agriculture urbaine high-tech', 'tags' => ['agricultural-biotech', 'vertical-farming', 'led', 'hydroponics']],
            ['title' => 'Microbiome du sol : Optimiser la fertilité', 'tags' => ['agricultural-biotech', 'soil-microbiome', 'nitrogen', 'rhizosphere']],
            ['title' => 'Viande cultivée : Protéines sans élevage', 'tags' => ['agricultural-biotech', 'cultured-meat', 'cell-culture', 'food']],
            ['title' => 'RNA interference en agriculture', 'tags' => ['agricultural-biotech', 'rnai', 'pest-control', 'dsrna']],
            ['title' => 'Algues et microalgues : Biocarburants et alimentation', 'tags' => ['agricultural-biotech', 'algae', 'biofuels', 'spirulina']],
            ['title' => 'Precision breeding : Sélection assistée par génomique', 'tags' => ['agricultural-biotech', 'precision-breeding', 'markers', 'gwas']],
        ]
    ],
    'diagnostics' => [
        'name' => 'Diagnostics & Biosensors',
        'slug' => 'diagnostics-biosensors',
        'description' => 'Diagnostics moléculaires et biocapteurs',
        'color' => '#f43f5e',
        'articles' => [
            ['title' => 'Liquid biopsy : Détecter le cancer dans le sang', 'tags' => ['diagnostics', 'liquid-biopsy', 'ctdna', 'cancer']],
            ['title' => 'PCR et ses évolutions : qPCR, dPCR, LAMP', 'tags' => ['diagnostics', 'pcr', 'qpcr', 'molecular']],
            ['title' => 'Biosensors : Détection en temps réel', 'tags' => ['diagnostics', 'biosensors', 'electrochemical', 'optical']],
            ['title' => 'Point-of-care testing : Diagnostics décentralisés', 'tags' => ['diagnostics', 'poct', 'portable', 'rapid']],
            ['title' => 'NGS diagnostique : Séquençage clinique', 'tags' => ['diagnostics', 'ngs', 'clinical', 'panels']],
            ['title' => 'CRISPR diagnostics : SHERLOCK et DETECTR', 'tags' => ['diagnostics', 'crispr-dx', 'sherlock', 'detectr']],
            ['title' => 'Wearable biosensors : Monitoring continu', 'tags' => ['diagnostics', 'wearables', 'glucose', 'continuous']],
            ['title' => 'Mass spectrometry clinique', 'tags' => ['diagnostics', 'mass-spec', 'proteomics', 'metabolomics']],
            ['title' => 'Companion diagnostics : Thérapie personnalisée', 'tags' => ['diagnostics', 'cdx', 'biomarkers', 'precision']],
            ['title' => 'AI en pathologie : Analyse d\'images médicales', 'tags' => ['diagnostics', 'ai', 'pathology', 'deep-learning']],
        ]
    ],
    'neuroscience' => [
        'name' => 'Neuroscience & Brain',
        'slug' => 'neuroscience-brain',
        'description' => 'Neurosciences et technologies du cerveau',
        'color' => '#7c3aed',
        'articles' => [
            ['title' => 'Brain-computer interfaces : Neuralink et alternatives', 'tags' => ['neuroscience', 'bci', 'neuralink', 'neural']],
            ['title' => 'Optogenetics : Contrôler les neurones par la lumière', 'tags' => ['neuroscience', 'optogenetics', 'channelrhodopsin', 'light']],
            ['title' => 'Connectomics : Cartographier le cerveau', 'tags' => ['neuroscience', 'connectomics', 'mapping', 'synapses']],
            ['title' => 'Neurodégénérescence : Alzheimer et Parkinson', 'tags' => ['neuroscience', 'neurodegeneration', 'alzheimer', 'parkinson']],
            ['title' => 'Neuroplasticité : Le cerveau qui se réorganise', 'tags' => ['neuroscience', 'plasticity', 'learning', 'recovery']],
            ['title' => 'Single-neuron recording : Technologies et applications', 'tags' => ['neuroscience', 'electrophysiology', 'neurons', 'recording']],
            ['title' => 'Gene therapy neurologique', 'tags' => ['neuroscience', 'gene-therapy', 'cns', 'delivery']],
            ['title' => 'Organoids cérébraux : Mini-cerveaux in vitro', 'tags' => ['neuroscience', 'brain-organoids', '3d-culture', 'development']],
            ['title' => 'Neuroimaging avancé : fMRI et au-delà', 'tags' => ['neuroscience', 'neuroimaging', 'fmri', 'pet']],
            ['title' => 'Psychedelics thérapeutiques : Renaissance de la recherche', 'tags' => ['neuroscience', 'psychedelics', 'psilocybin', 'mental-health']],
        ]
    ],
    'immunology' => [
        'name' => 'Immunology & Vaccines',
        'slug' => 'immunology-vaccines',
        'description' => 'Immunologie et développement de vaccins',
        'color' => '#14b8a6',
        'articles' => [
            ['title' => 'mRNA vaccines : Technologie et perspectives', 'tags' => ['immunology', 'mrna', 'vaccines', 'lipid-nanoparticles']],
            ['title' => 'Universal flu vaccine : Le saint graal vaccinal', 'tags' => ['immunology', 'influenza', 'universal', 'hemagglutinin']],
            ['title' => 'Cancer vaccines : Vaccins thérapeutiques', 'tags' => ['immunology', 'cancer-vaccines', 'neoantigens', 'personalized']],
            ['title' => 'Adjuvants nouvelle génération', 'tags' => ['immunology', 'adjuvants', 'as01', 'innate']],
            ['title' => 'T cell exhaustion : Comprendre l\'épuisement immunitaire', 'tags' => ['immunology', 't-cells', 'exhaustion', 'pd1']],
            ['title' => 'Mucosal immunity : Immunité des muqueuses', 'tags' => ['immunology', 'mucosal', 'iga', 'nasal']],
            ['title' => 'Systems immunology : Approche systémique', 'tags' => ['immunology', 'systems', 'multi-omics', 'modeling']],
            ['title' => 'Autoimmunité : Mécanismes et traitements', 'tags' => ['immunology', 'autoimmune', 'tolerance', 'treg']],
            ['title' => 'Trained immunity : Mémoire innée', 'tags' => ['immunology', 'trained-immunity', 'innate', 'epigenetics']],
            ['title' => 'Vaccine delivery : Nouvelles plateformes', 'tags' => ['immunology', 'delivery', 'microneedles', 'nanoparticles']],
        ]
    ],
];

// ============================================================================
// IA ADDITIONNELLE - 100 articles
// ============================================================================

$aiTrends = [
    'ai-reasoning' => [
        'name' => 'AI Reasoning',
        'slug' => 'ai-reasoning',
        'description' => 'Raisonnement et cognition en IA',
        'color' => '#8b5cf6',
        'articles' => [
            ['title' => 'Chain-of-Thought Prompting : Raisonnement étape par étape', 'tags' => ['ai-reasoning', 'cot', 'prompting', 'reasoning']],
            ['title' => 'Tree of Thoughts : Exploration arborescente', 'tags' => ['ai-reasoning', 'tot', 'search', 'reasoning']],
            ['title' => 'Self-consistency : Améliorer la fiabilité des LLMs', 'tags' => ['ai-reasoning', 'self-consistency', 'sampling', 'voting']],
            ['title' => 'Constitutional AI : Aligner les modèles par principes', 'tags' => ['ai-reasoning', 'constitutional', 'alignment', 'safety']],
            ['title' => 'Reward modeling : Apprendre les préférences humaines', 'tags' => ['ai-reasoning', 'rlhf', 'reward', 'preferences']],
            ['title' => 'Causal reasoning en IA : Au-delà des corrélations', 'tags' => ['ai-reasoning', 'causal', 'inference', 'interventions']],
            ['title' => 'Neuro-symbolic AI : Combiner neural et symbolique', 'tags' => ['ai-reasoning', 'neuro-symbolic', 'logic', 'hybrid']],
            ['title' => 'Metacognition en IA : Modèles qui savent ce qu\'ils savent', 'tags' => ['ai-reasoning', 'metacognition', 'uncertainty', 'calibration']],
            ['title' => 'Analogical reasoning : Raisonner par analogie', 'tags' => ['ai-reasoning', 'analogical', 'transfer', 'abstraction']],
            ['title' => 'Mathematical reasoning : LLMs et mathématiques', 'tags' => ['ai-reasoning', 'math', 'theorem-proving', 'formal']],
        ]
    ],
    'multimodal-ai' => [
        'name' => 'Multimodal AI',
        'slug' => 'multimodal-ai',
        'description' => 'IA multimodale et vision-langage',
        'color' => '#ec4899',
        'articles' => [
            ['title' => 'GPT-4V et au-delà : Vision dans les LLMs', 'tags' => ['multimodal-ai', 'gpt4v', 'vision', 'vlm']],
            ['title' => 'CLIP et contrastive learning multimodal', 'tags' => ['multimodal-ai', 'clip', 'contrastive', 'embeddings']],
            ['title' => 'Video understanding : Comprendre les vidéos avec l\'IA', 'tags' => ['multimodal-ai', 'video', 'temporal', 'understanding']],
            ['title' => 'Audio-visual learning : Son et image ensemble', 'tags' => ['multimodal-ai', 'audio-visual', 'speech', 'sound']],
            ['title' => 'Document AI : Analyser documents et formulaires', 'tags' => ['multimodal-ai', 'document', 'ocr', 'layout']],
            ['title' => '3D understanding avec l\'IA', 'tags' => ['multimodal-ai', '3d', 'point-clouds', 'nerf']],
            ['title' => 'Text-to-image : Génération d\'images par le texte', 'tags' => ['multimodal-ai', 'text-to-image', 'diffusion', 'generation']],
            ['title' => 'Image captioning avancé : Décrire les images', 'tags' => ['multimodal-ai', 'captioning', 'description', 'vqa']],
            ['title' => 'Multimodal RAG : Retrieval sur tous les médias', 'tags' => ['multimodal-ai', 'rag', 'retrieval', 'multimodal']],
            ['title' => 'Embodied AI : IA incarnée dans des robots', 'tags' => ['multimodal-ai', 'embodied', 'robotics', 'manipulation']],
        ]
    ],
    'llm-optimization' => [
        'name' => 'LLM Optimization',
        'slug' => 'llm-optimization',
        'description' => 'Optimisation et efficacité des LLMs',
        'color' => '#06b6d4',
        'articles' => [
            ['title' => 'Quantization des LLMs : INT8, INT4 et au-delà', 'tags' => ['llm-optimization', 'quantization', 'int8', 'efficiency']],
            ['title' => 'Knowledge distillation : Compresser les modèles', 'tags' => ['llm-optimization', 'distillation', 'student', 'teacher']],
            ['title' => 'Pruning des réseaux de neurones', 'tags' => ['llm-optimization', 'pruning', 'sparsity', 'compression']],
            ['title' => 'Speculative decoding : Accélérer l\'inférence', 'tags' => ['llm-optimization', 'speculative', 'decoding', 'speed']],
            ['title' => 'Flash Attention : Attention efficace en mémoire', 'tags' => ['llm-optimization', 'flash-attention', 'memory', 'gpu']],
            ['title' => 'Mixture of Experts : Scaling efficace', 'tags' => ['llm-optimization', 'moe', 'sparse', 'routing']],
            ['title' => 'KV cache optimization : Réduire la mémoire', 'tags' => ['llm-optimization', 'kv-cache', 'memory', 'inference']],
            ['title' => 'Continuous batching : Maximiser le throughput', 'tags' => ['llm-optimization', 'batching', 'throughput', 'serving']],
            ['title' => 'LoRA et PEFT : Fine-tuning efficient', 'tags' => ['llm-optimization', 'lora', 'peft', 'adapters']],
            ['title' => 'TensorRT-LLM et optimisation GPU', 'tags' => ['llm-optimization', 'tensorrt', 'nvidia', 'inference']],
        ]
    ],
    'ai-safety' => [
        'name' => 'AI Safety & Alignment',
        'slug' => 'ai-safety-alignment',
        'description' => 'Sécurité et alignement de l\'IA',
        'color' => '#ef4444',
        'articles' => [
            ['title' => 'AI Alignment : Le problème fondamental', 'tags' => ['ai-safety', 'alignment', 'values', 'goals']],
            ['title' => 'Jailbreaking des LLMs : Attaques et défenses', 'tags' => ['ai-safety', 'jailbreak', 'adversarial', 'security']],
            ['title' => 'Red teaming en IA : Trouver les failles', 'tags' => ['ai-safety', 'red-team', 'testing', 'vulnerabilities']],
            ['title' => 'Interpretability : Comprendre les décisions de l\'IA', 'tags' => ['ai-safety', 'interpretability', 'explainability', 'xai']],
            ['title' => 'Hallucinations des LLMs : Causes et solutions', 'tags' => ['ai-safety', 'hallucinations', 'factuality', 'grounding']],
            ['title' => 'AI governance : Cadres réglementaires', 'tags' => ['ai-safety', 'governance', 'regulation', 'eu-ai-act']],
            ['title' => 'Dual-use AI : Risques et responsabilités', 'tags' => ['ai-safety', 'dual-use', 'biosecurity', 'cyber']],
            ['title' => 'Watermarking des contenus générés par IA', 'tags' => ['ai-safety', 'watermarking', 'detection', 'authenticity']],
            ['title' => 'AI ethics : Biais et équité', 'tags' => ['ai-safety', 'ethics', 'bias', 'fairness']],
            ['title' => 'Existential risk from AI : Débat et perspectives', 'tags' => ['ai-safety', 'x-risk', 'agi', 'long-term']],
        ]
    ],
    'ai-infrastructure' => [
        'name' => 'AI Infrastructure',
        'slug' => 'ai-infrastructure',
        'description' => 'Infrastructure et MLOps pour l\'IA',
        'color' => '#3b82f6',
        'articles' => [
            ['title' => 'GPU clusters pour l\'entraînement des LLMs', 'tags' => ['ai-infrastructure', 'gpu', 'clusters', 'training']],
            ['title' => 'H100, B100, B200 : Guide des GPUs NVIDIA', 'tags' => ['ai-infrastructure', 'nvidia', 'h100', 'hardware']],
            ['title' => 'TPU vs GPU : Comparatif pour l\'IA', 'tags' => ['ai-infrastructure', 'tpu', 'google', 'comparison']],
            ['title' => 'Distributed training : Entraînement distribué', 'tags' => ['ai-infrastructure', 'distributed', 'deepspeed', 'fsdp']],
            ['title' => 'Vector databases : Stockage pour RAG', 'tags' => ['ai-infrastructure', 'vector-db', 'pinecone', 'weaviate']],
            ['title' => 'Model serving : Déployer des modèles en production', 'tags' => ['ai-infrastructure', 'serving', 'vllm', 'triton']],
            ['title' => 'Feature stores pour le ML', 'tags' => ['ai-infrastructure', 'feature-store', 'feast', 'mlops']],
            ['title' => 'Experiment tracking : MLflow, W&B, Neptune', 'tags' => ['ai-infrastructure', 'tracking', 'mlflow', 'wandb']],
            ['title' => 'Data pipelines pour l\'IA', 'tags' => ['ai-infrastructure', 'data', 'pipelines', 'etl']],
            ['title' => 'Cost optimization pour l\'IA cloud', 'tags' => ['ai-infrastructure', 'cost', 'optimization', 'spot']],
        ]
    ],
    'ai-applications' => [
        'name' => 'AI Applications',
        'slug' => 'ai-applications',
        'description' => 'Applications pratiques de l\'IA',
        'color' => '#f59e0b',
        'articles' => [
            ['title' => 'AI dans la santé : Diagnostic et traitement', 'tags' => ['ai-applications', 'healthcare', 'diagnosis', 'medical']],
            ['title' => 'AI dans la finance : Trading et risque', 'tags' => ['ai-applications', 'finance', 'trading', 'risk']],
            ['title' => 'AI dans le retail : Personnalisation et prédiction', 'tags' => ['ai-applications', 'retail', 'recommendation', 'demand']],
            ['title' => 'AI dans l\'éducation : Tuteurs personnalisés', 'tags' => ['ai-applications', 'education', 'tutoring', 'adaptive']],
            ['title' => 'AI dans le légal : LegalTech et contrats', 'tags' => ['ai-applications', 'legal', 'contracts', 'review']],
            ['title' => 'AI dans les RH : Recrutement et talent', 'tags' => ['ai-applications', 'hr', 'recruitment', 'talent']],
            ['title' => 'AI dans le gaming : PNJ et génération', 'tags' => ['ai-applications', 'gaming', 'npc', 'procedural']],
            ['title' => 'AI dans l\'agriculture : Precision farming', 'tags' => ['ai-applications', 'agriculture', 'precision', 'yield']],
            ['title' => 'AI dans la supply chain : Optimisation logistique', 'tags' => ['ai-applications', 'supply-chain', 'logistics', 'optimization']],
            ['title' => 'AI dans l\'énergie : Smart grids et prédiction', 'tags' => ['ai-applications', 'energy', 'smart-grid', 'forecasting']],
        ]
    ],
    'generative-models' => [
        'name' => 'Generative Models',
        'slug' => 'generative-models',
        'description' => 'Modèles génératifs et création de contenu',
        'color' => '#a855f7',
        'articles' => [
            ['title' => 'Diffusion models : De DDPM à Stable Diffusion', 'tags' => ['generative-models', 'diffusion', 'stable-diffusion', 'ddpm']],
            ['title' => 'Flow matching : Nouvelle approche générative', 'tags' => ['generative-models', 'flow', 'matching', 'ode']],
            ['title' => 'Video generation : Sora et alternatives', 'tags' => ['generative-models', 'video', 'sora', 'generation']],
            ['title' => '3D generation : De l\'image au 3D', 'tags' => ['generative-models', '3d', 'reconstruction', 'gaussian-splatting']],
            ['title' => 'Music generation : IA compositeur', 'tags' => ['generative-models', 'music', 'audio', 'composition']],
            ['title' => 'Voice cloning et text-to-speech', 'tags' => ['generative-models', 'voice', 'tts', 'cloning']],
            ['title' => 'Controllable generation : Guider la génération', 'tags' => ['generative-models', 'controlnet', 'guidance', 'conditioning']],
            ['title' => 'GANs vs Diffusion : Comparatif', 'tags' => ['generative-models', 'gan', 'comparison', 'quality']],
            ['title' => 'Autoregressive image models', 'tags' => ['generative-models', 'autoregressive', 'transformers', 'images']],
            ['title' => 'Latent spaces et manipulation', 'tags' => ['generative-models', 'latent', 'editing', 'interpolation']],
        ]
    ],
    'nlp-advanced' => [
        'name' => 'Advanced NLP',
        'slug' => 'advanced-nlp',
        'description' => 'Traitement du langage naturel avancé',
        'color' => '#10b981',
        'articles' => [
            ['title' => 'Transformers architecture : Attention is all you need', 'tags' => ['advanced-nlp', 'transformers', 'attention', 'architecture']],
            ['title' => 'Tokenization avancée : BPE, WordPiece, SentencePiece', 'tags' => ['advanced-nlp', 'tokenization', 'bpe', 'subword']],
            ['title' => 'Position encodings : Absolute vs Relative vs RoPE', 'tags' => ['advanced-nlp', 'positions', 'rope', 'alibi']],
            ['title' => 'Long context : Extending LLM context windows', 'tags' => ['advanced-nlp', 'long-context', 'streaming', 'memory']],
            ['title' => 'Multilingual NLP : Modèles multilingues', 'tags' => ['advanced-nlp', 'multilingual', 'translation', 'cross-lingual']],
            ['title' => 'Named Entity Recognition avancé', 'tags' => ['advanced-nlp', 'ner', 'entities', 'extraction']],
            ['title' => 'Sentiment analysis : Au-delà du positif/négatif', 'tags' => ['advanced-nlp', 'sentiment', 'opinion', 'aspect']],
            ['title' => 'Question Answering : Systèmes de Q&A', 'tags' => ['advanced-nlp', 'qa', 'reading', 'comprehension']],
            ['title' => 'Text summarization : Résumé automatique', 'tags' => ['advanced-nlp', 'summarization', 'abstractive', 'extractive']],
            ['title' => 'Semantic search : Recherche sémantique', 'tags' => ['advanced-nlp', 'semantic-search', 'embeddings', 'retrieval']],
        ]
    ],
    'robotics-ai' => [
        'name' => 'Robotics & AI',
        'slug' => 'robotics-ai',
        'description' => 'Intelligence artificielle pour la robotique',
        'color' => '#64748b',
        'articles' => [
            ['title' => 'Foundation models pour la robotique', 'tags' => ['robotics-ai', 'foundation', 'rt-2', 'manipulation']],
            ['title' => 'Sim-to-real transfer : Du simulateur au réel', 'tags' => ['robotics-ai', 'sim2real', 'domain-randomization', 'transfer']],
            ['title' => 'Reinforcement learning en robotique', 'tags' => ['robotics-ai', 'rl', 'policy', 'control']],
            ['title' => 'Vision pour robots : Perception active', 'tags' => ['robotics-ai', 'vision', 'perception', 'depth']],
            ['title' => 'Motion planning avec l\'IA', 'tags' => ['robotics-ai', 'motion-planning', 'path', 'optimization']],
            ['title' => 'Grasping et manipulation d\'objets', 'tags' => ['robotics-ai', 'grasping', 'manipulation', 'dexterous']],
            ['title' => 'Human-robot interaction', 'tags' => ['robotics-ai', 'hri', 'collaboration', 'safety']],
            ['title' => 'Autonomous vehicles : État de l\'art', 'tags' => ['robotics-ai', 'autonomous', 'vehicles', 'perception']],
            ['title' => 'Drones autonomes et navigation', 'tags' => ['robotics-ai', 'drones', 'uav', 'slam']],
            ['title' => 'Humanoid robots : De Tesla Bot à Figure', 'tags' => ['robotics-ai', 'humanoid', 'bipedal', 'locomotion']],
        ]
    ],
    'ai-research' => [
        'name' => 'AI Research Frontiers',
        'slug' => 'ai-research-frontiers',
        'description' => 'Frontières de la recherche en IA',
        'color' => '#f472b6',
        'articles' => [
            ['title' => 'Scaling laws : Les lois d\'échelle en IA', 'tags' => ['ai-research', 'scaling', 'laws', 'chinchilla']],
            ['title' => 'Emergent abilities : Capacités émergentes des LLMs', 'tags' => ['ai-research', 'emergent', 'abilities', 'surprise']],
            ['title' => 'In-context learning : Apprendre sans fine-tuning', 'tags' => ['ai-research', 'icl', 'few-shot', 'learning']],
            ['title' => 'World models : Modèles du monde', 'tags' => ['ai-research', 'world-models', 'simulation', 'prediction']],
            ['title' => 'Test-time compute : Calcul à l\'inférence', 'tags' => ['ai-research', 'test-time', 'compute', 'scaling']],
            ['title' => 'Continual learning : Apprentissage continu', 'tags' => ['ai-research', 'continual', 'lifelong', 'catastrophic']],
            ['title' => 'Meta-learning : Apprendre à apprendre', 'tags' => ['ai-research', 'meta-learning', 'maml', 'adaptation']],
            ['title' => 'Neural architecture search', 'tags' => ['ai-research', 'nas', 'automl', 'architecture']],
            ['title' => 'Consciousness et AI : Le débat', 'tags' => ['ai-research', 'consciousness', 'sentience', 'philosophy']],
            ['title' => 'AGI : Artificial General Intelligence', 'tags' => ['ai-research', 'agi', 'general', 'intelligence']],
        ]
    ],
];

// ============================================================================
// QUANTUM COMPUTING - 100 articles
// ============================================================================

$quantumTrends = [
    'quantum-hardware' => [
        'name' => 'Quantum Hardware',
        'slug' => 'quantum-hardware',
        'description' => 'Hardware et processeurs quantiques',
        'color' => '#3b82f6',
        'articles' => [
            ['title' => 'Superconducting qubits : IBM et Google', 'tags' => ['quantum-hardware', 'superconducting', 'ibm', 'google']],
            ['title' => 'Trapped ions : IonQ et Quantinuum', 'tags' => ['quantum-hardware', 'trapped-ions', 'ionq', 'quantinuum']],
            ['title' => 'Photonic quantum computing : Xanadu et PsiQuantum', 'tags' => ['quantum-hardware', 'photonic', 'xanadu', 'light']],
            ['title' => 'Neutral atoms : QuEra et Pasqal', 'tags' => ['quantum-hardware', 'neutral-atoms', 'quera', 'pasqal']],
            ['title' => 'Topological qubits : L\'approche Microsoft', 'tags' => ['quantum-hardware', 'topological', 'microsoft', 'majorana']],
            ['title' => 'Silicon spin qubits : Intel et startups', 'tags' => ['quantum-hardware', 'silicon', 'spin', 'intel']],
            ['title' => 'Quantum annealing : D-Wave et applications', 'tags' => ['quantum-hardware', 'annealing', 'd-wave', 'optimization']],
            ['title' => 'Cryogenic systems : Refroidir les qubits', 'tags' => ['quantum-hardware', 'cryogenic', 'dilution', 'temperature']],
            ['title' => 'Quantum interconnects : Relier les processeurs', 'tags' => ['quantum-hardware', 'interconnects', 'networking', 'modular']],
            ['title' => 'Room temperature quantum : Défis et promesses', 'tags' => ['quantum-hardware', 'room-temp', 'nv-centers', 'diamond']],
        ]
    ],
    'quantum-algorithms' => [
        'name' => 'Quantum Algorithms',
        'slug' => 'quantum-algorithms',
        'description' => 'Algorithmes quantiques et applications',
        'color' => '#8b5cf6',
        'articles' => [
            ['title' => 'Shor\'s algorithm : Factorisation et cryptographie', 'tags' => ['quantum-algorithms', 'shor', 'factoring', 'rsa']],
            ['title' => 'Grover\'s algorithm : Recherche quantique', 'tags' => ['quantum-algorithms', 'grover', 'search', 'quadratic']],
            ['title' => 'VQE : Variational Quantum Eigensolver', 'tags' => ['quantum-algorithms', 'vqe', 'variational', 'chemistry']],
            ['title' => 'QAOA : Quantum Approximate Optimization', 'tags' => ['quantum-algorithms', 'qaoa', 'optimization', 'combinatorial']],
            ['title' => 'Quantum machine learning algorithms', 'tags' => ['quantum-algorithms', 'qml', 'kernels', 'neural']],
            ['title' => 'Quantum simulation : Simuler la physique', 'tags' => ['quantum-algorithms', 'simulation', 'hamiltonians', 'molecules']],
            ['title' => 'Quantum walks : Marches quantiques', 'tags' => ['quantum-algorithms', 'quantum-walks', 'graphs', 'speedup']],
            ['title' => 'HHL algorithm : Linear systems', 'tags' => ['quantum-algorithms', 'hhl', 'linear', 'exponential']],
            ['title' => 'Quantum phase estimation', 'tags' => ['quantum-algorithms', 'qpe', 'phase', 'eigenvalues']],
            ['title' => 'Quantum counting et sampling', 'tags' => ['quantum-algorithms', 'counting', 'sampling', 'boson']],
        ]
    ],
    'quantum-error-correction' => [
        'name' => 'Quantum Error Correction',
        'slug' => 'quantum-error-correction',
        'description' => 'Correction d\'erreurs quantiques',
        'color' => '#ef4444',
        'articles' => [
            ['title' => 'Surface codes : Le standard de la QEC', 'tags' => ['quantum-error-correction', 'surface', 'codes', 'lattice']],
            ['title' => 'Logical qubits vs Physical qubits', 'tags' => ['quantum-error-correction', 'logical', 'physical', 'overhead']],
            ['title' => 'Threshold theorem : Le seuil d\'erreur', 'tags' => ['quantum-error-correction', 'threshold', 'fault-tolerant', 'theory']],
            ['title' => 'LDPC codes pour le quantique', 'tags' => ['quantum-error-correction', 'ldpc', 'sparse', 'efficient']],
            ['title' => 'Color codes et topologie', 'tags' => ['quantum-error-correction', 'color-codes', 'topology', '2d']],
            ['title' => 'Bosonic codes : Cat qubits et GKP', 'tags' => ['quantum-error-correction', 'bosonic', 'cat', 'gkp']],
            ['title' => 'Real-time error decoding', 'tags' => ['quantum-error-correction', 'decoding', 'real-time', 'neural']],
            ['title' => 'Magic state distillation', 'tags' => ['quantum-error-correction', 'magic-states', 'distillation', 't-gates']],
            ['title' => 'Quantum memory : Stocker l\'information quantique', 'tags' => ['quantum-error-correction', 'memory', 'coherence', 'storage']],
            ['title' => 'Error mitigation vs Error correction', 'tags' => ['quantum-error-correction', 'mitigation', 'nisq', 'comparison']],
        ]
    ],
    'quantum-software' => [
        'name' => 'Quantum Software',
        'slug' => 'quantum-software',
        'description' => 'Logiciels et frameworks quantiques',
        'color' => '#06b6d4',
        'articles' => [
            ['title' => 'Qiskit : L\'écosystème IBM Quantum', 'tags' => ['quantum-software', 'qiskit', 'ibm', 'python']],
            ['title' => 'Cirq : Le framework Google', 'tags' => ['quantum-software', 'cirq', 'google', 'nisq']],
            ['title' => 'PennyLane : Quantum machine learning', 'tags' => ['quantum-software', 'pennylane', 'qml', 'xanadu']],
            ['title' => 'Amazon Braket : Quantum dans AWS', 'tags' => ['quantum-software', 'braket', 'aws', 'cloud']],
            ['title' => 'Azure Quantum : L\'offre Microsoft', 'tags' => ['quantum-software', 'azure', 'microsoft', 'q-sharp']],
            ['title' => 'Quantum compilers : Optimiser les circuits', 'tags' => ['quantum-software', 'compilers', 'optimization', 'transpilation']],
            ['title' => 'Quantum simulators : Tester sans hardware', 'tags' => ['quantum-software', 'simulators', 'classical', 'emulation']],
            ['title' => 'Hybrid quantum-classical workflows', 'tags' => ['quantum-software', 'hybrid', 'classical', 'integration']],
            ['title' => 'Quantum programming languages', 'tags' => ['quantum-software', 'languages', 'silq', 'quipper']],
            ['title' => 'Benchmarking quantum computers', 'tags' => ['quantum-software', 'benchmarking', 'metrics', 'volumetric']],
        ]
    ],
    'quantum-applications' => [
        'name' => 'Quantum Applications',
        'slug' => 'quantum-applications',
        'description' => 'Applications du calcul quantique',
        'color' => '#f59e0b',
        'articles' => [
            ['title' => 'Quantum chemistry : Simuler les molécules', 'tags' => ['quantum-applications', 'chemistry', 'molecules', 'drug-discovery']],
            ['title' => 'Quantum optimization : Problèmes combinatoires', 'tags' => ['quantum-applications', 'optimization', 'logistics', 'portfolio']],
            ['title' => 'Quantum machine learning : État de l\'art', 'tags' => ['quantum-applications', 'qml', 'kernels', 'advantage']],
            ['title' => 'Quantum finance : Pricing et risque', 'tags' => ['quantum-applications', 'finance', 'monte-carlo', 'option']],
            ['title' => 'Quantum sensing : Capteurs quantiques', 'tags' => ['quantum-applications', 'sensing', 'magnetometry', 'gravity']],
            ['title' => 'Quantum materials discovery', 'tags' => ['quantum-applications', 'materials', 'superconductors', 'batteries']],
            ['title' => 'Quantum for pharma : Drug design', 'tags' => ['quantum-applications', 'pharma', 'drug', 'protein']],
            ['title' => 'Quantum climate modeling', 'tags' => ['quantum-applications', 'climate', 'weather', 'simulation']],
            ['title' => 'Quantum in aerospace', 'tags' => ['quantum-applications', 'aerospace', 'scheduling', 'navigation']],
            ['title' => 'Quantum for energy : Grid optimization', 'tags' => ['quantum-applications', 'energy', 'grid', 'renewable']],
        ]
    ],
    'quantum-cryptography' => [
        'name' => 'Quantum Cryptography',
        'slug' => 'quantum-cryptography',
        'description' => 'Cryptographie quantique et post-quantique',
        'color' => '#ec4899',
        'articles' => [
            ['title' => 'QKD : Quantum Key Distribution', 'tags' => ['quantum-cryptography', 'qkd', 'bb84', 'key-exchange']],
            ['title' => 'Post-quantum cryptography : NIST standards', 'tags' => ['quantum-cryptography', 'post-quantum', 'nist', 'lattice']],
            ['title' => 'Harvest now, decrypt later : La menace', 'tags' => ['quantum-cryptography', 'hndl', 'threat', 'data']],
            ['title' => 'Quantum random number generators', 'tags' => ['quantum-cryptography', 'qrng', 'random', 'entropy']],
            ['title' => 'Quantum-safe migration : Comment se préparer', 'tags' => ['quantum-cryptography', 'migration', 'transition', 'strategy']],
            ['title' => 'Entanglement-based QKD', 'tags' => ['quantum-cryptography', 'entanglement', 'e91', 'bell']],
            ['title' => 'Satellite QKD : Communication quantique spatiale', 'tags' => ['quantum-cryptography', 'satellite', 'micius', 'space']],
            ['title' => 'Quantum digital signatures', 'tags' => ['quantum-cryptography', 'signatures', 'authentication', 'unforgeable']],
            ['title' => 'Lattice-based cryptography', 'tags' => ['quantum-cryptography', 'lattice', 'kyber', 'dilithium']],
            ['title' => 'Hash-based signatures : SPHINCS+', 'tags' => ['quantum-cryptography', 'hash', 'sphincs', 'stateless']],
        ]
    ],
    'quantum-networking' => [
        'name' => 'Quantum Networking',
        'slug' => 'quantum-networking',
        'description' => 'Réseaux quantiques et internet quantique',
        'color' => '#10b981',
        'articles' => [
            ['title' => 'Quantum internet : Vision et timeline', 'tags' => ['quantum-networking', 'internet', 'vision', 'stages']],
            ['title' => 'Quantum repeaters : Étendre la portée', 'tags' => ['quantum-networking', 'repeaters', 'entanglement', 'swap']],
            ['title' => 'Quantum memory networks', 'tags' => ['quantum-networking', 'memory', 'nodes', 'storage']],
            ['title' => 'Entanglement distribution', 'tags' => ['quantum-networking', 'entanglement', 'distribution', 'fidelity']],
            ['title' => 'Quantum teleportation networks', 'tags' => ['quantum-networking', 'teleportation', 'state-transfer', 'fidelity']],
            ['title' => 'Metropolitan quantum networks', 'tags' => ['quantum-networking', 'metropolitan', 'fiber', 'testbeds']],
            ['title' => 'Quantum network protocols', 'tags' => ['quantum-networking', 'protocols', 'routing', 'stack']],
            ['title' => 'Hybrid classical-quantum networks', 'tags' => ['quantum-networking', 'hybrid', 'integration', 'coexistence']],
            ['title' => 'Quantum network simulation', 'tags' => ['quantum-networking', 'simulation', 'netsquid', 'modeling']],
            ['title' => 'Quantum network security', 'tags' => ['quantum-networking', 'security', 'attacks', 'countermeasures']],
        ]
    ],
    'quantum-industry' => [
        'name' => 'Quantum Industry',
        'slug' => 'quantum-industry',
        'description' => 'Industrie et marché du quantique',
        'color' => '#64748b',
        'articles' => [
            ['title' => 'État du marché quantique 2025', 'tags' => ['quantum-industry', 'market', '2025', 'growth']],
            ['title' => 'IBM Quantum : Roadmap et stratégie', 'tags' => ['quantum-industry', 'ibm', 'roadmap', 'condor']],
            ['title' => 'Google Quantum AI : De Sycamore à Willow', 'tags' => ['quantum-industry', 'google', 'willow', 'supremacy']],
            ['title' => 'Startups quantiques prometteuses', 'tags' => ['quantum-industry', 'startups', 'funding', 'innovation']],
            ['title' => 'Quantum workforce : Former les talents', 'tags' => ['quantum-industry', 'workforce', 'education', 'skills']],
            ['title' => 'National quantum initiatives', 'tags' => ['quantum-industry', 'national', 'government', 'funding']],
            ['title' => 'Quantum consulting et services', 'tags' => ['quantum-industry', 'consulting', 'services', 'enterprise']],
            ['title' => 'Quantum hardware supply chain', 'tags' => ['quantum-industry', 'supply-chain', 'components', 'manufacturing']],
            ['title' => 'Quantum standards et interoperability', 'tags' => ['quantum-industry', 'standards', 'ieee', 'interop']],
            ['title' => 'ROI du quantique : Quand et comment', 'tags' => ['quantum-industry', 'roi', 'business-case', 'timeline']],
        ]
    ],
    'quantum-physics' => [
        'name' => 'Quantum Physics Fundamentals',
        'slug' => 'quantum-physics-fundamentals',
        'description' => 'Fondamentaux de la physique quantique',
        'color' => '#a855f7',
        'articles' => [
            ['title' => 'Superposition quantique : Fondements', 'tags' => ['quantum-physics', 'superposition', 'state', 'measurement']],
            ['title' => 'Intrication quantique expliquée', 'tags' => ['quantum-physics', 'entanglement', 'bell', 'nonlocal']],
            ['title' => 'Decoherence : L\'ennemi du quantique', 'tags' => ['quantum-physics', 'decoherence', 'noise', 'environment']],
            ['title' => 'Portes quantiques : Le vocabulaire de base', 'tags' => ['quantum-physics', 'gates', 'hadamard', 'cnot']],
            ['title' => 'Mesure quantique et collapse', 'tags' => ['quantum-physics', 'measurement', 'collapse', 'born']],
            ['title' => 'No-cloning theorem : Pourquoi on ne peut pas copier', 'tags' => ['quantum-physics', 'no-cloning', 'theorem', 'security']],
            ['title' => 'Bloch sphere : Visualiser les qubits', 'tags' => ['quantum-physics', 'bloch', 'visualization', 'state']],
            ['title' => 'Quantum tunneling : Effet tunnel', 'tags' => ['quantum-physics', 'tunneling', 'barrier', 'probability']],
            ['title' => 'Bell inequalities : Prouver la non-localité', 'tags' => ['quantum-physics', 'bell', 'inequalities', 'experiments']],
            ['title' => 'Quantum field theory pour débutants', 'tags' => ['quantum-physics', 'qft', 'fields', 'particles']],
        ]
    ],
    'quantum-future' => [
        'name' => 'Quantum Future',
        'slug' => 'quantum-future',
        'description' => 'Futur et perspectives du quantique',
        'color' => '#f472b6',
        'articles' => [
            ['title' => 'Roadmap vers le fault-tolerant', 'tags' => ['quantum-future', 'roadmap', 'fault-tolerant', 'timeline']],
            ['title' => 'Quantum advantage : Quand et pour quoi', 'tags' => ['quantum-future', 'advantage', 'applications', 'timeline']],
            ['title' => 'Million qubit era : Défis et solutions', 'tags' => ['quantum-future', 'million', 'scaling', 'architecture']],
            ['title' => 'Quantum-classical computing convergence', 'tags' => ['quantum-future', 'convergence', 'hybrid', 'integration']],
            ['title' => 'Quantum AI : La convergence ultime', 'tags' => ['quantum-future', 'ai', 'convergence', 'synergy']],
            ['title' => 'Quantum cloud : L\'avenir du QaaS', 'tags' => ['quantum-future', 'cloud', 'qaas', 'access']],
            ['title' => 'Democratizing quantum : Accès pour tous', 'tags' => ['quantum-future', 'democratization', 'education', 'tools']],
            ['title' => 'Quantum sustainability : Impact environnemental', 'tags' => ['quantum-future', 'sustainability', 'energy', 'environment']],
            ['title' => 'Quantum revolution : Impact sociétal', 'tags' => ['quantum-future', 'revolution', 'society', 'impact']],
            ['title' => 'Prédictions quantiques 2030', 'tags' => ['quantum-future', 'predictions', '2030', 'forecast']],
        ]
    ],
];

// ============================================================================
// FONCTIONS DE GÉNÉRATION
// ============================================================================

function createCategory(string $basePath, string $id, array $data): void {
    $categoryData = [
        'id' => $id,
        'name' => $data['name'],
        'slug' => $data['slug'],
        'description' => $data['description'],
        'color' => $data['color'],
        'sortOrder' => rand(20, 100),
        'createdAt' => date('c'),
        'updatedAt' => date('c'),
    ];

    $filePath = $basePath . '/data/blog/categories/' . $id . '.json';
    file_put_contents($filePath, json_encode($categoryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function createArticles(string $basePath, string $categoryId, array $category, string $imageType, DateTime &$baseDate, int &$articleCount): int {
    $postsDir = $basePath . '/data/blog/posts';
    $created = 0;

    foreach ($category['articles'] as $index => $article) {
        $postId = $categoryId . '-' . sprintf('%02d', $index + 1);
        $slug = generateSlug($article['title']);

        // Date de publication échelonnée
        $publishDate = clone $baseDate;
        $publishDate->add(new DateInterval('PT' . rand(1, 6) . 'H'));
        $baseDate->add(new DateInterval('PT' . rand(2, 8) . 'H'));

        $postData = [
            'id' => $postId,
            'title' => $article['title'],
            'slug' => $slug,
            'content' => generateContent($article['title'], $article['tags']),
            'excerpt' => "Découvrez les dernières avancées : " . $article['title'],
            'author' => 'Lunar Tech',
            'categoryId' => $categoryId,
            'featuredImage' => getUniqueImage($imageType),
            'status' => 'published',
            'tags' => $article['tags'],
            'createdAt' => $publishDate->format('c'),
            'updatedAt' => $publishDate->format('c'),
            'publishedAt' => $publishDate->format('c'),
        ];

        $filePath = $postsDir . '/' . $postId . '.json';
        file_put_contents($filePath, json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $created++;
        $articleCount++;
    }

    return $created;
}

// ============================================================================
// EXÉCUTION
// ============================================================================

$baseDate = new DateTime('2025-11-15');
$totalArticles = 0;

// Biologie
echo "=== BIOLOGIE / BIOTECHNOLOGIE ===\n";
foreach ($biologyTrends as $categoryId => $category) {
    createCategory($basePath, $categoryId, $category);
    $count = createArticles($basePath, $categoryId, $category, 'biology', $baseDate, $totalArticles);
    echo "✓ {$category['name']}: {$count} articles\n";
}

// IA additionnelle
echo "\n=== INTELLIGENCE ARTIFICIELLE ===\n";
foreach ($aiTrends as $categoryId => $category) {
    createCategory($basePath, $categoryId, $category);
    $count = createArticles($basePath, $categoryId, $category, 'ai', $baseDate, $totalArticles);
    echo "✓ {$category['name']}: {$count} articles\n";
}

// Quantum Computing
echo "\n=== INFORMATIQUE QUANTIQUE ===\n";
foreach ($quantumTrends as $categoryId => $category) {
    createCategory($basePath, $categoryId, $category);
    $count = createArticles($basePath, $categoryId, $category, 'quantum', $baseDate, $totalArticles);
    echo "✓ {$category['name']}: {$count} articles\n";
}

// Mettre à jour les images des articles existants
echo "\n=== MISE À JOUR DES IMAGES EXISTANTES ===\n";
$postsDir = $basePath . '/data/blog/posts';
$existingFiles = glob($postsDir . '/*.json');
$updatedImages = 0;

foreach ($existingFiles as $file) {
    $data = json_decode(file_get_contents($file), true);
    if ($data === null) continue;

    // Déterminer le type d'image basé sur la catégorie
    $catId = $data['categoryId'] ?? '';
    $imageType = 'tech';
    if (strpos($catId, 'crispr') !== false || strpos($catId, 'biology') !== false || strpos($catId, 'genomics') !== false ||
        strpos($catId, 'cell') !== false || strpos($catId, 'drug') !== false || strpos($catId, 'bio') !== false ||
        strpos($catId, 'neuro') !== false || strpos($catId, 'immuno') !== false || strpos($catId, 'diagnostic') !== false) {
        $imageType = 'biology';
    } elseif (strpos($catId, 'quantum') !== false) {
        $imageType = 'quantum';
    } elseif (strpos($catId, 'ai') !== false || strpos($catId, 'generative') !== false || strpos($catId, 'llm') !== false ||
              strpos($catId, 'nlp') !== false || strpos($catId, 'robot') !== false || strpos($catId, 'multimodal') !== false) {
        $imageType = 'ai';
    } elseif (strpos($catId, 'security') !== false || strpos($catId, 'cyber') !== false) {
        $imageType = 'security';
    } elseif (strpos($catId, 'cloud') !== false) {
        $imageType = 'cloud';
    } elseif (strpos($catId, 'blockchain') !== false) {
        $imageType = 'blockchain';
    } elseif (strpos($catId, 'spatial') !== false) {
        $imageType = 'spatial';
    }

    $data['featuredImage'] = getUniqueImage($imageType);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $updatedImages++;
}

echo "Images mises à jour : {$updatedImages}\n";

echo "\n=== RÉSUMÉ ===\n";
echo "Nouveaux articles créés : {$totalArticles}\n";
echo "Images mises à jour : {$updatedImages}\n";
echo "\nPour régénérer le blog statique :\n";
echo "  ./bin/console blog:regenerate\n";
