<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CustomQuery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        DB::statement("ALTER TABLE  `categories` ADD `parent_id` INT NOT NULL DEFAULT '0' AFTER `id`");         

       
        DB::statement("CREATE TABLE IF NOT EXISTS `quotations` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            `address_id` bigint(20) UNSIGNED DEFAULT NULL,
            `client_id` bigint(20) UNSIGNED DEFAULT NULL,
            `restorant_id` bigint(20) UNSIGNED NOT NULL,
            `delivery_price` double(8,2) NOT NULL,
            `order_price` double(8,2) NOT NULL,
            `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `payment_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `comment` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
            `lat` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `lng` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `time_to_prepare` int(11) DEFAULT NULL,
            `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `whatsapp_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
            `deleted_at` timestamp NULL DEFAULT NULL,
            `md` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            KEY `orders_address_id_foreign` (`address_id`),
            KEY `orders_client_id_foreign` (`client_id`),
            KEY `orders_restorant_id_foreign` (`restorant_id`),
            KEY `orders_employee_id_foreign` (`employee_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
           
           DB::statement("CREATE TABLE IF NOT EXISTS `quotation_has_items` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            `quotation_id` bigint(20) UNSIGNED NOT NULL,
            `item_id` bigint(20) UNSIGNED NOT NULL,
            `qty` int(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `order_has_items_item_id_foreign` (`item_id`),
            KEY `order_has_items_quotation_id_foreign` (`quotation_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

           DB::statement("CREATE TABLE IF NOT EXISTS `quotation_has_status` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            `quotation_id` bigint(20) UNSIGNED NOT NULL,
            `status_id` bigint(20) UNSIGNED NOT NULL,
            `user_id` bigint(20) UNSIGNED NOT NULL,
            `comment` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
            PRIMARY KEY (`id`),
            KEY `order_has_status_status_id_foreign` (`status_id`),
            KEY `order_has_status_user_id_foreign` (`user_id`),
            KEY `order_has_status_quotation_id_foreign` (`quotation_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
