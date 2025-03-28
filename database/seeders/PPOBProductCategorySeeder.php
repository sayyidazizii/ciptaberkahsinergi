<?php

namespace Database\Seeders;

use App\Models\PPOBProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PPOBProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PPOBProductCategory::create([
                [
                    "ppob_product_category_code" => "DANA",
                    "ppob_product_category_name" => "Topup Dana"
                ],
                [
                    "ppob_product_category_code" => "GRAB",
                    "ppob_product_category_name" => "Topup OVO"
                ],
                [
                    "ppob_product_category_code" => "GOJEK",
                    "ppob_product_category_name" => "Topup GoPay"
                ],
                [
                    "ppob_product_category_code" => "ETOLL",
                    "ppob_product_category_name" => "Topup E-Toll"
                ],
                [
                    "ppob_product_category_code" => "GAME",
                    "ppob_product_category_name" => "Voucher Game"
                ],
                [
                    "ppob_product_category_code" => "PULSA",
                    "ppob_product_category_name" => "TopUp Pulsa"
                ],
                [
                    "ppob_product_category_code" => "PDAM",
                    "ppob_product_category_name" => "Pembayaran PDAM"
                ],
                [
                    "ppob_product_category_code" => "TELKOM",
                    "ppob_product_category_name" => "Pembayaran Telkom"
                ],
                [
                    "ppob_product_category_code" => "1pay",
                    "ppob_product_category_name" => "Pulsa Prabayar"
                ],
                [
                    "ppob_product_category_code" => "PLNPREPAID",
                    "ppob_product_category_name" => "TopUp Listrik"
                ],
                [
                    "ppob_product_category_code" => "PLNPOSTPAID",
                    "ppob_product_category_name" => "Pembayaran Listrik"
                ],
                [
                    "ppob_product_category_code" => "BPJSKES",
                    "ppob_product_category_name" => "BPJS Kesehatan"
                ],
                [
                    "ppob_product_category_code" => "SHOPEE",
                    "ppob_product_category_name" => "Topup Shopee"
                ]
        ]);
    }
}
