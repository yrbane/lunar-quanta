# Sécurité - Lunar Quanta

## Vue d'ensemble

Lunar Quanta implémente une architecture de sécurité en couches, sans dépendance externe. Chaque décision est guidée par un principe : **ne jamais faire confiance aux données entrantes**, qu'elles viennent de l'utilisateur, du filesystem ou d'un provider OAuth.

```
┌─────────────────────────────────────────────────────────────────┐
│                  COUCHES DE SÉCURITÉ                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Couche 1 : Chiffrement (EncryptionService)               │  │
│  │  AES-256-CBC + HMAC-SHA256 (encrypt-then-MAC)             │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Couche 2 : Stockage (JsonStorage / FileStorage)          │  │
│  │  Chiffrement au repos + protection path traversal         │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Couche 3 : Authentification (OAuth, Password Reset)      │  │
│  │  SSL forcé, tokens hashés, timing constant                │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Couche 4 : Contenu (HtmlSanitizer)                       │  │
│  │  Protection XSS, tags/attributs autorisés                 │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## APP_KEY : la clé maîtresse

### Pourquoi une clé d'environnement ?

La variable `APP_KEY` est le secret partagé qui protège toutes les données chiffrées (utilisateurs, tokens). Elle est **obligatoire** : sans elle, l'application refuse de démarrer.

```
┌──────────────┐     getenv()     ┌──────────────┐
│  Environnement│ ──────────────→ │  JsonStorage  │
│  APP_KEY=...  │                 │               │
└──────────────┘                  │  si absent :  │
                                  │  RuntimeException
                                  └──────┬───────┘
                                         │
                                         ▼
                                  ┌──────────────────┐
                                  │EncryptionService  │
                                  │  (AES-256-CBC)    │
                                  └──────────────────┘
```

**Pourquoi ne pas la mettre dans un fichier de config ?** Parce qu'un fichier commité dans Git serait exposé à tous les contributeurs et dans l'historique. Une variable d'environnement reste sur la machine cible et n'entre jamais dans le dépôt.

### Génération

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Cette commande produit 64 caractères hexadécimaux (256 bits d'entropie). On utilise `random_bytes()` car c'est un CSPRNG (Cryptographically Secure Pseudo-Random Number Generator) fourni par le système d'exploitation.

### Configuration

```bash
# .env (jamais commité, ajouté à .gitignore)
export APP_KEY="a1b2c3d4e5f6...64_caractères_hex"
```

### Vérification dans JsonStorage

```php
// src/Service/Storage/JsonStorage.php
public function __construct()
{
    $this->dataPath = getenv('DATA_PATH') ?: __DIR__.'/../../../data';
    $appKey = getenv('APP_KEY');
    if (!$appKey) {
        throw new \RuntimeException(
            'APP_KEY environment variable is required for encryption. '
            . 'Generate one with: php -r "echo bin2hex(random_bytes(32));"'
        );
    }
    $this->encryptionService = new EncryptionService($appKey);
}
```

**Pourquoi échouer bruyamment ?** Un système qui fonctionne sans chiffrement donnerait un faux sentiment de sécurité. En lançant une `RuntimeException`, on force le développeur à configurer correctement son environnement avant tout déploiement.

## EncryptionService : chiffrement authentifié

### Le pattern Encrypt-then-MAC

L'`EncryptionService` utilise le pattern **encrypt-then-MAC** : on chiffre d'abord les données, puis on calcule un HMAC sur le résultat chiffré.

```
              CHIFFREMENT (encrypt)
              ═══════════════════

APP_KEY (hex)
    │
    ▼
┌─────────────────────────────────┐
│  hash('sha512', $key, true)     │  ← Dérivation de clés
│  = 64 octets binaires           │
├────────────────┬────────────────┤
│  32 octets     │  32 octets     │
│  encryptionKey │  hmacKey       │
└───────┬────────┴───────┬────────┘
        │                │
        ▼                │
┌──────────────────┐     │
│ random_bytes(16) │     │         ← IV aléatoire (CSPRNG)
│ = IV             │     │
└───────┬──────────┘     │
        │                │
        ▼                │
┌──────────────────┐     │
│ AES-256-CBC      │     │
│ encrypt(         │     │
│   plaintext,     │     │
│   encryptionKey, │     │
│   IV             │     │
│ ) = ciphertext   │     │
└───────┬──────────┘     │
        │                │
        ▼                ▼
