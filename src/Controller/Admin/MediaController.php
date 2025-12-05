<?php

declare(strict_types=1);

namespace Lunar\Controller\Admin;

use Lunar\Attribute\Route;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Media\DalleClient;
use Lunar\Service\Media\ImagenClient;
use Lunar\Service\Media\ImageOptimizer;
use Lunar\Service\Media\ImageResult;
use Lunar\Service\Media\ImageService;
use Lunar\Service\Media\PexelsClient;

/**
 * Contrôleur d'administration des médias.
 *
 * Gère la galerie de médias, la recherche d'images et l'upload :
 * - Galerie des images uploadées
 * - Recherche via Pexels
 * - Génération via DALL-E / Imagen
 * - Upload et optimisation
 */
#[Route('/admin/media')]
class MediaController extends BaseController
{
    private ImageService $imageService;
    private string $uploadDir;

    public function __construct()
    {
        parent::__construct();

        $basePath = dirname(__DIR__, 3);
        $this->uploadDir = $basePath . '/public/uploads/media';

        $optimizer = new ImageOptimizer($this->uploadDir);
        $this->imageService = new ImageService($optimizer);

        // Charger les providers depuis les variables d'environnement
        $this->configureProviders();
    }

    /**
     * Configure les fournisseurs d'images.
     */
    private function configureProviders(): void
    {
        // Pexels
        $pexelsKey = $_ENV['PEXELS_API_KEY'] ?? '';
        if (!empty($pexelsKey)) {
            $this->imageService->addProvider(new PexelsClient($pexelsKey));
        }

        // DALL-E
        $openaiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        if (!empty($openaiKey)) {
            $this->imageService->addProvider(new DalleClient($openaiKey));
        }

        // Imagen
        $gcpKey = $_ENV['GCP_API_KEY'] ?? '';
        $gcpProject = $_ENV['GCP_PROJECT_ID'] ?? '';
        if (!empty($gcpKey) && !empty($gcpProject)) {
            $this->imageService->addProvider(new ImagenClient($gcpKey, $gcpProject));
        }
    }

    /**
     * Galerie des médias.
     */
    #[Route('', methods: ['GET'], name: 'admin.media.index')]
    public function index(Request $request): Response
    {
        $images = $this->getUploadedImages();

        $flash = null;
        if (isset($request->getQueryParams()['uploaded'])) {
            $flash = ['type' => 'success', 'message' => 'Image uploadée avec succès !'];
        }
        if (isset($request->getQueryParams()['deleted'])) {
            $flash = ['type' => 'success', 'message' => 'Image supprimée.'];
        }

        return $this->renderAdmin('admin/media/index', [
            'title' => 'Galerie Médias',
            'images' => $images,
            'providers' => $this->imageService->getProviders(),
            'generativeProviders' => $this->imageService->getGenerativeProviders(),
            'flash' => $flash,
        ]);
    }

    /**
     * Upload d'image.
     */
    #[Route('/upload', methods: ['POST'], name: 'admin.media.upload')]
    public function upload(Request $request): Response
    {
        $files = $request->getUploadedFiles();

        if (!isset($files['image']) || $files['image']['error'] !== UPLOAD_ERR_OK) {
            return $this->jsonError('Erreur lors de l\'upload');
        }

        $file = $files['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file['type'], $allowedTypes)) {
            return $this->jsonError('Type de fichier non autorisé');
        }

        // Limite à 10 Mo
        if ($file['size'] > 10 * 1024 * 1024) {
            return $this->jsonError('Fichier trop volumineux (max 10 Mo)');
        }

        $imageData = file_get_contents($file['tmp_name']);
        $filename = $this->sanitizeFilename($file['name']);

        $result = $this->imageService->upload($imageData, $filename);

        if ($result === null) {
            return $this->jsonError('Erreur lors du traitement de l\'image');
        }

        return $this->json([
            'success' => true,
            'image' => $result,
        ]);
    }

    /**
     * Recherche d'images via les providers.
     */
    #[Route('/search', methods: ['GET'], name: 'admin.media.search')]
    public function search(Request $request): Response
    {
        $query = $request->getQueryParams()['q'] ?? '';
        $provider = $request->getQueryParams()['provider'] ?? '';
        $limit = (int) ($request->getQueryParams()['limit'] ?? 20);

        if (empty($query)) {
            return $this->json(['results' => []]);
        }

        $limit = min(max($limit, 1), 50);

        if (!empty($provider)) {
            $results = $this->imageService->searchProvider($provider, $query, $limit);
        } else {
            $results = $this->imageService->search($query, $limit);
        }

        return $this->json([
            'results' => array_map(fn(ImageResult $r) => $r->toArray(), $results),
        ]);
    }

