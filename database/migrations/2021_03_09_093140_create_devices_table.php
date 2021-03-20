<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateDevicesTable.
 */
class CreateDevicesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('devices', function(Blueprint $table) {
            $table->increments('id');
            $table->string('device_name')->nullable();
            $table->string('device_type')->nullable();
            $table->string('apn_token')->unique()->nullable();
            $table->boolean('notification_status')->default(true);
            $table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('devices');
	}
}
