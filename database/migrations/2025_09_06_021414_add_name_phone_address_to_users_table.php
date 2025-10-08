<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNamePhoneAddressToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
          
            $table->string('phone', 15)->nullable()->after('email'); // store as string (with + if any)
            $table->string('address', 150)->nullable()->after('phone');
            // optional: keep 'name' for old code; you may want to keep it or populate it from first/last
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'phone', 'address']);
        });
    }
}
