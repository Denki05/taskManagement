<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('pic'); // username atau user_id tergantung kebutuhan
            $table->date('tanggal');
            $table->enum('status', ['active', 'done'])->default('active');
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
        Schema::dropIfExists('task_headers');
    }
}
