<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('task_header_id');
            $table->text('keterangan_task');
            $table->string('ref_cust')->nullable();
            $table->enum('status', ['active', 'done', 'hide'])->default('active');
            $table->boolean('is_favorite')->default(0);
            $table->date('move_to_date')->nullable(); // hanya sebagai catatan
            $table->timestamps();

            $table->foreign('task_header_id')
                  ->references('id')
                  ->on('task_headers')
                  ->onDelete('cascade'); // jika header dihapus, otomatis hapus list
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_lists');
    }
}
