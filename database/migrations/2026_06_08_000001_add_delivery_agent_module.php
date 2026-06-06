<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivery_agent_id')) {
                $table->unsignedBigInteger('delivery_agent_id')->nullable()->after('user_id');
                $table->index('delivery_agent_id', 'orders_del_agent_idx');
                $table->foreign('delivery_agent_id', 'orders_del_agent_fk')->references('id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'delivery_status')) {
                $table->string('delivery_status', 32)->default('pending')->after('status');
                $table->index('delivery_status', 'orders_del_status_idx');
            }

            if (! Schema::hasColumn('orders', 'out_for_delivery_at')) {
                $table->timestamp('out_for_delivery_at')->nullable()->after('shipped_at');
            }

            if (! Schema::hasColumn('orders', 'delivery_failed_at')) {
                $table->timestamp('delivery_failed_at')->nullable()->after('delivered_at');
            }

            if (! Schema::hasColumn('orders', 'delivery_failure_reason')) {
                $table->string('delivery_failure_reason', 120)->nullable()->after('delivery_failed_at');
            }

            if (! Schema::hasColumn('orders', 'delivery_note')) {
                $table->text('delivery_note')->nullable()->after('delivery_failure_reason');
            }

            if (! Schema::hasColumn('orders', 'delivered_by_id')) {
                $table->unsignedBigInteger('delivered_by_id')->nullable()->after('delivered_at');
                $table->index('delivered_by_id', 'orders_del_by_idx');
                $table->foreign('delivered_by_id', 'orders_del_by_fk')->references('id')->on('users')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('order_delivery_events')) {
            Schema::create('order_delivery_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('event_type', 50);
                $table->string('old_status', 50)->nullable();
                $table->string('new_status', 50)->nullable();
                $table->text('note')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'created_at'], 'ode_order_created_idx');
                $table->index(['event_type', 'created_at'], 'ode_type_created_idx');
                $table->foreign('order_id', 'ode_order_fk')->references('id')->on('orders')->cascadeOnDelete();
                $table->foreign('user_id', 'ode_user_fk')->references('id')->on('users')->nullOnDelete();
            });
        }

        $this->seedDeliveryRoleAndPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('order_delivery_events');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivered_by_id')) {
                try { $table->dropForeign('orders_del_by_fk'); } catch (Throwable $e) {}
                try { $table->dropIndex('orders_del_by_idx'); } catch (Throwable $e) {}
                $table->dropColumn('delivered_by_id');
            }

            foreach (['delivery_note', 'delivery_failure_reason', 'delivery_failed_at', 'out_for_delivery_at'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('orders', 'delivery_status')) {
                try { $table->dropIndex('orders_del_status_idx'); } catch (Throwable $e) {}
                $table->dropColumn('delivery_status');
            }

            if (Schema::hasColumn('orders', 'delivery_agent_id')) {
                try { $table->dropForeign('orders_del_agent_fk'); } catch (Throwable $e) {}
                try { $table->dropIndex('orders_del_agent_idx'); } catch (Throwable $e) {}
                $table->dropColumn('delivery_agent_id');
            }
        });
    }

    private function seedDeliveryRoleAndPermissions(): void
    {
        if (! class_exists(Permission::class) || ! class_exists(Role::class)) {
            return;
        }

        $guardName = config('fb_permissions.guard', config('permission.default_guard', 'web')) ?: 'web';

        $permissions = [
            'view assigned deliveries',
            'update assigned delivery status',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName,
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'DeliveryAgent',
            'guard_name' => $guardName,
        ]);

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
