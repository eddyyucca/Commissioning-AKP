<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name', 120);
            $t->string('category', 10)->index();
            $t->string('brand', 60)->nullable();
            $t->string('model', 60)->nullable();
            $t->string('serial_number', 60)->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->string('plate', 30)->nullable();
            $t->string('owner', 80)->default('AKP');
            $t->string('area', 80)->nullable()->index();
            $t->boolean('is_active')->default(true)->index();
            $t->date('commissioned_until')->nullable()->index();
            $t->dateTime('last_commissioned_at')->nullable();
            $t->unsignedBigInteger('last_commissioning_id')->nullable();
            $t->dateTime('hold_since')->nullable()->index();
            $t->string('hold_reason', 255)->nullable();
            $t->date('idle_since')->nullable();
            $t->string('notes', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('checklist_template_items', function (Blueprint $t) {
            $t->id();
            $t->string('scope', 20)->default('both');           // both | commissioning | recommissioning
            $t->string('categories', 60)->default('all');       // all atau LV,HD,...
            $t->string('reasons', 120)->nullable();             // khusus recommissioning
            $t->string('group_name', 80);
            $t->string('label', 255);
            $t->boolean('is_critical')->default(false);
            $t->string('reference', 120)->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('commissionings', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $t->string('unit_code', 30)->index();
            $t->string('unit_name', 120);
            $t->string('category', 10);
            $t->string('kind', 20);                              // commissioning | recommissioning
            $t->string('reason', 30)->nullable();
            $t->string('reason_note', 255)->nullable();
            $t->string('area', 80)->nullable();
            $t->date('planned_date')->nullable();
            $t->unsignedInteger('meter_reading')->nullable();   // odometer (km) atau hour meter (jam)
            $t->string('meter_unit', 10)->nullable();
            $t->string('status', 20)->default('draft')->index(); // draft submitted rejected approved cancelled
            $t->string('result', 20)->nullable();                // laik | laik_catatan
            $t->date('valid_until')->nullable();
            $t->string('requested_by_id', 40)->nullable();
            $t->string('requested_by_name', 120);
            $t->string('requested_by_nik', 40)->nullable();
            $t->dateTime('submitted_at')->nullable();
            $t->string('safety_by_name', 120)->nullable();
            $t->string('safety_by_id', 40)->nullable();
            $t->dateTime('safety_at')->nullable();
            $t->string('safety_notes', 500)->nullable();
            $t->string('workshop_by_name', 120)->nullable();
            $t->string('workshop_by_id', 40)->nullable();
            $t->dateTime('workshop_at')->nullable();
            $t->string('workshop_notes', 500)->nullable();
            $t->string('decided_by_name', 120)->nullable();
            $t->string('decided_by_id', 40)->nullable();
            $t->dateTime('decided_at')->nullable();
            $t->string('decision_notes', 500)->nullable();
            $t->string('returned_reason', 500)->nullable();
            $t->string('cancelled_reason', 255)->nullable();
            $t->timestamps();
            $t->index(['unit_id', 'status']);
        });

        Schema::create('commissioning_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('commissioning_id')->constrained('commissionings')->cascadeOnDelete();
            $t->string('group_name', 80);
            $t->string('label', 255);
            $t->boolean('is_critical')->default(false);
            $t->string('reference', 120)->nullable();
            $t->string('result', 5)->nullable();                 // ok | bad | na
            $t->string('note', 500)->nullable();
            $t->string('photo_path', 255)->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('findings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('commissioning_id')->constrained('commissionings')->cascadeOnDelete();
            $t->unsignedBigInteger('item_id')->nullable()->index();
            $t->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $t->string('title', 255);
            $t->string('severity', 10)->default('minor');       // critical | major | minor
            $t->string('description', 500)->nullable();
            $t->string('action_plan', 500)->nullable();
            $t->string('pic_name', 120)->nullable();
            $t->date('due_date')->nullable();
            $t->string('status', 15)->default('open')->index();  // open | in_progress | closed
            $t->dateTime('closed_at')->nullable();
            $t->string('closed_by_name', 120)->nullable();
            $t->string('closed_note', 500)->nullable();
            $t->timestamps();
        });

        Schema::create('incidents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $t->dateTime('occurred_at')->index();
            $t->string('kind', 20);
            $t->string('severity', 10)->default('ringan');
            $t->string('description', 1000);
            $t->boolean('requires_recommissioning')->default(false);
            $t->string('reported_by_name', 120);
            $t->string('reported_by_id', 40)->nullable();
            $t->string('status', 10)->default('open')->index();  // open | resolved
            $t->unsignedBigInteger('resolved_by_commissioning_id')->nullable();
            $t->dateTime('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('unit_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $t->string('type', 20);
            $t->string('number', 80)->nullable();
            $t->date('issued_at')->nullable();
            $t->date('expires_at')->nullable()->index();
            $t->string('notes', 255)->nullable();
            $t->string('created_by_name', 120)->nullable();
            $t->timestamps();
        });

        Schema::create('alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unit_id')->nullable()->constrained('units')->cascadeOnDelete();
            $t->string('dedupe_key', 120)->unique();
            $t->string('type', 30)->index();
            $t->string('severity', 10)->index();                 // kritis | tinggi | sedang | info
            $t->string('title', 200);
            $t->string('message', 500);
            $t->date('due_date')->nullable();
            $t->dateTime('first_seen_at');
            $t->dateTime('last_seen_at');
            $t->string('acknowledged_by_name', 120)->nullable();
            $t->dateTime('acknowledged_at')->nullable();
            $t->dateTime('resolved_at')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['alerts', 'unit_documents', 'incidents', 'findings', 'commissioning_items', 'commissionings', 'checklist_template_items', 'units'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
