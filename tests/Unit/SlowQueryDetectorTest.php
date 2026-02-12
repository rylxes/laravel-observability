<?php

namespace Rylxes\Observability\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rylxes\Observability\Analyzers\SlowQueryDetector;

class SlowQueryDetectorTest extends TestCase
{
    protected SlowQueryDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new SlowQueryDetector();
    }

    /** @test */
    public function it_recommends_adding_where_clause()
    {
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $queries = collect([
            (object) [
                'sql' => 'SELECT * FROM users',
                'table_name' => 'users',
                'is_duplicate' => false,
                'duration_ms' => 1500,
            ],
        ]);

        $recommendations = $method->invoke($this->detector, $queries);

        $types = array_column($recommendations, 'type');
        $this->assertContains('missing_where', $types);
    }

    /** @test */
    public function it_recommends_avoiding_select_star()
    {
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $queries = collect([
            (object) [
                'sql' => 'SELECT * FROM users WHERE id = 1',
                'table_name' => 'users',
                'is_duplicate' => false,
                'duration_ms' => 1500,
            ],
        ]);

        $recommendations = $method->invoke($this->detector, $queries);

        $types = array_column($recommendations, 'type');
        $this->assertContains('select_star', $types);
    }

    /** @test */
    public function it_detects_n_plus_one_duplicate()
    {
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $queries = collect([
            (object) [
                'sql' => 'SELECT id, name FROM users WHERE id = 1',
                'table_name' => 'users',
                'is_duplicate' => true,
                'duration_ms' => 1500,
            ],
        ]);

        $recommendations = $method->invoke($this->detector, $queries);

        $types = array_column($recommendations, 'type');
        $this->assertContains('n_plus_one', $types);
    }

    /** @test */
    public function it_detects_slow_sorting()
    {
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $queries = collect([
            (object) [
                'sql' => 'SELECT id FROM users WHERE active = 1 ORDER BY created_at DESC',
                'table_name' => 'users',
                'is_duplicate' => false,
                'duration_ms' => 3000,
            ],
        ]);

        $recommendations = $method->invoke($this->detector, $queries);

        $types = array_column($recommendations, 'type');
        $this->assertContains('slow_sorting', $types);
    }

    /** @test */
    public function it_does_not_flag_sorting_below_threshold()
    {
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $queries = collect([
            (object) [
                'sql' => 'SELECT id FROM users WHERE active = 1 ORDER BY created_at DESC',
                'table_name' => 'users',
                'is_duplicate' => false,
                'duration_ms' => 500, // Below 2000ms threshold
            ],
        ]);

        $recommendations = $method->invoke($this->detector, $queries);

        $types = array_column($recommendations, 'type');
        $this->assertNotContains('slow_sorting', $types);
    }

    /** @test */
    public function it_does_not_flag_select_with_where_and_limit()
    {
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $queries = collect([
            (object) [
                'sql' => 'SELECT id FROM users LIMIT 10',
                'table_name' => 'users',
                'is_duplicate' => false,
                'duration_ms' => 1500,
            ],
        ]);

        $recommendations = $method->invoke($this->detector, $queries);

        $types = array_column($recommendations, 'type');
        // Should NOT flag missing_where because LIMIT is present
        $this->assertNotContains('missing_where', $types);
    }

    /** @test */
    public function it_deduplicates_recommendations()
    {
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $queries = collect([
            (object) [
                'sql' => 'SELECT * FROM users',
                'table_name' => 'users',
                'is_duplicate' => false,
                'duration_ms' => 1500,
            ],
            (object) [
                'sql' => 'SELECT * FROM users',
                'table_name' => 'users',
                'is_duplicate' => false,
                'duration_ms' => 1600,
            ],
        ]);

        $recommendations = $method->invoke($this->detector, $queries);

        // Should deduplicate identical recommendations
        $selectStarCount = count(array_filter($recommendations, fn ($r) => $r['type'] === 'select_star'));
        $this->assertEquals(1, $selectStarCount);
    }

    /** @test */
    public function it_returns_empty_for_no_issues()
    {
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('generateRecommendations');
        $method->setAccessible(true);

        $queries = collect([
            (object) [
                'sql' => 'SELECT id, name FROM users WHERE active = 1',
                'table_name' => 'users',
                'is_duplicate' => false,
                'duration_ms' => 500,
            ],
        ]);

        $recommendations = $method->invoke($this->detector, $queries);

        $this->assertEmpty($recommendations);
    }
}
