<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 20)->default('ukuran'); // ukuran | warna | lainnya
            $table->string('value', 150);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // Migrasi data: salin varian JSON lama (products.variants) ke tabel baru.
        // Kolom JSON lama dibiarkan (tidak di-drop) agar migrasi aman dan bisa di-rollback.
        DB::table('products')->whereNotNull('variants')->orderBy('id')->each(function ($product) {
            $variants = json_decode($product->variants, true);

            if (! is_array($variants)) {
                return;
            }

            $rows = [];
            foreach (array_values($variants) as $i => $value) {
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }
                $rows[] = [
                    'product_id' => $product->id,
                    'type' => Str::contains(Str::lower($value), ['custom', 'fabrikasi', 'produksi ulang', 'reverse']) ? 'lainnya' : 'ukuran',
                    'value' => Str::limit(trim($value), 150, ''),
                    'sort_order' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($rows) {
                DB::table('product_variants')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
