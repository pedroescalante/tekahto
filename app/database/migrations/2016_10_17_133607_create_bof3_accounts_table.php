<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBof3AccountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('bof3_accounts', function (Blueprint $table) {
    			$table->increments('id');
			$table->string('email', 100);
			$table->string('pricing_plan', 100);
			$table->date('start_date');
			$table->string('status', 100);
			$table->integer('merchant_id');
			$table->integer('subscription_id');
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
		//
	}

}
