<?php

namespace Tests\Unit;

use App\Services\FaceMatchingService;
use Tests\TestCase;

class FaceMatchingServiceTest extends TestCase
{
    public function test_matching_signatures_pass(): void
    {
        $matcher = new FaceMatchingService;
        $enrolled = [0.2, 0.3, 0.7, 0.3, 0.5, 0.5, 0.4, 0.8, 0.35, 0.75, 0.65, 0.75, 0.15, 0.55, 0.85, 0.55, 0.5, 0.5];
        $candidate = [0.21, 0.31, 0.69, 0.29, 0.51, 0.49, 0.41, 0.79, 0.36, 0.74, 0.64, 0.76, 0.16, 0.54, 0.84, 0.56, 0.51, 0.49];

        $result = $matcher->compare($enrolled, $candidate);

        $this->assertTrue($result['matched']);
        $this->assertNotNull($result['score']);
    }

    public function test_different_signatures_fail(): void
    {
        $matcher = new FaceMatchingService;
        $enrolled = [0.2, 0.3, 0.7, 0.3, 0.5, 0.5, 0.4, 0.8, 0.35, 0.75, 0.65, 0.75, 0.15, 0.55, 0.85, 0.55, 0.5, 0.5];
        $candidate = [0.9, 0.1, 0.1, 0.9, 0.2, 0.2, 0.8, 0.1, 0.9, 0.2, 0.1, 0.8, 0.95, 0.1, 0.05, 0.9, 0.1, 0.9];

        $result = $matcher->compare($enrolled, $candidate);

        $this->assertFalse($result['matched']);
    }
}
