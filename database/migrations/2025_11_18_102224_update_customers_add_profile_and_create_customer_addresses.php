<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing customers table: add columns if not exists
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'full_name')) {
                    $table->string('full_name', 191)->nullable()->after('phone');
                }
                if (!Schema::hasColumn('customers', 'mobile_verified')) {
                    $table->boolean('mobile_verified')->default(false)->after('full_name');
                }
                if (!Schema::hasColumn('customers', 'email')) {
                    $table->string('email', 191)->nullable()->after('mobile_verified');
                }
                if (!Schema::hasColumn('customers', 'profile_photo_url')) {
                    $table->string('profile_photo_url', 512)->nullable()->after('email');
                }
                if (!Schema::hasColumn('customers', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('profile_photo_url');
                }
                if (!Schema::hasColumn('customers', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
                if (!Schema::hasColumn('customers', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('longitude');
                }
            });

            // Try to make phone unique (skip on failure)
            try {
                $sm = DB::getDoctrineSchemaManager();
                $indexes = array_map(fn($i) => $i->getName(), $sm->listTableIndexes(DB::getTablePrefix() . 'customers'));
                if (!in_array('customers_phone_unique', $indexes) && !in_array('phone_unique', $indexes)) {
                    Schema::table('customers', function (Blueprint $table) {
                        $table->unique('phone');
                    });
                }
            } catch (\Exception $e) {
                // ignore
            }
        } else {
            // Create minimal customers table if missing
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 30)->unique();
                $table->string('full_name', 191)->nullable();
                $table->boolean('mobile_verified')->default(false);
                $table->string('email', 191)->nullable();
                $table->string('profile_photo_url', 512)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Create customer_addresses table
        if (!Schema::hasTable('customer_addresses')) {
            Schema::create('customer_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('type', 50)->nullable();
                $table->string('address_line1', 255)->nullable();
                $table->string('address_line2', 255)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('state', 120)->nullable();
                $table->string('zip_code', 20)->nullable();
                $table->string('country', 120)->default('India');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_addresses')) {
            Schema::dropIfExists('customer_addresses');
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $cols = [
                    'full_name','mobile_verified','email','profile_photo_url','latitude','longitude','is_active'
                ];
                foreach ($cols as $c) { if (Schema::hasColumn('customers', $c)) { try { $table->dropColumn($c); } catch (\Exception $e) { } } }
                try { $table->dropUnique(['phone']); } catch (\Exception $e) { }
            });
        }
    }
};