┌──────────────────────────────┐
│ HMAC-SHA256(                 │
│   IV + ciphertext,          │
│   hmacKey                   │
│ ) = 32 octets               │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ base64(IV + ciphertext + HMAC)│  ← Format de sortie
└──────────────────────────────┘
```

### Pourquoi SHA-512 pour dériver deux clés ?

```php
$derivedKey = hash('sha512', $key, true);        // 64 octets
$this->encryptionKey = substr($derivedKey, 0, 32); // Première moitié
$this->hmacKey = substr($derivedKey, 32, 32);      // Seconde moitié
```

**Problème résolu** : utiliser la même clé pour le chiffrement et le HMAC serait dangereux. Si une vulnérabilité dans AES-CBC fuite des informations sur la clé, le HMAC serait compromis aussi. En dérivant deux clés indépendantes depuis une source unique, on isole les deux opérations cryptographiques.

**Pourquoi SHA-512 ?** Il produit naturellement 64 octets, soit exactement 2 x 32 octets. Pas besoin de KDF complexe comme HKDF pour ce cas d'usage.

### Pourquoi random_bytes() et pas openssl_random_pseudo_bytes() ?

```php
$iv = random_bytes($ivLength);
```

`random_bytes()` (PHP 7+) est garanti CSPRNG par le langage. Il utilise `/dev/urandom` sur Linux ou `CryptGenRandom` sur Windows. À l'inverse, `openssl_random_pseudo_bytes()` a historiquement souffert de bugs où le paramètre `$strong` pouvait retourner `false` sans que les développeurs le vérifient, produisant des IV prévisibles.

### Vérification HMAC en premier (constant-time)

```php
// src/Service/Security/EncryptionService.php - decrypt()

// Vérifier le HMAC AVANT de tenter le déchiffrement
$expectedHmac = hash_hmac($this->hmacAlgo, $iv.$encryptedData, $this->hmacKey, true);
if (!hash_equals($expectedHmac, $hmac)) {
    throw new SecurityException(
        'HMAC verification failed: data may have been tampered with'
    );
}
```

**Deux protections critiques ici :**

1. **Vérifier avant de déchiffrer** : si on déchiffrait d'abord, un attaquant pourrait exploiter des oracles de padding (attaque Padding Oracle) pour déchiffrer sans connaître la clé.

2. **`hash_equals()` pour la comparaison** : une comparaison classique (`===`) s'arrête au premier octet différent. Le temps d'exécution révèle alors combien d'octets sont corrects (attaque timing). `hash_equals()` compare en temps constant, quelle que soit la position de la différence.

```
  Comparaison classique (===)          hash_equals()
  ════════════════════════             ═══════════════

  Attendu:  a1 b2 c3 d4              Attendu:  a1 b2 c3 d4
  Reçu:     a1 b2 XX d4              Reçu:     a1 b2 XX d4
                   ↑                  Compare TOUS les octets
            Stop ! 3e octet           puis retourne le résultat
            → temps court             → même temps toujours
```

### Format des données chiffrées

```
base64(IV + ciphertext + HMAC)

┌────────┬──────────────────────┬──────────────────┐
│ IV     │ Ciphertext           │ HMAC-SHA256      │
│ 16 oct │ variable             │ 32 octets        │
└────────┴──────────────────────┴──────────────────┘

