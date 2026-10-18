<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            $table->string('name');
            $table->string('meta_name')->nullable();

            $table->string('sku')->unique();

            $table->text('description_en');
            $table->text('description_ar')->nullable();
            $table->text('meta_description')->nullable();

            $table->decimal('weight', 10, 2)->nullable();

            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unsignedInteger('reserved_stock')->default(0);

            $table->unsignedInteger('price_in_fils');
            $table->unsignedInteger('cost_price_in_fils')->nullable();

            $table->json('specs_en')->nullable();
            $table->json('specs_ar')->nullable();

            $table
                ->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('type_id')->constrained()->cascadeOnDelete();
            $table->timestamp('featured_at')->nullable();
            $table->timestamp('activated_at')->default(now())->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('activated_at');
            $table->index('type_id');
            $table->index('brand_id');
            $table->index('featured_at');
            $table->index('price_in_fils');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
