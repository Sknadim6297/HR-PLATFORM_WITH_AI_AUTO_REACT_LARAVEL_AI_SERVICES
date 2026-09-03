<?php

namespace Tests\Unit;

use App\Services\AI\CosineSimilarity;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CosineSimilarityTest extends TestCase
{
    private CosineSimilarity $similarity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->similarity = new CosineSimilarity;
    }

    #[Test]
    public function identical_vectors_score_one(): void
    {
        $this->assertSame(1.0, $this->similarity->score([1, 0], [1, 0]));
    }

    #[Test]
    public function orthogonal_vectors_score_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->similarity->score([1, 0], [0, 1]), 0.000001);
    }

    #[Test]
    public function opposite_vectors_score_negative_one(): void
    {
        $this->assertSame(-1.0, $this->similarity->score([1, 0], [-1, 0]));
    }

    #[Test]
    public function zero_vector_returns_null_without_division_by_zero(): void
    {
        $this->assertNull($this->similarity->score([0, 0], [1, 0]));
        $this->assertNull($this->similarity->score([1, 0], [0, 0]));
    }

    #[Test]
    public function dimension_mismatch_returns_null(): void
    {
        $this->assertNull($this->similarity->score([1, 0], [1, 0, 0]));
    }

    #[Test]
    public function invalid_numeric_values_return_null(): void
    {
        $this->assertNull($this->similarity->score([1, 'x'], [1, 0]));
    }

    #[Test]
    public function empty_vectors_return_null(): void
    {
        $this->assertNull($this->similarity->score([], [1, 0]));
        $this->assertNull($this->similarity->score([1, 0], []));
    }
}
