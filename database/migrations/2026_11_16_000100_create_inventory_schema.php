<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('institution_code')->nullable();
            $t->text('address')->nullable();
            $t->string('timezone')->default('Asia/Jakarta');
            $t->unsignedSmallInteger('label_width_mm')->default(90);
            $t->unsignedSmallInteger('label_height_mm')->default(45);
            $t->timestamps();
        });
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
        });
        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->restrictOnDelete();
            $t->foreignId('permission_id')->constrained()->restrictOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('role_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('is_active')->default(true)->index();
        });
        foreach (['locations', 'people', 'classifications', 'fund_sources', 'ownerships'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->string('code')->nullable()->unique();
                $t->string('name');
                $t->boolean('is_active')->default(true)->index();
                $t->text('notes')->nullable();
                if ($name === 'ownerships') {
                    $t->boolean('is_bmn')->default(false);
                }
                $t->timestamps();
            });
        }
        Schema::create('rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('location_id')->constrained()->restrictOnDelete();
            $t->string('code')->nullable()->unique();
            $t->string('name');
            $t->boolean('is_active')->default(true)->index();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('procurements', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->string('supplier')->nullable();
            $t->date('acquired_on')->nullable();
            $t->string('method');
            $t->decimal('total', 18, 2);
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('procurement_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->foreignId('classification_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('ownership_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 18, 2);
            $t->string('inventory_prefix');
            $t->timestamps();
        });
        Schema::create('funding_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('procurement_id')->constrained()->restrictOnDelete();
            $t->foreignId('fund_source_id')->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('budget_year');
            $t->string('document_reference');
            $t->decimal('amount', 18, 2);
            $t->string('source_snapshot');
            $t->timestamps();
        });
        Schema::create('assets', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('inventory_number');
            $t->unique(['school_id', 'inventory_number']);
            $t->string('qr_token', 64)->unique();
            $t->string('name')->index();
            $t->foreignId('classification_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('ownership_id')->constrained()->restrictOnDelete();
            $t->string('official_register')->nullable()->index();
            $t->string('brand')->nullable();
            $t->string('model')->nullable();
            $t->string('serial_number')->nullable()->index();
            $t->text('specifications')->nullable();
            $t->date('acquired_on')->nullable();
            $t->unsignedSmallInteger('acquisition_year')->nullable()->index();
            $t->decimal('acquisition_value', 18, 2)->nullable();
            $t->string('acquisition_method')->default('unknown');
            $t->string('condition')->default('unknown')->index();
            $t->string('lifecycle_status')->default('active')->index();
            $t->foreignId('room_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('person_id')->nullable()->constrained('people')->restrictOnDelete();
            $t->foreignId('procurement_item_id')->nullable()->constrained()->restrictOnDelete();
            $t->text('notes')->nullable();
            $t->boolean('verified')->default(false)->index();
            $t->string('disposal_reference')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('transfers', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('asset_id')->constrained()->restrictOnDelete();
            $t->foreignUuid('active_asset_id')->nullable()->unique()->constrained('assets')->restrictOnDelete();
            foreach (['from_room_id', 'to_room_id'] as $col) {
                $t->foreignId($col)->nullable()->constrained('rooms')->restrictOnDelete();
            }
            foreach (['from_person_id', 'to_person_id'] as $col) {
                $t->foreignId($col)->nullable()->constrained('people')->restrictOnDelete();
            }
            $t->json('origin_snapshot');
            $t->json('destination_snapshot');
            $t->string('status')->default('draft')->index();
            $t->date('effective_on');
            $t->text('reason');
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->foreignId('correction_of_id')->nullable()->constrained('transfers')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('asset_events', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('asset_id')->constrained()->restrictOnDelete();
            $t->string('type');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('actor_snapshot');
            $t->json('data');
            $t->date('effective_on')->nullable();
            $t->timestamps();
        });
        Schema::create('maintenances', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('asset_id')->constrained()->restrictOnDelete();
            $t->date('performed_on');
            $t->text('complaint');
            $t->text('action');
            $t->string('vendor');
            $t->decimal('cost', 18, 2)->nullable();
            $t->text('result');
            $t->string('condition_after');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('asset_id')->constrained()->restrictOnDelete();
            $t->foreignId('transfer_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('maintenance_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('path')->unique();
            $t->string('original_name');
            $t->string('mime');
            $t->unsignedInteger('size');
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('actor_snapshot');
            $t->string('action')->index();
            $t->string('subject_type');
            $t->string('subject_id')->index();
            $t->json('changes');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'attachments', 'maintenances', 'asset_events', 'transfers', 'assets', 'funding_allocations', 'procurement_items', 'procurements', 'rooms', 'ownerships', 'fund_sources', 'classifications', 'people', 'locations'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('role_id');
            $t->dropColumn('is_active');
        });
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('schools');
    }
};
