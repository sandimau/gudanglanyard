<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Webhook upsert(['nota'], ...) tanpa unique index selalu INSERT,
     * sehingga satu nota punya banyak baris status berbeda.
     */
    public function up(): void
    {
        // Gabungkan metadata ke baris terbaru (MAX id) sebelum hapus duplikat
        DB::statement("
            UPDATE marketplace_buffers keeper
            INNER JOIN (
                SELECT
                    nota,
                    MAX(id) AS keep_id,
                    MAX(project_id) AS project_id,
                    MAX(marketplace_id) AS marketplace_id,
                    MAX(custom) AS custom,
                    MAX(mp) AS mp
                FROM marketplace_buffers
                WHERE nota IS NOT NULL AND nota != ''
                GROUP BY nota
                HAVING COUNT(*) > 1
            ) agg ON keeper.id = agg.keep_id
            SET
                keeper.project_id = COALESCE(keeper.project_id, agg.project_id),
                keeper.marketplace_id = COALESCE(keeper.marketplace_id, agg.marketplace_id),
                keeper.custom = COALESCE(agg.custom, keeper.custom),
                keeper.mp = COALESCE(keeper.mp, agg.mp)
        ");

        DB::statement("
            DELETE b FROM marketplace_buffers b
            INNER JOIN (
                SELECT nota, MAX(id) AS keep_id
                FROM marketplace_buffers
                WHERE nota IS NOT NULL AND nota != ''
                GROUP BY nota
                HAVING COUNT(*) > 1
            ) k ON b.nota = k.nota AND b.id != k.keep_id
        ");

        DB::table('marketplace_buffers')
            ->where(function ($q) {
                $q->whereNull('nota')->orWhere('nota', '');
            })
            ->delete();

        $hasUnique = collect(DB::select('SHOW INDEX FROM marketplace_buffers'))
            ->contains(function ($idx) {
                return (int) $idx->Non_unique === 0
                    && in_array($idx->Key_name, ['marketplace_buffers_nota_unique', 'idx_nota_unique'], true);
            });

        if (!$hasUnique) {
            Schema::table('marketplace_buffers', function (Blueprint $table) {
                $table->unique('nota', 'marketplace_buffers_nota_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('marketplace_buffers', function (Blueprint $table) {
            $table->dropUnique('marketplace_buffers_nota_unique');
        });
    }
};
