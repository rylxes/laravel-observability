<?php

namespace Rylxes\Observability\Analyzers;

use Illuminate\Support\Facades\DB;

class ExplainAnalyzer
{
    /**
     * Run EXPLAIN on a query and return normalized results.
     */
    public function explain(string $sql, array $bindings = [], ?string $connection = null): ?array
    {
        if (!config('observability.queries.capture_explain', true)) {
            return null;
        }

        // Only EXPLAIN SELECT queries
        if (config('observability.queries.explain_only_select', true)) {
            $trimmed = trim(strtoupper($sql));
            if (!str_starts_with($trimmed, 'SELECT')) {
                return null;
            }
        }

        // Safety check: skip queries that modify data
        if ($this->containsSideEffects($sql)) {
            return null;
        }

        $driver = $this->getDriver($connection);

        try {
            $rawOutput = $this->runExplain($sql, $bindings, $connection, $driver);

            if (empty($rawOutput)) {
                return null;
            }

            return $this->normalizeOutput($rawOutput, $driver);
        } catch (\Throwable $e) {
            \Log::debug('Observability: EXPLAIN failed - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Run the EXPLAIN statement based on database driver.
     */
    protected function runExplain(string $sql, array $bindings, ?string $connection, string $driver): array
    {
        $db = $connection ? DB::connection($connection) : DB::connection();

        $timeout = config('observability.queries.explain_timeout', 5);

        return match ($driver) {
            'pgsql' => $this->runPostgresExplain($db, $sql, $bindings),
            'sqlite' => $this->runSqliteExplain($db, $sql, $bindings),
            default => $this->runMysqlExplain($db, $sql, $bindings),
        };
    }

    /**
     * Run MySQL EXPLAIN.
     */
    protected function runMysqlExplain($db, string $sql, array $bindings): array
    {
        return $db->select("EXPLAIN {$sql}", $bindings);
    }

    /**
     * Run PostgreSQL EXPLAIN with JSON format.
     */
    protected function runPostgresExplain($db, string $sql, array $bindings): array
    {
        $results = $db->select("EXPLAIN (FORMAT JSON) {$sql}", $bindings);

        if (!empty($results)) {
            $json = $results[0]->{'QUERY PLAN'} ?? null;
            if ($json) {
                return json_decode($json, true) ?? [];
            }
        }

        return [];
    }

    /**
     * Run SQLite EXPLAIN QUERY PLAN.
     */
    protected function runSqliteExplain($db, string $sql, array $bindings): array
    {
        return $db->select("EXPLAIN QUERY PLAN {$sql}", $bindings);
    }

    /**
     * Normalize EXPLAIN output across database drivers.
     */
    protected function normalizeOutput(array $rawOutput, string $driver): array
    {
        return match ($driver) {
            'pgsql' => $this->normalizePostgres($rawOutput),
            'sqlite' => $this->normalizeSqlite($rawOutput),
            default => $this->normalizeMysql($rawOutput),
        };
    }

    /**
     * Normalize MySQL EXPLAIN output.
     */
    protected function normalizeMysql(array $rows): array
    {
        $result = [
            'raw_output' => array_map(fn ($row) => (array) $row, $rows),
            'scan_type' => 'unknown',
            'rows_examined' => 0,
            'index_used' => null,
            'possible_indexes' => [],
            'warnings' => [],
            'suggestions' => [],
        ];

        foreach ($rows as $row) {
            $row = (object) $row;

            // Determine scan type from 'type' column
            $type = $row->type ?? '';
            $result['scan_type'] = $this->mapMysqlScanType($type);

            // Rows examined
            $result['rows_examined'] += (int) ($row->rows ?? 0);

            // Index info
            $result['index_used'] = $row->key ?? null;
            if (!empty($row->possible_keys)) {
                $result['possible_indexes'] = explode(',', $row->possible_keys);
            }

            $tableName = $row->table ?? 'unknown';

            // Generate warnings and suggestions
            if ($type === 'ALL') {
                $rows = $row->rows ?? 'unknown';
                $result['warnings'][] = "Full table scan on table \"{$tableName}\" ({$rows} rows)";
                $result['suggestions'][] = "Consider adding an index on table \"{$tableName}\" for the WHERE clause columns";
            }

            $extra = $row->Extra ?? '';
            if (str_contains($extra, 'Using filesort')) {
                $result['warnings'][] = "Using filesort on table \"{$tableName}\"";
                $result['suggestions'][] = "Add composite index on ORDER BY columns for table \"{$tableName}\"";
            }

            if (str_contains($extra, 'Using temporary')) {
                $result['warnings'][] = "Temporary table created for table \"{$tableName}\"";
                $result['suggestions'][] = "Consider denormalization or adding index for GROUP BY columns";
            }

            if (empty($row->key) && !empty($row->possible_keys)) {
                $result['warnings'][] = "Possible indexes exist but none used on table \"{$tableName}\"";
                $result['suggestions'][] = "Review index hints or query structure";
            }
        }

        return $result;
    }

    /**
     * Normalize PostgreSQL EXPLAIN (JSON) output.
     */
    protected function normalizePostgres(array $plan): array
    {
        $result = [
            'raw_output' => $plan,
            'scan_type' => 'unknown',
            'rows_examined' => 0,
            'index_used' => null,
            'possible_indexes' => [],
            'warnings' => [],
            'suggestions' => [],
        ];

        $node = $plan[0]['Plan'] ?? $plan[0] ?? [];

        if (empty($node)) {
            return $result;
        }

        $nodeType = $node['Node Type'] ?? '';
        $result['scan_type'] = $this->mapPostgresScanType($nodeType);
        $result['rows_examined'] = (int) ($node['Plan Rows'] ?? 0);

        if (isset($node['Index Name'])) {
            $result['index_used'] = $node['Index Name'];
        }

        $relationName = $node['Relation Name'] ?? 'unknown';

        if ($nodeType === 'Seq Scan') {
            $result['warnings'][] = "Sequential scan on table \"{$relationName}\"";
            $result['suggestions'][] = "Consider adding an index for filter columns on \"{$relationName}\"";
        }

        return $result;
    }

    /**
     * Normalize SQLite EXPLAIN QUERY PLAN output.
     */
    protected function normalizeSqlite(array $rows): array
    {
        $result = [
            'raw_output' => array_map(fn ($row) => (array) $row, $rows),
            'scan_type' => 'unknown',
            'rows_examined' => 0,
            'index_used' => null,
            'possible_indexes' => [],
            'warnings' => [],
            'suggestions' => [],
        ];

        foreach ($rows as $row) {
            $row = (object) $row;
            $detail = $row->detail ?? '';

            if (str_contains($detail, 'SCAN')) {
                $result['scan_type'] = 'full_scan';
                $result['warnings'][] = "Full table scan detected: {$detail}";
                $result['suggestions'][] = 'Consider adding an index to avoid full table scan';
            } elseif (str_contains($detail, 'SEARCH')) {
                $result['scan_type'] = 'index_scan';
            } elseif (str_contains($detail, 'USING INDEX')) {
                $result['scan_type'] = 'index_scan';
                if (preg_match('/USING INDEX (\w+)/', $detail, $matches)) {
                    $result['index_used'] = $matches[1];
                }
            }
        }

        return $result;
    }

    /**
     * Map MySQL EXPLAIN type to normalized scan type.
     */
    protected function mapMysqlScanType(string $type): string
    {
        return match (strtolower($type)) {
            'all' => 'full_scan',
            'index' => 'index_scan',
            'range' => 'range_scan',
            'ref', 'eq_ref' => 'ref',
            'const', 'system' => 'const',
            'fulltext' => 'fulltext',
            default => 'unknown',
        };
    }

    /**
     * Map PostgreSQL EXPLAIN node type to normalized scan type.
     */
    protected function mapPostgresScanType(string $nodeType): string
    {
        return match ($nodeType) {
            'Seq Scan' => 'full_scan',
            'Index Scan', 'Index Only Scan', 'Bitmap Index Scan' => 'index_scan',
            'Bitmap Heap Scan' => 'range_scan',
            default => 'unknown',
        };
    }

    /**
     * Check if SQL contains side-effect operations.
     */
    protected function containsSideEffects(string $sql): bool
    {
        $normalized = trim(strtoupper($sql));

        return str_starts_with($normalized, 'INSERT') ||
               str_starts_with($normalized, 'UPDATE') ||
               str_starts_with($normalized, 'DELETE') ||
               str_starts_with($normalized, 'DROP') ||
               str_starts_with($normalized, 'ALTER') ||
               str_starts_with($normalized, 'CREATE') ||
               str_starts_with($normalized, 'TRUNCATE');
    }

    /**
     * Get the database driver name.
     */
    protected function getDriver(?string $connection): string
    {
        $conn = $connection ?? config('database.default');

        return config("database.connections.{$conn}.driver", 'mysql');
    }
}
