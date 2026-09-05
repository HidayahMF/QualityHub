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
        $table->string('employee_id', 50)
            ->nullable()
            ->unique()
            ->after('id');

        $table->foreignId('department_id')
            ->nullable()
            ->after('email')
            ->constrained('departments')
            ->nullOnDelete();

        $table->string('role', 30)
            ->default('user')
            ->after('department_id');

        $table->string('account_status', 30)
            ->default('active')
            ->after('role');

        $table->timestamp('last_login_at')
            ->nullable()
            ->after('account_status');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['department_id']);

        $table->dropColumn([
            'employee_id',
            'department_id',
            'role',
            'account_status',
            'last_login_at',
        ]);
    });
}
};
