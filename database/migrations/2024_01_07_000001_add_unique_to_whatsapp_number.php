<?php

use App\Helpers\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('users')
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->orderBy('id')
            ->get(['id', 'whatsapp_number']);

        $seen = [];
        foreach ($rows as $row) {
            $normalized = PhoneNumber::normalize($row->whatsapp_number);

            if ($normalized === null) {
                DB::table('users')->where('id', $row->id)->update(['whatsapp_number' => null]);
                continue;
            }

            if (in_array($normalized, $seen, true)) {
                DB::table('users')->where('id', $row->id)->update(['whatsapp_number' => null]);
                continue;
            }

            $seen[] = $normalized;

            if ($normalized !== $row->whatsapp_number) {
                DB::table('users')->where('id', $row->id)->update(['whatsapp_number' => $normalized]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_number']);
        });
    }
};
