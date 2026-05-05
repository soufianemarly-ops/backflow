<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds realistic test data for a Moroccan pastry shop.
 *
 * Creates:
 *   - 10 raw materials  (ns_raw_materials)
 *   - 5 NexoPOS products (nexopos_products + nexopos_products_unit_quantities)
 *   - 5 recipes          (ns_recipes + ns_recipe_ingredients)
 *
 * Prerequisites (must already exist):
 *   category_id = 5   → Viennoiseries
 *   unit_group  = 2   → standard unit group
 *   unit_id     = 4   → Pièce
 *   author_id   = 6   → admin user
 */
class PastisserieMorocaineSeeder extends Seeder
{
    private const CATEGORY_ID  = 5;
    private const UNIT_GROUP   = 2;
    private const UNIT_ID      = 4;
    private const AUTHOR_ID    = 6;

    public function run(): void
    {
        $now = now();

        // ----------------------------------------------------------------
        // 1. RAW MATERIALS
        // ----------------------------------------------------------------
        $rawMaterials = [
            [
                'name'           => 'Farine de blé',
                'unit'           => 'kg',
                'stock_quantity' => 50.00000,
                'alert_quantity' => 10.00000,
                'cost_per_unit'  => 8.00000,
                'description'    => 'Farine T55 pour pâtisserie',
            ],
            [
                'name'           => 'Beurre',
                'unit'           => 'kg',
                'stock_quantity' => 20.00000,
                'alert_quantity' => 5.00000,
                'cost_per_unit'  => 60.00000,
                'description'    => 'Beurre doux 82% MG',
            ],
            [
                'name'           => 'Sucre blanc',
                'unit'           => 'kg',
                'stock_quantity' => 30.00000,
                'alert_quantity' => 5.00000,
                'cost_per_unit'  => 10.00000,
                'description'    => 'Sucre cristallisé',
            ],
            [
                'name'           => 'Oeufs',
                'unit'           => 'pcs',
                'stock_quantity' => 200.00000,
                'alert_quantity' => 48.00000,
                'cost_per_unit'  => 2.00000,
                'description'    => 'Oeufs frais calibre M',
            ],
            [
                'name'           => 'Lait entier',
                'unit'           => 'L',
                'stock_quantity' => 20.00000,
                'alert_quantity' => 5.00000,
                'cost_per_unit'  => 15.00000,
                'description'    => 'Lait entier frais',
            ],
            [
                'name'           => 'Crème fraîche',
                'unit'           => 'L',
                'stock_quantity' => 10.00000,
                'alert_quantity' => 2.00000,
                'cost_per_unit'  => 40.00000,
                'description'    => 'Crème fraîche épaisse 30% MG',
            ],
            [
                'name'           => 'Chocolat noir',
                'unit'           => 'kg',
                'stock_quantity' => 10.00000,
                'alert_quantity' => 2.00000,
                'cost_per_unit'  => 80.00000,
                'description'    => 'Chocolat noir 70% cacao',
            ],
            [
                'name'           => 'Levure boulangère',
                'unit'           => 'kg',
                'stock_quantity' => 2.00000,
                'alert_quantity' => 0.50000,
                'cost_per_unit'  => 30.00000,
                'description'    => 'Levure sèche instantanée',
            ],
            [
                'name'           => 'Sel fin',
                'unit'           => 'kg',
                'stock_quantity' => 5.00000,
                'alert_quantity' => 1.00000,
                'cost_per_unit'  => 5.00000,
                'description'    => 'Sel de table',
            ],
            [
                'name'           => 'Extrait de vanille',
                'unit'           => 'L',
                'stock_quantity' => 0.50000,
                'alert_quantity' => 0.10000,
                'cost_per_unit'  => 500.00000,
                'description'    => 'Extrait naturel de vanille',
            ],
        ];

        $rawMaterialIds = [];

        foreach ( $rawMaterials as $rm ) {
            $existing = DB::table( 'ns_raw_materials' )->where( 'name', $rm[ 'name' ] )->first();

            if ( $existing ) {
                $rawMaterialIds[ $rm[ 'name' ] ] = $existing->id;
                $this->command->line( "  Raw material already exists: {$rm['name']} (id={$existing->id})" );
                continue;
            }

            $id = DB::table( 'ns_raw_materials' )->insertGetId( array_merge( $rm, [
                'author_id'  => self::AUTHOR_ID,
                'store_id'   => 0,
                'uuid'       => (string) Str::uuid(),
                'created_at' => $now,
                'updated_at' => $now,
            ] ) );

            $rawMaterialIds[ $rm[ 'name' ] ] = $id;
            $this->command->info( "  Created raw material: {$rm['name']} (id={$id})" );
        }

        // ----------------------------------------------------------------
        // 2. NEXOPOS PRODUCTS + UNIT QUANTITIES
        // ----------------------------------------------------------------
        $products = [
            [
                'name'       => 'Croissant au beurre',
                'sku'        => 'PAST-CROISSANT-001',
                'barcode'    => '6111000001001',
                'sale_price' => 8.00,
                'desc'       => 'Croissant pur beurre feuilleté',
            ],
            [
                'name'       => 'Pain au lait',
                'sku'        => 'PAST-PAINLAIT-001',
                'barcode'    => '6111000002001',
                'sale_price' => 5.00,
                'desc'       => 'Pain au lait moelleux',
            ],
            [
                'name'       => 'Brioche marocaine',
                'sku'        => 'PAST-BRIOCHE-001',
                'barcode'    => '6111000003001',
                'sale_price' => 15.00,
                'desc'       => 'Brioche à la fleur d\'oranger',
            ],
            [
                'name'       => 'Tarte au chocolat',
                'sku'        => 'PAST-TARTE-001',
                'barcode'    => '6111000004001',
                'sale_price' => 25.00,
                'desc'       => 'Tarte fondante au chocolat noir',
            ],
            [
                'name'       => 'Msemen',
                'sku'        => 'PAST-MSEMEN-001',
                'barcode'    => '6111000005001',
                'sale_price' => 3.00,
                'desc'       => 'Msemen marocain croustillant',
            ],
        ];

        $productIds = [];

        foreach ( $products as $prod ) {
            $existing = DB::table( 'nexopos_products' )->where( 'sku', $prod[ 'sku' ] )->first();

            if ( $existing ) {
                $productIds[ $prod[ 'name' ] ] = $existing->id;
                $this->command->line( "  Product already exists: {$prod['name']} (id={$existing->id})" );
                continue;
            }

            $productId = DB::table( 'nexopos_products' )->insertGetId( [
                'name'             => $prod[ 'name' ],
                'sku'              => $prod[ 'sku' ],
                'barcode'          => $prod[ 'barcode' ],
                'barcode_type'     => 'ean13',
                'description'      => $prod[ 'desc' ],
                'type'             => 'materialized',
                'product_type'     => 'product',
                'status'           => 'available',
                'stock_management' => 'enabled',
                'accurate_tracking'=> 0,
                'searchable'       => 1,
                'category_id'      => self::CATEGORY_ID,
                'unit_group'       => self::UNIT_GROUP,
                'tax_type'         => 'inclusive',
                'tax_group_id'     => null,
                'tax_value'        => 0,
                'on_expiration'    => 'prevent_sales',
                'expires'          => 0,
                'auto_cogs'        => 0,
                'pinned'           => 0,
                'position'         => 0,
                'thumbnail_id'     => 0,
                'parent_id'        => 0,
                'author_id'        => self::AUTHOR_ID,
                'uuid'             => (string) Str::uuid(),
                'created_at'       => $now,
                'updated_at'       => $now,
            ] );

            // Unit quantity entry (stock = 50, visible in POS)
            DB::table( 'nexopos_products_unit_quantities' )->insert( [
                'product_id'           => $productId,
                'unit_id'              => self::UNIT_ID,
                'quantity'             => 50,
                'low_quantity'         => 5,
                'stock_alert_enabled'  => 1,
                'sale_price'           => $prod[ 'sale_price' ],
                'sale_price_edit'      => $prod[ 'sale_price' ],
                'sale_price_net'       => $prod[ 'sale_price' ],
                'sale_price_gross'     => $prod[ 'sale_price' ],
                'sale_price_tax'       => 0,
                'wholesale_price'      => round( $prod[ 'sale_price' ] * 0.6, 2 ),
                'wholesale_price_edit' => round( $prod[ 'sale_price' ] * 0.6, 2 ),
                'wholesale_price_gross'=> round( $prod[ 'sale_price' ] * 0.6, 2 ),
                'wholesale_price_net'  => round( $prod[ 'sale_price' ] * 0.6, 2 ),
                'wholesale_price_tax'  => 0,
                'custom_price'         => 0,
                'custom_price_edit'    => 0,
                'custom_price_gross'   => 0,
                'custom_price_net'     => 0,
                'custom_price_tax'     => 0,
                'visible'              => 1,
                'is_weighable'         => 0,
                'cogs'                 => 0,
                'convert_unit_id'      => null,
                'uuid'                 => (string) Str::uuid(),
                'created_at'           => $now,
                'updated_at'           => $now,
            ] );

            $productIds[ $prod[ 'name' ] ] = $productId;
            $this->command->info( "  Created product: {$prod['name']} (id={$productId})" );
        }

        // ----------------------------------------------------------------
        // 3. RECIPES + INGREDIENTS
        // ----------------------------------------------------------------

        // Map ingredient names → raw material ids (kg quantities per unit produced)
        $recipes = [
            [
                'name'        => 'Recette Croissant au beurre',
                'product'     => 'Croissant au beurre',
                'description' => 'Pâte feuilletée levée pour 10 croissants',
                'ingredients' => [
                    [ 'Farine de blé',    0.200 ],  // 200g
                    [ 'Beurre',           0.080 ],  // 80g
                    [ 'Sucre blanc',      0.010 ],  // 10g
                    [ 'Lait entier',      0.100 ],  // 100ml
                    [ 'Levure boulangère',0.005 ],  // 5g
                    [ 'Sel fin',          0.003 ],  // 3g
                ],
            ],
            [
                'name'        => 'Recette Pain au lait',
                'product'     => 'Pain au lait',
                'description' => 'Pâte moelleuse pour 8 pains au lait',
                'ingredients' => [
                    [ 'Farine de blé',    0.150 ],  // 150g
                    [ 'Lait entier',      0.100 ],  // 100ml
                    [ 'Beurre',           0.030 ],  // 30g
                    [ 'Sucre blanc',      0.020 ],  // 20g
                    [ 'Levure boulangère',0.003 ],  // 3g
                    [ 'Sel fin',          0.002 ],  // 2g
                ],
            ],
            [
                'name'        => 'Recette Brioche marocaine',
                'product'     => 'Brioche marocaine',
                'description' => 'Brioche à la fleur d\'oranger pour 1 pièce',
                'ingredients' => [
                    [ 'Farine de blé',     0.200 ],  // 200g
                    [ 'Beurre',            0.100 ],  // 100g
                    [ 'Oeufs',             2.000 ],  // 2 œufs
                    [ 'Sucre blanc',       0.030 ],  // 30g
                    [ 'Lait entier',       0.050 ],  // 50ml
                    [ 'Levure boulangère', 0.005 ],  // 5g
                    [ 'Extrait de vanille',0.005 ],  // 5ml
                ],
            ],
            [
                'name'        => 'Recette Tarte au chocolat',
                'product'     => 'Tarte au chocolat',
                'description' => 'Tarte fondante pour 8 parts',
                'ingredients' => [
                    [ 'Chocolat noir',  0.200 ],  // 200g
                    [ 'Beurre',         0.100 ],  // 100g
                    [ 'Oeufs',          3.000 ],  // 3 œufs
                    [ 'Sucre blanc',    0.080 ],  // 80g
                    [ 'Farine de blé',  0.050 ],  // 50g
                    [ 'Crème fraîche',  0.100 ],  // 100ml
                ],
            ],
            [
                'name'        => 'Recette Msemen',
                'product'     => 'Msemen',
                'description' => 'Galette feuilletée pour 6 pièces',
                'ingredients' => [
                    [ 'Farine de blé', 0.200 ],  // 200g
                    [ 'Sel fin',       0.005 ],  // 5g
                    [ 'Beurre',        0.030 ],  // 30g
                    [ 'Sucre blanc',   0.010 ],  // 10g
                ],
            ],
        ];

        foreach ( $recipes as $rec ) {
            $existing = DB::table( 'ns_recipes' )->where( 'name', $rec[ 'name' ] )->first();

            if ( $existing ) {
                $this->command->line( "  Recipe already exists: {$rec['name']} (id={$existing->id})" );
                continue;
            }

            $productId = $productIds[ $rec[ 'product' ] ] ?? null;

            $recipeId = DB::table( 'ns_recipes' )->insertGetId( [
                'name'        => $rec[ 'name' ],
                'product_id'  => $productId,
                'description' => $rec[ 'description' ],
                'author_id'   => self::AUTHOR_ID,
                'uuid'        => (string) Str::uuid(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ] );

            $ingredientRows = [];
            foreach ( $rec[ 'ingredients' ] as [ $materialName, $quantity ] ) {
                $materialId = $rawMaterialIds[ $materialName ] ?? null;

                if ( ! $materialId ) {
                    $this->command->warn( "    Raw material not found: {$materialName}" );
                    continue;
                }

                $ingredientRows[] = [
                    'recipe_id'       => $recipeId,
                    'raw_material_id' => $materialId,
                    'quantity'        => $quantity,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            if ( ! empty( $ingredientRows ) ) {
                DB::table( 'ns_recipe_ingredients' )->insert( $ingredientRows );
            }

            $this->command->info( "  Created recipe: {$rec['name']} (id={$recipeId}, " . count( $ingredientRows ) . " ingredients)" );
        }

        $this->command->newLine();
        $this->command->info( 'PastisserieMorocaineSeeder completed.' );
    }
}
