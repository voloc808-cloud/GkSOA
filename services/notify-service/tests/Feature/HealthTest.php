<?php
namespace Tests\Feature;
class HealthTest extends \Tests\TestCase
{
    public function test_health_endpoint(): void
    {
        $this->get('/health');
        $this->assertResponseStatus(200);
        $this->seeJson(['status' => 'success']);
    }
}
