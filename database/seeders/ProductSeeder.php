<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const PRODUCTS = [
        ['id' => '101', 'name' => 'Wireless Noise-Cancelling Headphones',  'sku' => 'WH-1000XM5',  'price' => 279.00, 'type' => 'simple',   'stock' => 'in_stock',       'qty' => 42],
        ['id' => '102', 'name' => 'Ergonomic Office Chair',                'sku' => 'CHAIR-PRO',   'price' => 449.00, 'type' => 'simple',   'stock' => 'in_stock',       'qty' => 15],
        ['id' => '103', 'name' => 'Standing Desk Converter',               'sku' => 'DESK-CNVRT',  'price' => 189.00, 'type' => 'simple',   'stock' => 'in_stock',       'qty' => 28],
        ['id' => '104', 'name' => 'Mechanical Keyboard TKL',               'sku' => 'KB-TKL-RED',  'price' => 129.00, 'type' => 'variable', 'stock' => 'in_stock',       'qty' => 67],
        ['id' => '105', 'name' => 'USB-C 4K Monitor 27"',                  'sku' => 'MON-27-4K',   'price' => 599.00, 'type' => 'simple',   'stock' => 'in_stock',       'qty' => 9],
        ['id' => '106', 'name' => 'Laptop Stand Aluminium',                'sku' => 'STAND-ALU',   'price' => 59.00,  'type' => 'simple',   'stock' => 'in_stock',       'qty' => 88],
        ['id' => '107', 'name' => 'LED Desk Lamp with USB Charging',       'sku' => 'LAMP-LED-USB', 'price' => 49.00, 'type' => 'simple',   'stock' => 'in_stock',       'qty' => 54],
        ['id' => '108', 'name' => 'Webcam 1080p with Privacy Cover',       'sku' => 'CAM-1080P',   'price' => 89.00,  'type' => 'simple',   'stock' => 'in_stock',       'qty' => 31],
        ['id' => '109', 'name' => 'Portable SSD 1TB',                      'sku' => 'SSD-1TB-USB', 'price' => 109.00, 'type' => 'simple',   'stock' => 'in_stock',       'qty' => 23],
        ['id' => '110', 'name' => 'Wrist Rest Gel Pad',                    'sku' => 'WRIST-GEL',   'price' => 22.00,  'type' => 'simple',   'stock' => 'in_stock',       'qty' => 112],
        ['id' => '111', 'name' => 'Cable Management Kit',                  'sku' => 'CABLE-KIT',   'price' => 18.00,  'type' => 'simple',   'stock' => 'out_of_stock',   'qty' => 0],
        ['id' => '112', 'name' => 'Dual Monitor Arm',                      'sku' => 'MON-ARM-D',   'price' => 149.00, 'type' => 'simple',   'stock' => 'in_stock',       'qty' => 19],
        ['id' => '113', 'name' => 'Wireless Charging Pad 15W',             'sku' => 'CHG-15W',     'price' => 35.00,  'type' => 'simple',   'stock' => 'in_stock',       'qty' => 76],
        ['id' => '114', 'name' => 'Bluetooth Trackball Mouse',             'sku' => 'MOUSE-TRK',   'price' => 99.00,  'type' => 'simple',   'stock' => 'in_stock',       'qty' => 37],
        ['id' => '115', 'name' => 'Anti-Glare Screen Protector 27"',       'sku' => 'SCRN-27-AG',  'price' => 29.00,  'type' => 'simple',   'stock' => 'on_backorder',   'qty' => 0],
        ['id' => '116', 'name' => 'Mesh Wi-Fi System (3-pack)',            'sku' => 'WIFI-MESH3',  'price' => 249.00, 'type' => 'grouped',  'stock' => 'in_stock',       'qty' => 11],
        ['id' => '117', 'name' => 'Smart Power Strip 6-outlet',            'sku' => 'POWER-6',     'price' => 44.00,  'type' => 'simple',   'stock' => 'in_stock',       'qty' => 63],
        ['id' => '118', 'name' => 'Noise-Cancelling Earbuds TWS',          'sku' => 'EARB-TWS',    'price' => 149.00, 'type' => 'variable', 'stock' => 'in_stock',       'qty' => 45],
        ['id' => '119', 'name' => 'Foldable Keyboard Travel',              'sku' => 'KB-FOLD',     'price' => 69.00,  'type' => 'simple',   'stock' => 'out_of_stock',   'qty' => 0],
        ['id' => '120', 'name' => 'USB Hub 7-Port USB 3.0',                'sku' => 'HUB-7P',      'price' => 39.00,  'type' => 'simple',   'stock' => 'in_stock',       'qty' => 94],
    ];

    public function run(): void
    {
        $workspace = Workspace::where('slug', 'demo-store')->first();
        $stores    = Store::where('workspace_id', $workspace->id)->get();

        foreach ($stores as $store) {
            foreach (self::PRODUCTS as $p) {
                Product::create([
                    'workspace_id'        => $workspace->id,
                    'store_id'            => $store->id,
                    'external_id'         => $p['id'],
                    'name'                => $p['name'],
                    'sku'                 => $p['sku'],
                    'price'               => $p['price'],
                    'status'              => 'publish',
                    'stock_status'        => $p['stock'],
                    'stock_quantity'      => $p['qty'],
                    'product_type'        => $p['type'],
                    'platform_updated_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
}
