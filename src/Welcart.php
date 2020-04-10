<?php
namespace Helper;

use Codeception\Module;

class Welcart extends Module
{

    /**
     * **HOOK** executed before test
     *
     * @param \Codeception\TestInterface $test
     */
    public function _before(\Codeception\TestInterface $test): void
    {
        global $usces;
    
        update_option('timezone_string', 'Asia/Tokyo');
        $usces->set_default_page();
        $usces->set_default_categories();
        $usces->create_table();
        $usces->update_table();
        $rets07 = usces_upgrade_07();
        if ($rets07) {
            $rets11 = usces_upgrade_11();
        }
        if ($rets11) {
            $rets14 = usces_upgrade_14();
        }
        if ($rets14) {
            $rets141 = usces_upgrade_141();
        }
        if ($rets141) {
            $rets143 = usces_upgrade_143();
        }
        $usces->update_options();
        usces_schedule_event();
    }

    /**
     * **HOOK** executed after test
     *
     * @param \Codeception\TestInterface $test
     */
    public function _after(\Codeception\TestInterface $test): void
    {
        global $wpdb;

        $access = $wpdb->prefix . 'usces_access';
        $log = $wpdb->prefix . 'usces_log';

        $cmeta = $wpdb->prefix . 'usces_continuation_meta';
        $memmetat = $wpdb->prefix . 'usces_member_meta';
        $ordermetat = $wpdb->prefix . 'usces_order_meta';
        $ordercartmetat = $wpdb->prefix . 'usces_ordercart_meta';

        $cont = $wpdb->prefix . 'usces_continuation';
        $memt = $wpdb->prefix . 'usces_member';
        $ordert = $wpdb->prefix . 'usces_order';
        $ordercartt = $wpdb->prefix . 'usces_ordercart';

        $wpdb->query("SET foreign_key_checks = 0");
        $wpdb->query("DROP TABLE IF EXISTS {$log}, {$access}");
        $wpdb->query("DROP TABLE IF EXISTS {$ordercartmetat}, {$ordermetat}, {$memmetat}, {$cmeta}");
        $wpdb->query("DROP TABLE IF EXISTS {$ordert}, {$memt}, {$cont}, {$ordercartt}");
        $wpdb->query("SET foreign_key_checks = 1");
    }

