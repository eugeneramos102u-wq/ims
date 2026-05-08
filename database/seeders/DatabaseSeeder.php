<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DocumentSequence;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PaymentTerm;
use App\Models\Role;
use App\Models\AdjustmentType;
use App\Models\BankAccount;
use App\Models\Salesperson;
use App\Models\Supplier;
use App\Models\Uom;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ──
        $roles = [
            ['role_name' => 'Admin',   'permissions' => ['all' => true]],
            ['role_name' => 'Manager', 'permissions' => ['confirm' => true, 'override_price' => true]],
            ['role_name' => 'Cashier', 'permissions' => ['create_draft' => true]],
            ['role_name' => 'Viewer',  'permissions' => ['read_only' => true]],
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['role_name' => $r['role_name']], ['permissions' => $r['permissions']]);
        }

        // ── Document sequences ──
        $sequences = [
            ['seq_key' => 'item',     'prefix' => 'ITM-',  'next_number' => 1, 'padding' => 4],
            ['seq_key' => 'customer', 'prefix' => 'CUS-',  'next_number' => 1, 'padding' => 4],
            ['seq_key' => 'sp',       'prefix' => 'SP-',   'next_number' => 1, 'padding' => 4],
            ['seq_key' => 'so',       'prefix' => 'SO-',   'next_number' => 1, 'padding' => 6],
            ['seq_key' => 'invoice',  'prefix' => 'INV-',  'next_number' => 1, 'padding' => 6],
            ['seq_key' => 'or',       'prefix' => 'OR-',   'next_number' => 1, 'padding' => 6],
            ['seq_key' => 'supplier', 'prefix' => 'SUP-',  'next_number' => 1, 'padding' => 4],
            ['seq_key' => 'po',       'prefix' => 'PO-',   'next_number' => 1, 'padding' => 6],
            ['seq_key' => 'grn',      'prefix' => 'SR-',   'next_number' => 1, 'padding' => 6],
            ['seq_key' => 'deposit',  'prefix' => 'DEP-',  'next_number' => 1, 'padding' => 6],
            ['seq_key' => 'adj',      'prefix' => 'ADJ-',  'next_number' => 1, 'padding' => 6],
        ];
        foreach ($sequences as $s) {
            DocumentSequence::firstOrCreate(['seq_key' => $s['seq_key']], $s);
        }

        // ── Default users (delegated to UserSeeder) ──
        $this->call(UserSeeder::class);
        $manager = User::where('username', 'manager')->first();
        $cashier = User::where('username', 'cashier')->first();

        // ── UoM ──
        $uoms = [
            ['BAG', 'Bag / Sack',   'Count'],
            ['PCS', 'Pieces',       'Count'],
            ['SHT', 'Sheet',        'Area'],
            ['CAN', 'Can / Tin',    'Count'],
            ['BOX', 'Box / Carton', 'Count'],
            ['MTR', 'Meter',        'Length'],
            ['LTR', 'Liter',        'Volume'],
            ['KG',  'Kilogram',     'Weight'],
        ];
        foreach ($uoms as [$code, $name, $cat]) {
            Uom::firstOrCreate(['uom_code' => $code], ['uom_name' => $name, 'category' => $cat]);
        }

        // ── Item Categories ──
        $cats = [
            ['Construction',      '🏗', '#60a5fa'],
            ['Hardware',          '🔧', '#f59e0b'],
            ['Plumbing',          '🚿', '#38bdf8'],
            ['Electrical',        '⚡', '#facc15'],
            ['Paint & Coatings',  '🎨', '#f87171'],
            ['Wood & Lumber',     '🪵', '#a78bfa'],
            ['Safety & PPE',      '🦺', '#34d399'],
        ];
        foreach ($cats as [$name, $icon, $color]) {
            ItemCategory::firstOrCreate(['category_name' => $name], ['icon' => $icon, 'color' => $color]);
        }

        // ── Payment Terms ──
        $terms = [
            ['COD',     'Cash on Delivery', 0,  0,  0, 'both'],
            ['NET15',   'Net 15 Days',      15, 0,  0, 'both'],
            ['NET30',   'Net 30 Days',      30, 0,  0, 'both'],
            ['NET60',   'Net 60 Days',      60, 0,  0, 'sales'],
            ['2-10-30', '2/10 Net 30',      30, 2, 10, 'sales'],
        ];
        foreach ($terms as [$code, $name, $due, $disc, $discDays, $applies]) {
            PaymentTerm::firstOrCreate(['term_code' => $code], [
                'term_name' => $name,
                'due_days' => $due,
                'discount_pct' => $disc,
                'discount_days' => $discDays,
                'applies_to' => $applies,
            ]);
        }

        // ── Sample Items ──
        $construction = ItemCategory::where('category_name', 'Construction')->first();
        $plumbing     = ItemCategory::where('category_name', 'Plumbing')->first();
        $paint        = ItemCategory::where('category_name', 'Paint & Coatings')->first();
        $wood         = ItemCategory::where('category_name', 'Wood & Lumber')->first();

        $bag = Uom::where('uom_code', 'BAG')->first();
        $pcs = Uom::where('uom_code', 'PCS')->first();
        $can = Uom::where('uom_code', 'CAN')->first();
        $sht = Uom::where('uom_code', 'SHT')->first();

        $items = [
            ['ITM-0001', 'Cement 40kg',         $construction, $bag, 285,  320,  350,  248, 50],
            ['ITM-0002', 'Steel Bar 12mm',      $construction, $pcs, 395,  450,  480,  120, 20],
            ['ITM-0003', 'Steel Bar 10mm',      $construction, $pcs, 285,  340,  380,   80, 20],
            ['ITM-0004', 'PVC Elbow 3"',        $plumbing,     $pcs,  24,   32,   38,   15,  5],
            ['ITM-0005', 'PVC Pipe 4" x 3m',    $plumbing,     $pcs, 240,  290,  320,   42, 10],
            ['ITM-0006', 'Paint 4L White',      $paint,        $can, 480,  560,  620,   62, 10],
            ['ITM-0007', 'Plywood 3/4" Marine', $wood,         $sht, 980, 1100, 1200,   34,  5],
            ['ITM-0008', 'Plywood 1/4" Ord.',   $wood,         $sht, 320,  380,  420,   28,  5],
        ];
        foreach ($items as [$code, $name, $c, $u, $cost, $whole, $retail, $qty, $reorder]) {
            Item::firstOrCreate(['item_code' => $code], [
                'item_name' => $name,
                'category_id' => $c->id,
                'uom_id' => $u->id,
                'unit_cost' => $cost,
                'wholesale_price' => $whole,
                'retail_price' => $retail,
                'qty_on_hand' => $qty,
                'reorder_level' => $reorder,
                'tax_rate_pct' => 12,
            ]);
        }
        DocumentSequence::where('seq_key', 'item')->update(['next_number' => 9]);

        // ── Customers ──
        $net30 = PaymentTerm::where('term_code', 'NET30')->first();
        $net15 = PaymentTerm::where('term_code', 'NET15')->first();

        $customers = [
            ['CUS-0001', 'SM Prime Holdings',     'Ana Rodriguez',   'ana@smprime.local',    '09171110001', 500000, $net30],
            ['CUS-0002', 'Robinsons Retail',      'Mark Lim',        'mark@robinsons.local', '09171110002', 300000, $net15],
            ['CUS-0003', 'Puregold Price Club',   'Elena Cruz',      'elena@puregold.local', '09171110003', 200000, $net30],
            ['CUS-0004', 'AllHome Corp',          'Pedro Reyes',     'pedro@allhome.local',  '09171110004', 150000, $net15],
        ];
        foreach ($customers as [$code, $name, $contact, $email, $phone, $credit, $term]) {
            Customer::firstOrCreate(['customer_code' => $code], [
                'company_name' => $name,
                'contact_person' => $contact,
                'email' => $email,
                'phone' => $phone,
                'credit_limit' => $credit,
                'payment_term_id' => $term?->id,
            ]);
        }
        DocumentSequence::where('seq_key', 'customer')->update(['next_number' => 5]);

        // ── Salespersons ──
        $salespersons = [
            ['SP-0001', 'Juan de la Cruz', $manager?->id, '09171110001', 'NCR — Metro Manila', 2.50],
            ['SP-0002', 'Maria Santos',    $cashier?->id, '09171110002', 'CALABARZON',         2.00],
        ];
        foreach ($salespersons as [$code, $name, $userId, $phone, $territory, $rate]) {
            Salesperson::firstOrCreate(['sp_code' => $code], [
                'full_name' => $name,
                'user_id' => $userId,
                'phone' => $phone,
                'territory' => $territory,
                'commission_rate_pct' => $rate,
            ]);
        }
        DocumentSequence::where('seq_key', 'sp')->update(['next_number' => 3]);

        // ── Suppliers ──
        $cod = PaymentTerm::where('term_code', 'COD')->first();

        $suppliers = [
            ['SUP-0001', 'ABC Trading Co.',     'Carlo Mendoza',  'sales@abctrading.local', '09181110001', $net30, 'BDO',      '0010-1234-5678'],
            ['SUP-0002', 'XYZ Supply Inc.',     'Lourdes Garcia', 'sales@xyzsupply.local',  '09181110002', $cod,   'BPI',      '0030-9876-9012'],
            ['SUP-0003', 'Mega Hardware Corp',  'Roberto Tan',    'sales@megahw.local',     '09181110003', $net15, 'MetroBank','0070-2233-3456'],
            ['SUP-0004', 'Premier Lumber Co.',  'Ana de Leon',    'orders@premier.local',   '09181110004', $net30, 'BDO',      '0010-5566-7788'],
        ];
        foreach ($suppliers as [$code, $name, $contact, $email, $phone, $term, $bankName, $bankAcc]) {
            Supplier::firstOrCreate(['supplier_code' => $code], [
                'company_name' => $name,
                'contact_person' => $contact,
                'email' => $email,
                'phone' => $phone,
                'payment_term_id' => $term?->id,
                'bank_name' => $bankName,
                'bank_account' => $bankAcc,
            ]);
        }
        DocumentSequence::where('seq_key', 'supplier')->update(['next_number' => 5]);

        // ── Adjustment Types ──
        $adjTypes = [
            ['BBAL',  'Beginning Balance', 'BOTH', 'Sets the opening qty during system setup or annual cutover. qty_before is treated as 0; qty_after = qty_adjusted.'],
            ['ADJIN', 'Adjustment In',     'IN',   'Increases qty_on_hand. Use for found stock, returns from internal use, etc.'],
            ['ADJOUT','Adjustment Out',    'OUT',  'Decreases qty_on_hand. Use for samples, internal consumption, missing items at count.'],
            ['WOFF',  'Write-off',         'OUT',  'Removes damaged, expired, or unusable stock. Treated as a loss for accounting.'],
        ];
        foreach ($adjTypes as [$code, $name, $direction, $description]) {
            AdjustmentType::firstOrCreate(['code' => $code], [
                'type_name' => $name,
                'direction' => $direction,
                'description' => $description,
            ]);
        }

        // ── Company Bank Accounts (deposits land here) ──
        $banks = [
            ['BDO', '1234567890', 'InventoryPro Trading Inc.', 'Makati Branch', true],
            ['BPI', '9876543210', 'InventoryPro Trading Inc.', 'Ortigas Branch', false],
        ];
        foreach ($banks as [$bank, $acc, $name, $branch, $default]) {
            BankAccount::firstOrCreate(
                ['bank_name' => $bank, 'account_number' => $acc],
                ['account_name' => $name, 'branch' => $branch, 'is_default' => $default]
            );
        }
    }
}
