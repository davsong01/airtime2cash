<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_AUTO_SHARE_INSTRUCTION = <<<'HTML'
<h5>How Auto Transfer Works</h5>
<ol>
    <li>Select network and enter airtime amount.</li>
    <li>Enter the <strong>phone number</strong> holding the airtime.</li>
    <li>Click <strong>Initiate</strong> &mdash; you'll be asked for your SIM PIN.</li>
    <li>Enter your SIM PIN &mdash; an AutoSync OTP is sent to the sender's phone.</li>
    <li>Enter the OTP to <strong>share airtime</strong> &mdash; it is moved automatically.</li>
    <li>Your wallet is credited instantly upon confirmation.</li>
</ol>
<p><strong>Important:</strong> Ensure the sender's SIM has enough airtime and a valid share PIN.</p>
HTML;

    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'auto_share_instruction')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('auto_share_instruction')->nullable();
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumns('products', ['type', 'auto_share_instruction'])) {
            DB::table('products')
                ->where('type', 'airtime2cash')
                ->whereNull('auto_share_instruction')
                ->update(['auto_share_instruction' => self::DEFAULT_AUTO_SHARE_INSTRUCTION]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'auto_share_instruction')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('auto_share_instruction');
            });
        }
    }
};
