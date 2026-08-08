<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function normalizeChargeBreakdown($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            if (is_string($decoded) && $decoded !== '') {
                $decoded = json_decode($decoded, true);
            }

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    public function up(): void
    {
        if (!Schema::hasTable('transaction_logs') || !Schema::hasColumn('transaction_logs', 'charge_breakdown')) {
            return;
        }

        DB::table('transaction_logs')
            ->select('id', 'charge_breakdown')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = $this->normalizeChargeBreakdown($row->charge_breakdown);

                    if ($normalized === []) {
                        continue;
                    }

                    DB::table('transaction_logs')
                        ->where('id', $row->id)
                        ->update([
                            'charge_breakdown' => json_encode($normalized),
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
