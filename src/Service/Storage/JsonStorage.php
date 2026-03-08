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
 * Class JsonStorage.
 *
 * Implémente le stockage des entités sous forme de fichiers JSON chiffrés.
 */
class JsonStorage implements StorageInterface
{
    private string $dataPath;
    private EncryptionService $encryptionService;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->dataPath = getenv('DATA_PATH') ?: __DIR__.'/../../../data';
        $appKey = getenv('APP_KEY');
        if (!$appKey) {
            throw new \RuntimeException('APP_KEY environment variable is required for encryption. Generate one with: php -r "echo bin2hex(random_bytes(32));"');
        }
        $this->encryptionService = new EncryptionService($appKey);
    }

    /**
     * Sauvegarde l'utilisateur dans un fichier.
     *
     * Le fichier est nommé par le hash de l'email et stocké dans un dossier
     * déterminé par les 3 premières lettres du hash.
     *
     * @param User $user instance de l'utilisateur
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
