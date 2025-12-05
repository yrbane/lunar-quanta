<?php

declare(strict_types=1);

namespace Tests\Service\Api;

use Lunar\Service\Api\ApiResponse;
use Lunar\Service\Api\ApiResponseBuilder;
use Lunar\Service\Core\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour les helpers de réponse API.
 *
 * Les réponses API suivent une structure JSON cohérente :
 * - Succès : {"success": true, "data": ...}
 * - Erreur : {"success": false, "error": {"code": "...", "message": "..."}}
 * - Collection : {"success": true, "data": [...], "meta": {"total": ..., "page": ...}}
 */
final class ApiResponseTest extends TestCase
{
    // =========================================================================
    // Tests des réponses de succès
    // =========================================================================

    public function testSuccessResponseWithData(): void
    {
        $response = ApiResponse::success(['id' => 1, 'name' => 'Test']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertSame(['id' => 1, 'name' => 'Test'], $body['data']);
    }

    public function testSuccessResponseWithCustomStatusCode(): void
    {
        $response = ApiResponse::success(['id' => 1], 201);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testSuccessResponseWithNullData(): void
    {
        $response = ApiResponse::success(null);

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertNull($body['data']);
    }

    // =========================================================================
    // Tests des réponses de création (201 Created)
    // =========================================================================

    public function testCreatedResponse(): void
    {
        $response = ApiResponse::created(['id' => 42, 'name' => 'New Item']);

        $this->assertSame(201, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertSame(42, $body['data']['id']);
    }

    public function testCreatedResponseWithLocation(): void
    {
        $response = ApiResponse::created(['id' => 42], '/api/items/42');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('/api/items/42', $response->getHeaders()['Location']);
    }

    // =========================================================================
    // Tests des réponses sans contenu (204 No Content)
    // =========================================================================

    public function testNoContentResponse(): void
    {
        $response = ApiResponse::noContent();

        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty($response->getBody());
    }

    // =========================================================================
    // Tests des réponses d'erreur
    // =========================================================================

    public function testErrorResponse(): void
    {
        $response = ApiResponse::error('Something went wrong', 'SERVER_ERROR', 500);

        $this->assertSame(500, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertSame('SERVER_ERROR', $body['error']['code']);
        $this->assertSame('Something went wrong', $body['error']['message']);
    }

    public function testBadRequestResponse(): void
    {
        $response = ApiResponse::badRequest('Invalid input');

        $this->assertSame(400, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertSame('BAD_REQUEST', $body['error']['code']);
    }

    public function testUnauthorizedResponse(): void
    {
        $response = ApiResponse::unauthorized('Please login');

        $this->assertSame(401, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertSame('UNAUTHORIZED', $body['error']['code']);
    }

    public function testForbiddenResponse(): void
    {
        $response = ApiResponse::forbidden('Access denied');

        $this->assertSame(403, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertSame('FORBIDDEN', $body['error']['code']);
    }

    public function testNotFoundResponse(): void
    {
        $response = ApiResponse::notFound('Resource not found');

        $this->assertSame(404, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertSame('NOT_FOUND', $body['error']['code']);
    }

    public function testValidationErrorResponse(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email must be valid'],
            'password' => ['Password is too short']
        ];

        $response = ApiResponse::validationError($errors);

        $this->assertSame(422, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertSame('VALIDATION_ERROR', $body['error']['code']);
        $this->assertSame($errors, $body['error']['details']);
    }

    public function testConflictResponse(): void
    {
        $response = ApiResponse::conflict('Resource already exists');

        $this->assertSame(409, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertSame('CONFLICT', $body['error']['code']);
    }

    public function testInternalErrorResponse(): void
    {
        $response = ApiResponse::internalError('An unexpected error occurred');

        $this->assertSame(500, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertSame('INTERNAL_ERROR', $body['error']['code']);
    }

    // =========================================================================
    // Tests des réponses paginées
    // =========================================================================

    public function testPaginatedResponse(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $response = ApiResponse::paginated($items, total: 50, page: 2, perPage: 10);

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertSame($items, $body['data']);
        $this->assertSame(50, $body['meta']['total']);
        $this->assertSame(2, $body['meta']['page']);
        $this->assertSame(10, $body['meta']['per_page']);
        $this->assertSame(5, $body['meta']['total_pages']);
        $this->assertTrue($body['meta']['has_more']);
    }

    public function testPaginatedResponseOnLastPage(): void
    {
        $items = [['id' => 50, 'name' => 'Last Item']];

        $response = ApiResponse::paginated($items, total: 50, page: 5, perPage: 10);

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['meta']['has_more']);
    }

    // =========================================================================
    // Tests du builder fluent
    // =========================================================================

    public function testBuilderWithData(): void
    {
        $response = ApiResponseBuilder::create()
            ->success()
            ->data(['id' => 1])
            ->build();

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertSame(['id' => 1], $body['data']);
    }

    public function testBuilderWithMeta(): void
    {
        $response = ApiResponseBuilder::create()
            ->success()
            ->data([])
            ->meta(['version' => '1.0', 'timestamp' => '2025-01-15'])
            ->build();

        $body = json_decode($response->getBody(), true);
        $this->assertSame('1.0', $body['meta']['version']);
    }

    public function testBuilderWithHeaders(): void
    {
        $response = ApiResponseBuilder::create()
            ->success()
            ->header('X-Request-Id', 'abc123')
            ->header('X-Custom', 'value')
            ->build();

        $this->assertSame('abc123', $response->getHeaders()['X-Request-Id']);
        $this->assertSame('value', $response->getHeaders()['X-Custom']);
    }

    public function testBuilderWithStatusCode(): void
    {
        $response = ApiResponseBuilder::create()
            ->success()
            ->status(202)
            ->build();

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testBuilderErrorResponse(): void
    {
        $response = ApiResponseBuilder::create()
            ->error('NOT_FOUND', 'User not found')
            ->status(404)
            ->build();

        $this->assertSame(404, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertSame('NOT_FOUND', $body['error']['code']);
    }

    // =========================================================================
    // Tests de l'encodage JSON
    // =========================================================================

    public function testJsonEncodingWithUnicode(): void
    {
        $response = ApiResponse::success(['name' => 'Café résumé']);

        $body = json_decode($response->getBody(), true);
        $this->assertSame('Café résumé', $body['data']['name']);

        // Vérifier que les caractères ne sont pas échappés inutilement
        $this->assertStringContainsString('Café', $response->getBody());
    }

    public function testJsonEncodingWithSlashes(): void
    {
        $response = ApiResponse::success(['url' => 'https://example.com/path']);

        // Les slashes ne doivent pas être échappés
        $this->assertStringContainsString('https://example.com/path', $response->getBody());
    }

    public function testJsonEncodingPrettyPrint(): void
    {
        $response = ApiResponse::success(['key' => 'value'], prettyPrint: true);

        // Pretty print ajoute des retours à la ligne
        $this->assertStringContainsString("\n", $response->getBody());
    }

    // =========================================================================
    // Tests des en-têtes
    // =========================================================================

    public function testContentTypeHeaderIsJson(): void
    {
        $response = ApiResponse::success([]);

        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
    }

    public function testCacheControlHeaderCanBeSet(): void
    {
        $response = ApiResponseBuilder::create()
            ->success()
            ->header('Cache-Control', 'no-cache, no-store')
            ->build();

        $this->assertSame('no-cache, no-store', $response->getHeaders()['Cache-Control']);
    }
}
