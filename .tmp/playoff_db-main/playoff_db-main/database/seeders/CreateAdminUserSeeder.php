<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 檢查管理員用戶是否已存在
        $adminEmail = 'admin@example.com';
        $user = User::where('email', $adminEmail)->first();

        if (!$user) {
            // 創建管理員用戶
            $user = User::create([
                'name' => 'Admin',
                'email' => $adminEmail,
                'password' => bcrypt('password')
            ]);
        }

        // 檢查管理員角色是否已存在
        $roleName = 'admin';
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            // 創建管理員角色
            $role = Role::create(['name' => $roleName]);

            // 獲取所有權限
            $permissions = Permission::pluck('id', 'id')->all();

            // 將所有權限分配給管理員角色
            $role->syncPermissions($permissions);
        }

        // 確保用戶有管理員角色
        if (!$user->hasRole($roleName)) {
            $user->assignRole([$role->id]);
        }

        $this->command->info('管理員用戶和角色創建成功！');
    }
}
