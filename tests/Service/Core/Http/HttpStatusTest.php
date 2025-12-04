<?php

declare(strict_types=1);

namespace Tests\Service\Core\Http;

use Lunar\Service\Core\Http\HttpStatus;
use PHPUnit\Framework\TestCase;

class HttpStatusTest extends TestCase
{
    public function testCommonStatusCodeConstants(): void
    {
        $this->assertSame(200, HttpStatus::OK);
        $this->assertSame(201, HttpStatus::CREATED);
        $this->assertSame(204, HttpStatus::NO_CONTENT);
        $this->assertSame(301, HttpStatus::MOVED_PERMANENTLY);
        $this->assertSame(302, HttpStatus::FOUND);
        $this->assertSame(400, HttpStatus::BAD_REQUEST);
        $this->assertSame(401, HttpStatus::UNAUTHORIZED);
        $this->assertSame(403, HttpStatus::FORBIDDEN);
        $this->assertSame(404, HttpStatus::NOT_FOUND);
        $this->assertSame(500, HttpStatus::INTERNAL_SERVER_ERROR);
        $this->assertSame(503, HttpStatus::SERVICE_UNAVAILABLE);
    }

    public function testInformationalStatusCodes(): void
    {
        $this->assertSame(100, HttpStatus::CONTINUE);
        $this->assertSame(101, HttpStatus::SWITCHING_PROTOCOLS);
        $this->assertSame(102, HttpStatus::PROCESSING);
        $this->assertSame(103, HttpStatus::EARLY_HINTS);
    }

    public function testSuccessStatusCodes(): void
    {
        $this->assertSame(202, HttpStatus::ACCEPTED);
        $this->assertSame(203, HttpStatus::NON_AUTHORITATIVE_INFORMATION);
        $this->assertSame(205, HttpStatus::RESET_CONTENT);
        $this->assertSame(206, HttpStatus::PARTIAL_CONTENT);
        $this->assertSame(207, HttpStatus::MULTI_STATUS);
        $this->assertSame(208, HttpStatus::ALREADY_REPORTED);
        $this->assertSame(226, HttpStatus::IM_USED);
    }

    public function testRedirectionStatusCodes(): void
    {
        $this->assertSame(300, HttpStatus::MULTIPLE_CHOICES);
        $this->assertSame(303, HttpStatus::SEE_OTHER);
        $this->assertSame(304, HttpStatus::NOT_MODIFIED);
        $this->assertSame(305, HttpStatus::USE_PROXY);
        $this->assertSame(306, HttpStatus::SWITCH_PROXY);
        $this->assertSame(307, HttpStatus::TEMPORARY_REDIRECT);
        $this->assertSame(308, HttpStatus::PERMANENT_REDIRECT);
    }

    public function testClientErrorStatusCodes(): void
    {
        $this->assertSame(405, HttpStatus::METHOD_NOT_ALLOWED);
        $this->assertSame(406, HttpStatus::NOT_ACCEPTABLE);
        $this->assertSame(407, HttpStatus::PROXY_AUTHENTICATION_REQUIRED);
        $this->assertSame(408, HttpStatus::REQUEST_TIMEOUT);
        $this->assertSame(409, HttpStatus::CONFLICT);
        $this->assertSame(410, HttpStatus::GONE);
        $this->assertSame(418, HttpStatus::IM_A_TEAPOT);
        $this->assertSame(422, HttpStatus::UNPROCESSABLE_ENTITY);
        $this->assertSame(429, HttpStatus::TOO_MANY_REQUESTS);
        $this->assertSame(451, HttpStatus::UNAVAILABLE_FOR_LEGAL_REASONS);
    }

    public function testServerErrorStatusCodes(): void
    {
        $this->assertSame(501, HttpStatus::NOT_IMPLEMENTED);
        $this->assertSame(502, HttpStatus::BAD_GATEWAY);
        $this->assertSame(504, HttpStatus::GATEWAY_TIMEOUT);
        $this->assertSame(505, HttpStatus::HTTP_VERSION_NOT_SUPPORTED);
    }

    public function testGetDefaultMessageReturnsCorrectMessage(): void
    {
        $this->assertSame('OK', HttpStatus::getDefaultMessage(200));
        $this->assertSame('Not Found', HttpStatus::getDefaultMessage(404));
        $this->assertSame('Internal Server Error', HttpStatus::getDefaultMessage(500));
        $this->assertSame("I'm a teapot", HttpStatus::getDefaultMessage(418));
    }

    public function testGetDefaultMessageReturnsGenericForUnknownCode(): void
    {
        $this->assertSame('An unexpected error has occurred.', HttpStatus::getDefaultMessage(999));
        $this->assertSame('An unexpected error has occurred.', HttpStatus::getDefaultMessage(0));
    }
}