    /**
     * Adds an arbitrary number of Welcart test members
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $members
     * @return void
     */
    public function createTestMembers(array $members): void
    {
        global $wpdb;

        $index = 0;
        $salt = usces_get_salt('', 1);
        $pass = usces_get_hash('password', $salt);
        $member_table = $wpdb->prefix . 'usces_member';
        foreach ($members as $mem) {
            $q = $wpdb->prepare(
                "INSERT INTO {$member_table} (
                    `mem_email`, `mem_pass`, `mem_status`, `mem_cookie`, `mem_point`,
                    `mem_name1`, `mem_name2`, `mem_name3`, `mem_name4`, `mem_zip`, `mem_pref`,
                    `mem_address1`, `mem_address2`, `mem_address3`, `mem_tel`, `mem_fax`, `mem_delivery_flag`,
                    `mem_registered`, `mem_nicename`
                )
                VALUES (%s, %s, %d, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s)",
                isset($mem['mem_email']) ? $mem['mem_email'] : "test{$index}@gmail.com",
                isset($mem['mem_pass']) ? $mem['mem_pass'] : $pass,
                isset($mem['mem_status']) ? $mem['mem_status'] : '0',
                isset($mem['mem_cookie']) ? $mem['mem_cookie'] : '',
                isset($mem['mem_point']) ? $mem['mem_point'] : 0,
                isset($mem['mem_name1']) ? $mem['mem_name1'] : "Man{$index}",
                isset($mem['mem_name2']) ? $mem['mem_name2'] : "Test{$index}",
                isset($mem['mem_name3']) ? $mem['mem_name3'] : '',
                isset($mem['mem_name4']) ? $mem['mem_name4'] : '',
                isset($mem['mem_zip']) ? $mem['mem_zip'] : '1100011',
                isset($mem['mem_pref']) ? $mem['mem_pref'] : '高知県',
                isset($mem['mem_address1']) ? $mem['mem_address1'] : '横浜市上北町',
                isset($mem['mem_address2']) ? $mem['mem_address2'] : '3-1',
                isset($mem['mem_address3']) ? $mem['mem_address3'] : '',
                isset($mem['mem_tel']) ? $mem['mem_tel'] : '08011112222',
                isset($mem['mem_fax']) ? $mem['mem_fax'] : '',
                isset($mem['mem_delivery_flag']) ? $mem['mem_delivery_flag'] : '0',
                isset($mem['mem_registered']) ? $mem['mem_registered'] : '0000-00-00 00:00:00',
                isset($mem['mem_nicename']) ? $mem['mem_nicename'] : '',
            );

            $index++;
            $wpdb->query($q);
        }
    }

    /**
     * Adds an arbitrary number of Welcart test orders
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $orders
     * @return void
     */
    public function createTestOrders(array $orders): void
    {
        global $wpdb;

        $order_table_name = $wpdb->prefix . 'usces_order';
        $index = 0;
        foreach ($orders as $order) {
            $query = $wpdb->prepare(
                "INSERT INTO {$order_table_name} (
                    `mem_id`, `order_email`, `order_name1`, `order_name2`, `order_name3`, `order_name4`, 
                    `order_zip`, `order_pref`, `order_address1`, `order_address2`, `order_address3`, 
                    `order_tel`, `order_fax`, `order_delivery`, `order_cart`, `order_note`, `order_delivery_method`, `order_delivery_date`, `order_delivery_time`, 
                    `order_payment_name`, `order_condition`, `order_item_total_price`, `order_getpoint`, `order_usedpoint`, `order_discount`, 
                    `order_shipping_charge`, `order_cod_fee`, `order_tax`, `order_date`, `order_modified`, `order_status`, `order_delidue_date`
                ) 
                VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %f, %d, %d, %f, %f, %f, %f, %s, %s, %s, %s)",
                isset($order['mem_id']) ? $order['mem_id'] : 0,
                isset($order['order_email']) ? $order['order_email'] : "test{$index}@gmail.com",
                isset($order['order_name1']) ? $order['order_name1'] : "Mega{$index}",
                isset($order['order_name2']) ? $order['order_name2'] : "Man{$index}",
                isset($order['order_name3']) ? $order['order_name3'] : '',
                isset($order['order_name4']) ? $order['order_name4'] : '',
                isset($order['order_zip']) ? $order['order_zip'] : '1100011',
                isset($order['order_pref']) ? $order['order_pref'] : '鳥取県',
                isset($order['order_address1']) ? $order['order_address1'] : '鳥取県',
                isset($order['order_address2']) ? $order['order_address2'] : '横浜市上北町',
                isset($order['order_address3']) ? $order['order_address3'] : '9-2',
                isset($order['order_tel']) ? $order['order_tel'] : '08011111111',
                isset($order['order_fax']) ? $order['order_fax'] : '',
                isset($order['order_delivery']) ? $order['order_delivery'] : 'a:21:{s:5:"name1";s:4:"Test";s:5:"name2";s:3:"Man";s:5:"name3";s:0:"";s:5:"name4";s:0:"";s:7:"zipcode";s:7:"1100011";s:8:"address1";s:18:"横浜市上北町";s:8:"address2";s:3:"9-2";s:8:"address3";s:0:"";s:3:"tel";s:11:"08011111111";s:3:"fax";s:0:"";s:7:"country";s:2:"JP";s:4:"pref";s:9:"鳥取県";s:13:"delivery_flag";s:1:"0";s:2:"ID";s:4:"1001";s:12:"mailaddress1";s:24:"test@gmail.com";s:12:"mailaddress2";s:24:"test@gmail.com";s:5:"point";s:1:"0";s:8:"delivery";s:0:"";s:10:"registered";s:19:"2020-03-16 02:51:38";s:8:"nicename";s:0:"";s:6:"status";s:1:"0";}',
                isset($order['order_cart']) ? $order['order_cart'] : 'a:1:{i:0;a:7:{s:6:"serial";s:37:"a:1:{i:37;a:1:{s:9:"code1%3A1";i:0;}}";s:7:"post_id";i:37;s:3:"sku";s:9:"code1%3A1";s:7:"options";a:0:{}s:5:"price";s:4:"7000";s:8:"quantity";i:1;s:7:"advance";s:0:"";}}',
                isset($order['order_note']) ? $order['order_note'] : '',
                isset($order['order_delivery_method']) ? $order['order_delivery_method'] : '1',
                isset($order['order_delivery_date']) ? $order['order_delivery_date'] : '指定しない',
                isset($order['order_delivery_time']) ? $order['order_delivery_time'] : '指定しない',
                isset($order['order_payment_name']) ? $order['order_payment_name'] : 'COD',
                isset($order['order_condition']) ? $order['order_condition'] : 'a:15:{s:12:"display_mode";s:9:"Usualsale";s:18:"campaign_privilege";s:0:"";s:17:"campaign_category";i:0;s:15:"privilege_point";s:0:"";s:18:"privilege_discount";s:0:"";s:11:"tax_display";s:8:"activate";s:8:"tax_mode";s:7:"include";s:10:"tax_target";s:3:"all";s:8:"tax_rate";s:0:"";s:10:"tax_method";s:7:"cutting";s:18:"applicable_taxrate";s:8:"standard";s:16:"tax_rate_reduced";s:0:"";s:18:"membersystem_state";s:8:"activate";s:18:"membersystem_point";s:8:"activate";s:14:"point_coverage";i:0;}',
                isset($order['order_item_total_price']) ? $order['order_item_total_price'] : '0.00',
                isset($order['order_getpoint']) ? $order['order_getpoint'] : '0',
                isset($order['order_usedpoint']) ? $order['order_usedpoint'] : '0',
                isset($order['order_discount']) ? $order['order_discount'] : '0.00',
                isset($order['order_shipping_charge']) ? $order['order_shipping_charge'] : '0.00',
                isset($order['order_cod_fee']) ? $order['order_cod_fee'] : '0.00',
                isset($order['order_tax']) ? $order['order_tax'] : '0.00',
                isset($order['order_date']) ? $order['order_date'] : '0000-00-00 00:00:00',
                isset($order['order_modified']) ? $order['order_modified'] : '',
                isset($order['order_status']) ? $order['order_status'] : '',
                isset($order['order_delidue_date']) ? $order['order_delidue_date'] : '指定しない'
            );

            $index++;
            $wpdb->query($query);
        }
    }
}