    /**
     * Génération d'image via IA.
     */
    #[Route('/generate', methods: ['POST'], name: 'admin.media.generate')]
    public function generate(Request $request): Response
    {
        $body = $request->getParsedBody();
        $prompt = $body['prompt'] ?? '';
        $provider = $body['provider'] ?? 'dalle';

        if (empty($prompt)) {
            return $this->jsonError('Le prompt est requis');
        }

        if (!in_array($provider, $this->imageService->getGenerativeProviders())) {
            return $this->jsonError('Fournisseur de génération non disponible');
        }

        $result = $this->imageService->generate($prompt, $provider);

        if ($result === null) {
            return $this->jsonError('Erreur lors de la génération');
        }

        return $this->json([
            'success' => true,
            'image' => $result->toArray(),
        ]);
    }

    /**
     * Téléchargement d'une image externe.
     */
    #[Route('/download', methods: ['POST'], name: 'admin.media.download')]
    public function download(Request $request): Response
    {
        $body = $request->getParsedBody();
        $url = $body['url'] ?? '';

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->jsonError('URL invalide');
        }

        $result = $this->imageService->downloadFromUrl($url);

        if ($result === null) {
            return $this->jsonError('Erreur lors du téléchargement');
        }

        return $this->json([
            'success' => true,
            'image' => $result,
        ]);
    }

    /**
     * Suppression d'une image.
     */
    #[Route('/delete', methods: ['POST'], name: 'admin.media.delete')]
    public function delete(Request $request): Response
    {
        $body = $request->getParsedBody();
        $path = $body['path'] ?? '';

        if (empty($path)) {
            return $this->jsonError('Chemin requis');
        }

        // Sécurité : vérifier que le fichier est dans le dossier uploads
        $realPath = realpath($this->uploadDir . '/' . basename($path));
        if ($realPath === false || !str_starts_with($realPath, realpath($this->uploadDir))) {
            return $this->jsonError('Accès non autorisé');
        }

        $success = $this->imageService->delete($realPath);

        if (!$success) {
            return $this->jsonError('Erreur lors de la suppression');
        }

        return $this->json(['success' => true]);
    }

    /**
     * Détails d'une image (modal).
     */
    #[Route('/{filename}', methods: ['GET'], name: 'admin.media.show')]
    public function show(Request $request, string $filename): Response
    {
        $path = $this->uploadDir . '/' . $filename;

        if (!file_exists($path)) {
            return $this->notFound('Image non trouvée');
        }

        $info = getimagesize($path);
        $stat = stat($path);

        return $this->json([
            'filename' => $filename,
            'url' => '/uploads/media/' . $filename,
            'width' => $info[0] ?? 0,
            'height' => $info[1] ?? 0,
            'size' => $stat['size'] ?? 0,
            'type' => $info['mime'] ?? 'unknown',
            'modified' => date('Y-m-d H:i:s', $stat['mtime'] ?? time()),
        ]);
    }

    /**
     * Récupère la liste des images uploadées.
     *
     * @return array<array{filename: string, url: string, thumb: string, size: int, modified: int}>
     */
    private function getUploadedImages(): array
    {
        if (!is_dir($this->uploadDir)) {
            return [];
        }

        $images = [];
        $files = scandir($this->uploadDir);
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Ignorer les thumbnails
            if (str_contains($file, '_thumb.')) {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                continue;
            }

            $path = $this->uploadDir . '/' . $file;
            $stat = stat($path);

            // Chercher le thumbnail
            $thumbFile = pathinfo($file, PATHINFO_FILENAME) . '_thumb.' . $ext;
            $thumbUrl = file_exists($this->uploadDir . '/' . $thumbFile)
                ? '/uploads/media/' . $thumbFile
                : '/uploads/media/' . $file;

            $images[] = [
                'filename' => $file,
                'url' => '/uploads/media/' . $file,
                'thumb' => $thumbUrl,
                'size' => $stat['size'] ?? 0,
                'modified' => $stat['mtime'] ?? time(),
            ];
        }

        // Trier par date de modification décroissante
        usort($images, fn($a, $b) => $b['modified'] <=> $a['modified']);

        return $images;
    }

    /**
     * Nettoie un nom de fichier.
     */
    private function sanitizeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Supprimer les caractères spéciaux
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');

        if (empty($name)) {
            $name = 'image_' . uniqid();
        }

        return $name . '.' . $ext;
    }

    /**
     * Rendu avec le layout admin.
     */
    private function renderAdmin(string $template, array $data = []): Response
    {
        $html = $this->render($template, $data);
        return new Response($html);
    }

    /**
     * Réponse JSON.
     */
    private function json(array $data): Response
    {
        return new Response(
            json_encode($data, JSON_UNESCAPED_UNICODE),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Réponse JSON d'erreur.
     */
    private function jsonError(string $message, int $status = 400): Response
    {
        return new Response(
            json_encode(['error' => $message], JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Page 404.
     */
    private function notFound(string $message): Response
    {
        return new Response(
            json_encode(['error' => $message], JSON_UNESCAPED_UNICODE),
            404,
            ['Content-Type' => 'application/json']
        );
    }
}
