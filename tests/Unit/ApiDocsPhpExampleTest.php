<?php

namespace Tests\Unit;

use App\Support\ApiDocsPhpExample;
use Tests\TestCase;

class ApiDocsPhpExampleTest extends TestCase
{
    public function test_builds_get_example(): void
    {
        $php = ApiDocsPhpExample::build('GET', 'http://localhost/api/v1/catalog');

        $this->assertStringContainsString("Http::withToken('ac_YOUR_API_KEY')", $php);
        $this->assertStringContainsString("->get('http://localhost/api/v1/catalog')", $php);
    }

    public function test_builds_post_example_with_payload(): void
    {
        $php = ApiDocsPhpExample::build('POST', 'http://localhost/api/v1/listings', [
            'price' => 1000,
            'ad_name' => 'test',
        ]);

        $this->assertStringContainsString('$payload = [', $php);
        $this->assertStringContainsString("->post('http://localhost/api/v1/listings', \$payload)", $php);
    }
}