Longueur minimale : 16 (IV) + 0 (données vides) + 32 (HMAC) = 48 octets
```

Le déchiffrement extrait les composants par position :

```php
$iv            = substr($data, 0, $ivLength);        // Début
$hmac          = substr($data, -$hmacLength);         // Fin
$encryptedData = substr($data, $ivLength, -$hmacLength); // Milieu
```

## Protection contre le Path Traversal

### Le problème

Sans protection, un ID comme `../../etc/passwd` pourrait amener le système à lire ou écrire des fichiers arbitraires :

```
Entrée malveillante : "../../etc/passwd"
Chemin résultant    : data/blog/posts/../../etc/passwd.json
Chemin réel         : /etc/passwd.json  ← DANGER !
```

### La solution : sanitization par regex

**FileStorage** et **PasswordResetService** appliquent la même stratégie : nettoyer l'ID pour ne conserver que les caractères sûrs.

```php
// src/Service/Storage/FileStorage.php
private function getPath(string $id): string
{
    // Nettoyer l'ID pour éviter les traversées de répertoire
    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);

    if ($safeId === '') {
        throw new \InvalidArgumentException('Invalid storage ID');
    }

    return $this->basePath . '/' . $safeId . '.json';
}
```

```php
// src/Service/Security/Auth/PasswordResetService.php
private function getTokenPath(string $id): string
{
    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);

    return $this->getTokensPath() . '/' . $safeId . '.json';
}
```

**Pourquoi un regex plutôt qu'un `basename()` ?** `basename()` ne supprime que les séparateurs de chemin (`/`, `\`). Un ID contenant un null byte (`%00`) ou des caractères spéciaux pourrait contourner cette protection sur certains systèmes de fichiers. Le regex whitelist (`[a-zA-Z0-9_-]`) est plus strict : seuls les caractères explicitement autorisés passent.

### Validation d'ID vide

```php
if ($safeId === '') {
    throw new \InvalidArgumentException('Invalid storage ID');
}
```

**Pourquoi ?** Si un attaquant envoie un ID composé uniquement de caractères spéciaux (par exemple `../..`), le regex les supprime tous. Sans cette vérification, on écrirait dans `data/blog/posts/.json`, un fichier caché qui pourrait ne pas apparaître dans les listings et corrompre silencieusement le stockage.

```
  Entrée           Après regex     Résultat
  ═══════          ═══════════     ════════
  "post-123"       "post-123"     ✓ data/posts/post-123.json
  "../../etc"      "etc"          ✓ data/posts/etc.json (inoffensif)
  "../../../"      ""             ✗ InvalidArgumentException
  "post 123"       "post123"      ✓ data/posts/post123.json
```

## OAuth : sécurité réseau

### SSL strict

Les providers OAuth (GitHub, Google) communiquent via HTTPS. Lunar Quanta force la vérification SSL sans exception :

```php
// src/Service/Security/OAuth/AbstractOAuthProvider.php
$options = [
    'ssl' => [
        'verify_peer'       => true,   // Vérifie le certificat du serveur
        'verify_peer_name'  => true,   // Vérifie que le nom correspond au certificat
        'allow_self_signed' => false,  // Refuse les certificats auto-signés
    ],
    'http' => [
        'timeout' => 10,               // Timeout de 10 secondes
    ],
];
```

**Pourquoi ces trois options ensemble ?**

```
┌─────────────────────────────────────────────────────────────────┐
│                  VÉRIFICATIONS SSL                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  verify_peer = true                                             │
│  ─────────────────                                              │
│  Vérifie que le certificat est signé par une CA de confiance.   │
│  Sans ça, un attaquant MITM peut présenter n'importe quel      │
│  certificat et intercepter les tokens OAuth.                    │
│                                                                 │
│  verify_peer_name = true                                        │
│  ──────────────────────                                         │
│  Vérifie que le CN/SAN du certificat correspond au hostname.    │
│  Sans ça, un certificat valide pour evil.com pourrait être      │
│  utilisé pour se faire passer pour github.com.                  │
│                                                                 │
│  allow_self_signed = false                                      │
│  ─────────────────────────                                      │
│  Refuse les certificats auto-signés même en développement.      │
│  Un certificat auto-signé ne prouve rien : n'importe qui        │
│  peut en créer un. Forcer de vrais certificats garantit          │
│  qu'on parle bien au bon serveur.                               │
│                                                                 │
│  timeout = 10                                                   │
│  ────────────                                                   │
│  Empêche les requêtes de bloquer indéfiniment. Un serveur       │
│  malveillant ou lent ne doit pas pouvoir geler l'application.   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Flux OAuth sécurisé

```
  Utilisateur              Lunar Quanta           Provider OAuth
  ════════════             ════════════           ══════════════

  1. Clic "Login"
       │
       ▼
       ├─────────────────→ Génère state random
       │                   Redirige vers provider
       │                        │
       │                        ├─────────────→ Auth page
       │                        │
  2. Autorise                   │              ← Code + State
       │                        │
       │                   Vérifie state (CSRF)
       │                        │
       │                        ├─────────────→ POST /token
       │                        │               (SSL vérifié)
       │                        │
       │                        │←────────────── Access Token
       │                        │
       │                        ├─────────────→ GET /userinfo
       │                        │               (SSL vérifié)
       │                        │
       │                        │←────────────── User data
       │                        │
       ←─────────────────── Session créée
  3. Connecté !
```

