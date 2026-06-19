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
        Schema::table('users', function (Blueprint $table) {
            // Renombrar o agregar campos firstName y lastName
            if (!Schema::hasColumn('users', 'firstName')) {
                $table->string('firstName')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'lastName')) {
                $table->string('lastName')->nullable()->after('firstName');
            }
            if (!Schema::hasColumn('users', 'phoneNumber')) {
                $table->string('phoneNumber')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['Admin', 'User'])->default('User')->after('password');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['Active', 'Inactive'])->default('Active')->after('role');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->json('address')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'profilePicture')) {
                $table->string('profilePicture')->nullable()->after('address');
            }
            // Hacer la columna 'name' nullable
            if (Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('firstName');
            $table->dropColumnIfExists('lastName');
            $table->dropColumnIfExists('phoneNumber');
            $table->dropColumnIfExists('role');
            $table->dropColumnIfExists('status');
            $table->dropColumnIfExists('address');
            $table->dropColumnIfExists('profilePicture');
        });
    }
};
