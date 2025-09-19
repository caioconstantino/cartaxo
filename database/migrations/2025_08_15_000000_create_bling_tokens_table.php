<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bling_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token', 512);
            $table->string('refresh_token', 512)->nullable();
            $table->string('token_type', 50)->nullable();
            $table->integer('expires_in')->nullable(); // em segundos
            $table->timestamp('expires_at')->nullable(); // calculado
            $table->text('scope')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('bling_tokens');
    }
};