## Tokens de réinitialisation de mot de passe

Le `PasswordResetService` implémente plusieurs mesures de sécurité complémentaires :

```
┌─────────────────────────────────────────────────────────────────┐
│             SÉCURITÉ DES TOKENS DE RESET                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. TOKEN HASHÉ (jamais stocké en clair)                        │
│     → Si la base fuite, les tokens sont inutilisables           │
│                                                                 │
│  2. EXPIRATION (1 heure par défaut)                             │
│     → Fenêtre d'attaque limitée                                 │
│                                                                 │
│  3. USAGE UNIQUE (invalidé après utilisation)                   │
│     → Pas de réutilisation possible                             │
│                                                                 │
│  4. INVALIDATION DES ANCIENS TOKENS                             │
│     → Une nouvelle demande annule les précédentes               │
│                                                                 │
│  5. PATH TRAVERSAL PROTECTION                                   │
│     → ID du token nettoyé par regex avant écriture              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Cycle de vie d'un token

```php
// 1. Création : génère un token sécurisé
$plainToken = $resetService->createToken($email);
// → Le token en clair est envoyé par email
// → Seul le hash est stocké sur le serveur

// 2. Validation : vérifie le hash, pas le token brut
$isValid = $resetService->isTokenValid($email, $plainToken);

// 3. Utilisation : reset le mot de passe et invalide le token
$success = $resetService->resetPassword($email, $plainToken, $newPassword);

// 4. Nettoyage : supprime les tokens expirés
$deleted = $resetService->cleanExpiredTokens();
```

## Stockage : deux stratégies selon la sensibilité

Lunar Quanta distingue clairement les données sensibles des données publiques :

```
┌─────────────────────────────────────────────────────────────────┐
│                     STRATÉGIE DE STOCKAGE                        │
├──────────────────────────┬──────────────────────────────────────┤
│                          │                                      │
│  Données SENSIBLES       │  Données PUBLIQUES                   │
│  (JsonStorage)           │  (FileStorage)                       │
│                          │                                      │
│  ✓ Chiffrement AES-256   │  ✗ Pas de chiffrement               │
│  ✓ APP_KEY obligatoire   │  ✓ Accès direct JSON                │
│  ✓ Utilisateurs          │  ✓ Articles de blog                 │
│  ✓ Tokens de reset       │  ✓ Catégories, tags                 │
│                          │                                      │
│  Fichier sur disque :    │  Fichier sur disque :                │
│  base64(IV+cipher+HMAC) │  {"title": "...", ...}               │
│                          │                                      │
│  COMMUN : protection path traversal par regex                   │
│                          │                                      │
└──────────────────────────┴──────────────────────────────────────┘
```

**Pourquoi ne pas tout chiffrer ?** Les articles de blog sont des données publiques destinées à être transformées en HTML statique. Les chiffrer ajouterait de la complexité et du temps de traitement sans bénéfice de sécurité. En revanche, les données utilisateur (email, mot de passe hashé, rôles) doivent être protégées au repos.

## Résumé des décisions de sécurité

| Décision | Raison |
|----------|--------|
| `random_bytes()` pour l'IV | CSPRNG garanti, pas d'ambiguïté comme `openssl_random_pseudo_bytes` |
| Encrypt-then-MAC | Protège contre les attaques Padding Oracle |
| SHA-512 split en 2 clés | Isolation entre chiffrement et HMAC |
| `hash_equals()` | Empêche les attaques timing sur la comparaison HMAC |
| HMAC vérifié avant déchiffrement | Pas de déchiffrement de données altérées |
| Regex whitelist `[a-zA-Z0-9_-]` | Protection path traversal stricte |
| Validation ID vide | Empêche l'écriture à des chemins invalides |
| SSL `verify_peer` + `verify_peer_name` | Empêche les attaques MITM sur OAuth |
| `allow_self_signed = false` | Pas d'exception, même en développement |
| Timeout 10s sur OAuth | Empêche les blocages par serveur malveillant |
| `RuntimeException` si `APP_KEY` absent | Fail-fast, pas de fonctionnement dégradé |
| Tokens hashés, expirables, usage unique | Défense en profondeur pour le password reset |
