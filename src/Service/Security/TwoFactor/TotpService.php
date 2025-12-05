<?php
/**
 * Lunar Quanta Framework - Service TOTP (Time-based One-Time Password).
 *
 * =============================================================================
 * QU'EST-CE QUE LE 2FA / TOTP ?
 * =============================================================================
 *
 * L'authentification à deux facteurs (2FA) ajoute une couche de sécurité :
 *
 * ```
 * AUTHENTIFICATION SIMPLE              AUTHENTIFICATION 2FA
 * ─────────────────────                ────────────────────
 *
 *     Ce que vous SAVEZ                    Ce que vous SAVEZ
 *           │                                    │
 *           ▼                                    ▼
 *     ┌───────────┐                        ┌───────────┐
 *     │ Password  │                        │ Password  │
 *     └─────┬─────┘                        └─────┬─────┘
 *           │                                    │
 *           ▼                                    ▼
 *        ACCÈS !                           Ce que vous AVEZ
 *                                                │
 *                                                ▼
 *                                          ┌───────────┐
 *                                          │ Code TOTP │
 *                                          │ (téléphone)│
 *                                          └─────┬─────┘
 *                                                │
 *                                                ▼
 *                                             ACCÈS !
 * ```
 *
 * =============================================================================
 * COMMENT FONCTIONNE TOTP ?
 * =============================================================================
 *
 * TOTP génère des codes temporaires basés sur :
 * 1. Un SECRET partagé (stocké côté serveur et dans l'app)
 * 2. Le TEMPS actuel (divisé en périodes de 30 secondes)
 *
 * ```
 * SECRET + TEMPS ──► HMAC-SHA1 ──► CODE À 6 CHIFFRES
 *
 *    "JBSWY3DPEHPK3PXP"
 *           +              ──►  "123456"
 *    1699999980 (timestamp)
 *
 * Le code change toutes les 30 secondes !
 * ```
 *
 * =============================================================================
 * FLUX D'ACTIVATION DU 2FA
 * =============================================================================
 *
 * ```
 * 1. L'utilisateur demande à activer le 2FA
 *        │
 *        ▼
 * 2. Le serveur génère un SECRET aléatoire
 *        │
 *        ▼
 * 3. Le serveur affiche un QR Code
 *    (contient le secret encodé)
 *        │
 *        ▼
 * 4. L'utilisateur scanne avec Google Authenticator
 *        │
 *        ▼
 * 5. L'utilisateur entre le code affiché
 *        │
 *        ▼
 * 6. Le serveur vérifie le code
 *        │
 *        ▼
 * 7. Si OK → 2FA activé !
 * ```
 *
 * @package    Lunar\Service\Security\TwoFactor
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Security\TwoFactor;

/**
 * Service TOTP pour l'authentification à deux facteurs.
 *
 * Implémente RFC 6238 (TOTP) et RFC 4226 (HOTP).
 */
class TotpService
{
    /**
     * Durée de validité d'un code en secondes.
     */
    private const TIME_STEP = 30;

    /**
     * Nombre de chiffres du code.
     */
    private const CODE_LENGTH = 6;

    /**
     * Nombre de périodes à vérifier (avant/après).
     * Permet de compenser les décalages d'horloge.
     */
    private const TIME_WINDOW = 1;

    /**
     * Nom de l'application pour les QR codes.
     */
    private string $issuer;

    public function __construct(string $issuer = 'Lunar Quanta')
    {
        $this->issuer = $issuer;
    }

    // =========================================================================
    // GÉNÉRATION DE SECRET
    // =========================================================================

    /**
     * Génère un secret aléatoire en Base32.
     *
     * Le secret est encodé en Base32 car c'est le format attendu
     * par les applications d'authentification (Google Authenticator, etc.).
     *
     * @param int $length Longueur en octets (16 = 128 bits recommandé)
     *
     * @return string Le secret en Base32
     */
    public function generateSecret(int $length = 16): string
    {
        $random = random_bytes($length);

        return $this->base32Encode($random);
    }

    // =========================================================================
    // GÉNÉRATION ET VÉRIFICATION DE CODE
    // =========================================================================

    /**
     * Génère le code TOTP actuel pour un secret.
     *
     * @param string   $secret    Le secret en Base32
     * @param int|null $timestamp Timestamp Unix (null = maintenant)
     *
     * @return string Le code à 6 chiffres
     */
    public function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = (int) floor($timestamp / self::TIME_STEP);

