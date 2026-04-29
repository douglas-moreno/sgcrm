<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->after('company_id')->constrained('roles')->restrictOnDelete();
            $table->string('avatar_path', 2048)->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('avatar_path');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropIndex(['is_active']);
            $table->dropColumn(['avatar_path', 'is_active', 'must_change_password', 'last_login_at']);
            $table->dropSoftDeletes();
        });
    }
};
