<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace Lunar\Service\Storage;

use Lunar\Entity\User;
use Lunar\Service\Security\EncryptionService;

/**
 * Stockage chiffré des entités utilisateur en fichiers JSON.
 *
 * Chaque utilisateur est stocké dans un fichier JSON chiffré par AES-256-CBC.
 * Le fichier est nommé par le hash SHA-256 de l'email et réparti dans
 * des sous-dossiers basés sur les 3 premiers caractères du hash
 * (sharding pour éviter un répertoire unique avec trop de fichiers).
 *
 * Sécurité : la variable d'environnement APP_KEY est OBLIGATOIRE.
 * Sans elle, le constructeur lance une RuntimeException.
 *
 * @example
 * ```php
 * // Nécessite APP_KEY dans l'environnement
 * putenv('APP_KEY=' . bin2hex(random_bytes(32)));
 *
 * $storage = new JsonStorage();
 * $storage->saveUser($user);   // Chiffré sur disque
 * $user = $storage->loadUser('email@example.com');  // Déchiffré à la lecture
 * ```
 *
 * @see EncryptionService Pour le détail du chiffrement AES-256-CBC + HMAC
 * @see docs/security.md  Pour l'architecture de sécurité complète
 */
class JsonStorage implements StorageInterface
{
    private string $dataPath;
    private EncryptionService $encryptionService;

    /**
     * Initialise le stockage avec la clé de chiffrement APP_KEY.
     *
     * @throws \RuntimeException Si APP_KEY n'est pas définie
     */
    public function __construct()
    {
        $this->dataPath = getenv('DATA_PATH') ?: __DIR__.'/../../../data';

        // APP_KEY est obligatoire — pas de clé par défaut pour éviter
        // qu'un déploiement en production utilise une clé connue
        $appKey = getenv('APP_KEY');
        if (!$appKey) {
            throw new \RuntimeException('APP_KEY environment variable is required for encryption. Generate one with: php -r "echo bin2hex(random_bytes(32));"');
        }
        $this->encryptionService = new EncryptionService($appKey);
    }

    /**
     * Sauvegarde l'utilisateur dans un fichier chiffré.
     *
     * Le chemin est déterminé par le hash SHA-256 de l'email :
     * data/user/{hash[0:3]}/{hash}.json
     *
     * Ce sharding par préfixe distribue les fichiers en ~4096 sous-dossiers,
     * évitant les problèmes de performance avec un seul répertoire massif.
     *
     * @param User $user L'instance utilisateur à persister
     */
    public function saveUser(User $user): void
    {
        $hash = $user->getHash();
        $subDir = substr($hash, 0, 3);
        $dir = $this->dataPath.'/user/'.$subDir;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = $dir.'/'.$hash.'.json';

        $jsonData = json_encode($user->toArray(), JSON_PRETTY_PRINT);
        if (false === $jsonData) {
            throw new \RuntimeException('Erreur lors de l\'encodage JSON');
        }

        $encryptedData = $this->encryptionService->encrypt($jsonData);
        file_put_contents($filePath, $encryptedData);
    }

    /**
     * Charge un utilisateur à partir de l'email.
     *
     * Utilise User::fromArray() pour reconstruire l'utilisateur
     * avec toutes ses propriétés (id, rôles, mot de passe hashé, etc.).
     *
     * @param string $email email de l'utilisateur
     *
     * @return null|User L'instance User ou null si non trouvé
     */
    public function loadUser(string $email): ?User
    {
        $hash = hash('sha256', $email);
        $subDir = substr($hash, 0, 3);
        $filePath = $this->dataPath.'/user/'.$subDir.'/'.$hash.'.json';

        if (!file_exists($filePath)) {
            return null;
        }

        $encryptedData = file_get_contents($filePath);
        if (false === $encryptedData) {
            return null;
        }

        $jsonData = $this->encryptionService->decrypt($encryptedData);

        $data = json_decode($jsonData, true);
        if (!is_array($data)) {
            return null;
        }

        // Vérifie les champs obligatoires
        if (!isset($data['email'], $data['name'], $data['password'])) {
            return null;
        }

        // Utilise fromArray pour une reconstruction complète
        return User::fromArray($data);
    }
}
