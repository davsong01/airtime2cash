<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu = [
            'Dashboard',
            'Announcement',
            'Catalogue',
            'API Provider',
            'Categories',
            "Email Management",
            'Products',
            'Customers',
            'All Customers',
            'Active Customers',
            'Suspended Customers',
            'Blacklisted Customers',
            'Customer Levels',
            'User Management',
            'All Admins',
            'All Roles',
            'All Permissions',
            'Financials',
            'Product Purchase Log',
            'Wallet Funding Log',
            'Wallet Log',
            'Earnings Log',
            'Credit Customer',
            'Debit Customer',
            'Reserved Account Numbers',
            'My Profile',
            'Callback Analysis',
            'KYC Management',
            'Payment Gateway Settings',
            'General Settings',
            'Email Setup'
        ];

        $permissions = [
            'product.index',
            'product.show',
            'product.edit',
            'product.update',
            'product.destroy',
            'role.show',
            'role.index',
            'role.edit',
            'role.update',
            'role.destroy',
            'role.create',
    
            'category.show',
            'category.index',
            'category.edit',
            'category.update',
            'category.destroy',

            'billerlog.index',
            'billerlog.show',
            'billerlog.edit',
            'billerlog.update',
            'billerlog.destroy',

            'customer-blacklist.show',
            'customer-blacklist.edit',
            'customer-blacklist.update',
            'customer-blacklist.destroy',

            'announcement.index',
            'announcement.show',
            'announcement.edit',
            'announcement.update',
            'announcement.destroy',
        
            'api.show',
            'api.index',
            'api.edit',
            'api.update',
            'api.destroy',

            'customerlevel.show',
            'customerlevel.show',
            'customerlevel.edit',
            'customerlevel.update',
            'customerlevel.destroy',

            'duplicate.product',
            'api.balance',

            'black.list.status',
            'admin.trans',
            'admin.walletlog',
            'admin.walletfundinglog',
            'admin.earninglog',
            'admin.credit.customer',
            'admin.debit.customer',
            'admin.process.credit.debit',
            'admin.verifybiller',
            'admin.verify.post',
            'admin.kyc',
            'admin.reserved.accounts',
            'account.transactions',
            'callback.analysis',
            'reserved_account.delete',
            'admin.single.transaction.view',
            'admin.query.wallet',
            'admin.requery.transaction',
            'customers',
            'customers.active',
            'customers.suspended',
            'customers.edit',
            'customers.update',
            'variations.pull',
            'variations.update',
            'manual.variations.add',
            'variation.delete',
            'create.reserved.account',
            'admins',
            'newAdmin',
            'adminSave',
            'viewAdmin',
            'updateAdmin',
            'settings.edit',
            'settings.update',
            'transaction.verify',
            'paymentgateway.index',
            'emails.index',
            'emails.update',
            'emails.pending',
            'emails.resend',
            'emails.sweep',

            'permission.show',
            'permission.index',
            'permission.edit',
            'permission.update',
            'permission.destroy',
            'permission.create',
            'permission.store',
            'emails-send',
            'customerlevel.index',
            'role.store',
            'emails.destroy',
            'announcement.store',
            'email.setup',
            'email.setup.update',

            'paymentgateway.show',
            'paymentgateway.edit',
            'paymentgateway.update',
            'paymentgateway.destroy',
            // 'paymentgateway.create',
            'paymentgateway.store',
            // 'product.create',
            // 'category.create',
            'category.repull',
            'category.pull',
            'product.repull',
            'product.pull',
            'customerlevel.create',
            'admin.transaction.pin.reset',
            'admin.password.reset'
        ];

        // RolePermission::truncate();
        foreach($menu as $key=>$value){
            RolePermission::updateOrCreate(
                [
                    'name' => $value,
                    'route' => $value,
                    'type' => 'menu',
                ],[
                'name' => $value,
                'route' => $value,
                'type' => 'menu',
                'status' => 'active',
            ]);
        }

        foreach ($permissions as $key => $value) {
            RolePermission::updateOrCreate(
                [
                    'name' => $value,
                    'route' => $value,
                    'type' => 'link',
                ],[
                'name' => $value,
                'route' => $value,
                'type' => 'link',
                'status' => 'active',
            ]);
        }

        Role::updateOrCreate(['name' => 'Admin','status' => 'active'],[
            'name' => 'Admin',
            'permissions' => "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,115,116,117,118,119,120,121",
            'status' => 'active'
        ]);
    }
}