        return $this->hotp($secret, $counter);
    }

    /**
     * Vérifie si un code est valide pour un secret.
     *
     * Vérifie le code actuel et les codes adjacents (fenêtre de temps)
     * pour compenser les légères différences d'horloge.
     *
     * @param string $secret Le secret en Base32
     * @param string $code   Le code saisi par l'utilisateur
     *
     * @return bool true si le code est valide
     */
    public function verifyCode(string $secret, string $code): bool
    {
        $timestamp = time();
        $counter = (int) floor($timestamp / self::TIME_STEP);

        // Vérifie les codes dans la fenêtre de temps
        for ($i = -self::TIME_WINDOW; $i <= self::TIME_WINDOW; $i++) {
            $expectedCode = $this->hotp($secret, $counter + $i);
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // QR CODE
    // =========================================================================

    /**
     * Génère l'URI otpauth pour les QR codes.
     *
     * Format : otpauth://totp/Issuer:user@example.com?secret=XXX&issuer=Issuer
     *
     * @param string $secret     Le secret en Base32
     * @param string $identifier L'identifiant de l'utilisateur (email)
     *
     * @return string L'URI otpauth
     */
    public function getOtpAuthUri(string $secret, string $identifier): string
    {
        $label = rawurlencode($this->issuer . ':' . $identifier);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $this->issuer,
            'algorithm' => 'SHA1',
            'digits' => self::CODE_LENGTH,
            'period' => self::TIME_STEP,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * Génère l'URL d'un QR code via une API externe.
     *
     * Utilise l'API Google Charts pour générer le QR code.
     * En production, préférez une génération locale.
     *
     * @param string $secret     Le secret en Base32
     * @param string $identifier L'identifiant de l'utilisateur
     * @param int    $size       Taille du QR code en pixels
     *
     * @return string L'URL de l'image QR code
     */
    public function getQrCodeUrl(string $secret, string $identifier, int $size = 200): string
    {
        $otpAuthUri = $this->getOtpAuthUri($secret, $identifier);

        return 'https://chart.googleapis.com/chart?'
            . http_build_query([
                'chs' => "{$size}x{$size}",
                'chld' => 'M|0',
                'cht' => 'qr',
                'chl' => $otpAuthUri,
            ]);
    }

    /**
     * Génère le QR code en SVG (sans dépendance externe).
     *
     * @param string $secret     Le secret en Base32
     * @param string $identifier L'identifiant de l'utilisateur
     *
     * @return string Le code SVG du QR code
     */
    public function getQrCodeSvg(string $secret, string $identifier): string
    {
        $uri = $this->getOtpAuthUri($secret, $identifier);
        $data = $this->generateQrMatrix($uri);

        return $this->renderQrSvg($data);
    }

    // =========================================================================
    // ALGORITHME HOTP (RFC 4226)
    // =========================================================================

    /**
     * Génère un code HOTP (HMAC-based One-Time Password).
     *
     * @param string $secret  Le secret en Base32
     * @param int    $counter Le compteur
     *
     * @return string Le code à 6 chiffres
     */
    private function hotp(string $secret, int $counter): string
    {
        // Décode le secret Base32
        $key = $this->base32Decode($secret);

        // Encode le compteur en 8 octets (big-endian)
        $counterBytes = pack('J', $counter);

        // Calcule HMAC-SHA1
        $hash = hash_hmac('sha1', $counterBytes, $key, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord($hash[19]) & 0x0f;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % (10 ** self::CODE_LENGTH);

        return str_pad((string) $code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // ENCODAGE BASE32
    // =========================================================================

    /**
     * Encode des données en Base32.
     *
     * @param string $data Les données binaires
     *
     * @return string La chaîne Base32
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';

        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $result = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $result .= $alphabet[bindec($chunk)];
        }

        return $result;
    }

    /**
     * Décode une chaîne Base32.
     *
     * @param string $data La chaîne Base32
     *
     * @return string Les données binaires
     */
    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $binary = '';

        foreach (str_split($data) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $result = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $result .= chr(bindec($chunk));
            }
        }

        return $result;
    }

    // =========================================================================
    // GÉNÉRATION QR CODE (SIMPLE)
    // =========================================================================

    /**
     * Génère une matrice QR code simple.
     *
     * Note: Implémentation basique. Pour la production,
     * utilisez une bibliothèque dédiée comme endroid/qr-code.
     *
     * @param string $data Les données à encoder
     *
     * @return array<array<int>> La matrice (1 = noir, 0 = blanc)
     */
    private function generateQrMatrix(string $data): array
    {
        // Implémentation simplifiée - retourne une matrice placeholder
        // En production, utilisez une vraie lib QR code
        $size = 25;
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));

        // Patterns de positionnement (coins)
        $this->addPositionPattern($matrix, 0, 0);
        $this->addPositionPattern($matrix, $size - 7, 0);
        $this->addPositionPattern($matrix, 0, $size - 7);

        // Données (pattern pseudo-aléatoire basé sur les données)
        $hash = md5($data);
        for ($y = 8; $y < $size - 8; $y++) {
            for ($x = 8; $x < $size - 8; $x++) {
                $idx = (($y - 8) * ($size - 16) + ($x - 8)) % 32;
                $matrix[$y][$x] = hexdec($hash[$idx]) > 7 ? 1 : 0;
            }
        }

        return $matrix;
    }

    /**
     * Ajoute un pattern de positionnement.
     *
     * @param array<array<int>> $matrix La matrice
     * @param int               $startX Position X
     * @param int               $startY Position Y
     */
    private function addPositionPattern(array &$matrix, int $startX, int $startY): void
    {
        $pattern = [
            [1, 1, 1, 1, 1, 1, 1],
            [1, 0, 0, 0, 0, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 0, 0, 0, 0, 1],
            [1, 1, 1, 1, 1, 1, 1],
        ];

        for ($y = 0; $y < 7; $y++) {
            for ($x = 0; $x < 7; $x++) {
                $matrix[$startY + $y][$startX + $x] = $pattern[$y][$x];
            }
        }
    }

    /**
     * Rend la matrice QR en SVG.
     *
     * @param array<array<int>> $matrix La matrice QR
     *
     * @return string Le code SVG
     */
    private function renderQrSvg(array $matrix): string
    {
        $size = count($matrix);
        $cellSize = 10;
        $padding = 20;
        $totalSize = $size * $cellSize + $padding * 2;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" '
            . "width=\"{$totalSize}\" height=\"{$totalSize}\" viewBox=\"0 0 {$totalSize} {$totalSize}\">";
        $svg .= "<rect width=\"{$totalSize}\" height=\"{$totalSize}\" fill=\"white\"/>";

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($matrix[$y][$x] === 1) {
                    $px = $padding + $x * $cellSize;
                    $py = $padding + $y * $cellSize;
                    $svg .= "<rect x=\"{$px}\" y=\"{$py}\" width=\"{$cellSize}\" height=\"{$cellSize}\" fill=\"black\"/>";
                }
            }
        }

        $svg .= '</svg>';

        return $svg;
    }

    // =========================================================================
    // CODES DE RÉCUPÉRATION
    // =========================================================================

    /**
     * Génère des codes de récupération.
     *
     * Ces codes permettent de se connecter si on perd l'accès
     * à l'application d'authentification.
     *
     * @param int $count  Nombre de codes à générer
     * @param int $length Longueur de chaque code
     *
     * @return array<string> Les codes de récupération
     */
    public function generateRecoveryCodes(int $count = 8, int $length = 8): array
    {
        $codes = [];
        $chars = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Sans I, O pour éviter confusion

        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < $length; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            // Format: XXXX-XXXX
            $codes[] = substr($code, 0, 4) . '-' . substr($code, 4, 4);
        }

        return $codes;
    }

    /**
     * Hash un code de récupération pour le stockage.
     *
     * @param string $code Le code en clair
     *
     * @return string Le hash
     */
    public function hashRecoveryCode(string $code): string
    {
        return hash('sha256', strtoupper(str_replace('-', '', $code)));
    }

    /**
     * Vérifie un code de récupération.
     *
     * @param string        $code       Le code saisi
     * @param array<string> $hashedCodes Les codes hashés stockés
     *
     * @return int|false L'index du code si valide, false sinon
     */
    public function verifyRecoveryCode(string $code, array $hashedCodes): int|false
    {
        $hash = $this->hashRecoveryCode($code);

        foreach ($hashedCodes as $index => $hashedCode) {
            if (hash_equals($hashedCode, $hash)) {
                return $index;
            }
        }

        return false;
    }
}
