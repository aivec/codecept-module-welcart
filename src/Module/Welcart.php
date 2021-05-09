<?php

namespace Codeception\Module;

use WP_UnitTest_Factory;
use Codeception\Module;
use InvalidArgumentException;
use Exception;
use RuntimeException;

class Welcart extends Module
{
    public const SKUS_EMPTY = 4000;
    public const SKU_NAME_EMPTY = 4001;
    public const SKU_PRICE_EMPTY = 4002;
    public const WPDB_ERROR = 5000;

    /**
     * **HOOK** executed before test
     *
     * @param \Codeception\TestInterface $test
     */
    public function _before(\Codeception\TestInterface $test): void {
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
    public function _after(\Codeception\TestInterface $test): void {
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

        $wpdb->query('SET foreign_key_checks = 0');
        $wpdb->query("DROP TABLE IF EXISTS {$log}, {$access}");
        $wpdb->query("DROP TABLE IF EXISTS {$ordercartmetat}, {$ordermetat}, {$memmetat}, {$cmeta}");
        $wpdb->query("DROP TABLE IF EXISTS {$ordert}, {$memt}, {$cont}, {$ordercartt}");
        $wpdb->query('SET foreign_key_checks = 1');
    }

    protected static function factory() {
        static $factory = null;
        if (!$factory) {
            $factory = new \WP_UnitTest_Factory();
        }
        return $factory;
    }

    /**
     * Adds an arbitrary number of Welcart test members
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $members
     * @return void
     */
    public function createTestMembers(array $members): void {
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
     * Creates a Welcart order by inserting directly into the database.
     *
     * This method does not perform any validation checks. You can add fake items and
     * customer/member info. You don't have to supply any arguments to this method if
     * you just want a dummy order in the database.
     *
     * Note that, for example, if `customer` data is set for the `$entry` paramater then
     * any required fields that are missing will be
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::createOrder() If you want checks to be performed the same way Welcart would for a purchase.
     * @global \wpdb $wpdb
     * @global \usc_e_shop $usces
     * @param array $entry
     * @param array $cart
     * @param array $member
     * @return void
     * @throws RuntimeException Thrown if a wpdb error occurs.
     */
    public function createOrderDirect(array $entry = [], array $cart = [], array $member = ['ID' => 0]): void {
        global $wpdb, $usces;

        $gmtdate = get_date_from_gmt(gmdate('Y-m-d H:i:s', time()));
        if ('continue' == $charging_type) {
            $order_modified = substr($gmtdate, 0, 10);
        } else {
            $noreceipt_status_table = apply_filters('usces_filter_noreceipt_status', get_option('usces_noreceipt_status'));

            $status = (in_array($set['settlement'], $noreceipt_status_table)) ? 'noreceipt' : '';
            $order_modified = null;
        }

        $order = !empty($entry['order']) ? $entry['order'] : [];
        $customer = !empty($entry['customer']) ? $entry['customer'] : [];
        if (!empty($cart)) {
            $cart = [
                [
                    'serial' => 'a:1:{i:37;a:1:{s:9:"code1%3A1";i:0;}}',
                    'post_id' => 37,
                    'sku' => 'code1%3A1',
                    'price' => 7000,
                    'quantity' => 1,
                    'advance' => '',
                ],
            ];
        }

        $customer['mailaddress1'] = isset($customer['mailaddress1']) ? $customer['mailaddress1'] : 'test@gmail.com';
        $customer['name1'] = isset($customer['name1']) ? $customer['name1'] : 'Mega';
        $customer['name2'] = isset($customer['name2']) ? $customer['name2'] : 'Man';
        $customer['name3'] = isset($customer['name3']) ? $customer['name3'] : '';
        $customer['name4'] = isset($customer['name4']) ? $customer['name4'] : '';
        $customer['zipcode'] = isset($customer['zipcode']) ? $customer['zipcode'] : '1100011';
        $customer['pref'] = isset($customer['pref']) ? $customer['pref'] : '鳥取県';
        $customer['address1'] = isset($customer['address1']) ? $customer['address1'] : '鳥取県';
        $customer['address2'] = isset($customer['address2']) ? $customer['address2'] : '横浜市上北町';
        $customer['address3'] = isset($customer['address3']) ? $customer['address3'] : '9-2';
        $customer['tel'] = isset($customer['tel']) ? $customer['tel'] : '08011111111';
        $customer['fax'] = isset($customer['fax']) ? $customer['fax'] : '';

        if (empty($entry['delivery'])) {
            $entry['delivery'] = 
        } else {
            $entry['delivery']['delivery_flag'] = '1';
        }

        $status = apply_filters('usces_filter_reg_orderdata_status', $status, $entry);
        $order_date = (isset($results['order_date'])) ? $results['order_date'] : $gmtdate;
        $delidue_date = (isset($entry['order']['delidue_date'])) ? $entry['order']['delidue_date'] : null;
        $order_table_name = $wpdb->prefix . 'usces_order';
        $query = $wpdb->prepare(
            "INSERT INTO {$order_table_name} (
                `mem_id`, `order_email`, `order_name1`, `order_name2`, `order_name3`, `order_name4`, 
                `order_zip`, `order_pref`, `order_address1`, `order_address2`, `order_address3`, 
                `order_tel`, `order_fax`, `order_delivery`, `order_cart`, `order_note`, `order_delivery_method`, `order_delivery_date`, `order_delivery_time`, 
                `order_payment_name`, `order_condition`, `order_item_total_price`, `order_getpoint`, `order_usedpoint`, `order_discount`, 
                `order_shipping_charge`, `order_cod_fee`, `order_tax`, `order_date`, `order_modified`, `order_status`, `order_delidue_date` ) 
            VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %f, %d, %d, %f, %f, %f, %f, %s, %s, %s, %s)",
            (int)$member['ID'],
            isset($customer['mailaddress1']) ? $customer['mailaddress1'] : 'test@gmail.com',
            isset($customer['name1']) ? $customer['name1'] : 'Mega',
            isset($customer['name2']) ? $customer['name2'] : 'Man',
            isset($customer['name3']) ? $customer['name3'] : '',
            isset($customer['name4']) ? $customer['name4'] : '',
            isset($customer['zipcode']) ? $customer['zipcode'] : '1100011',
            isset($customer['pref']) ? $customer['pref'] : '鳥取県',
            isset($customer['address1']) ? $customer['address1'] : '鳥取県',
            isset($customer['address2']) ? $customer['address2'] : '横浜市上北町',
            isset($customer['address3']) ? $customer['address3'] : '9-2',
            isset($customer['tel']) ? $customer['tel'] : '08011111111',
            isset($customer['fax']) ? $customer['fax'] : '',
            isset($entry['order_delivery']) ? $entry['order_delivery'] : 'a:21:{s:5:"name1";s:4:"Test";s:5:"name2";s:3:"Man";s:5:"name3";s:0:"";s:5:"name4";s:0:"";s:7:"zipcode";s:7:"1100011";s:8:"address1";s:18:"横浜市上北町";s:8:"address2";s:3:"9-2";s:8:"address3";s:0:"";s:3:"tel";s:11:"08011111111";s:3:"fax";s:0:"";s:7:"country";s:2:"JP";s:4:"pref";s:9:"鳥取県";s:13:"delivery_flag";s:1:"0";s:2:"ID";s:4:"1001";s:12:"mailaddress1";s:24:"test@gmail.com";s:12:"mailaddress2";s:24:"test@gmail.com";s:5:"point";s:1:"0";s:8:"delivery";s:0:"";s:10:"registered";s:19:"2020-03-16 02:51:38";s:8:"nicename";s:0:"";s:6:"status";s:1:"0";}',
            serialize($cart),
            isset($entry['order_note']) ? $entry['order_note'] : '',
            isset($entry['order_delivery_method']) ? $entry['order_delivery_method'] : '1',
            isset($entry['order_delivery_date']) ? $entry['order_delivery_date'] : '指定しない',
            isset($entry['order_delivery_time']) ? $entry['order_delivery_time'] : '指定しない',
            isset($entry['order_payment_name']) ? $entry['order_payment_name'] : 'COD',
            isset($entry['order_condition']) ? $entry['order_condition'] : 'a:15:{s:12:"display_mode";s:9:"Usualsale";s:18:"campaign_privilege";s:0:"";s:17:"campaign_category";i:0;s:15:"privilege_point";s:0:"";s:18:"privilege_discount";s:0:"";s:11:"tax_display";s:8:"activate";s:8:"tax_mode";s:7:"include";s:10:"tax_target";s:3:"all";s:8:"tax_rate";s:0:"";s:10:"tax_method";s:7:"cutting";s:18:"applicable_taxrate";s:8:"standard";s:16:"tax_rate_reduced";s:0:"";s:18:"membersystem_state";s:8:"activate";s:18:"membersystem_point";s:8:"activate";s:14:"point_coverage";i:0;}',
            isset($entry['order_item_total_price']) ? $entry['order_item_total_price'] : '0.00',
            isset($entry['order_getpoint']) ? $entry['order_getpoint'] : '0',
            isset($entry['order_usedpoint']) ? $entry['order_usedpoint'] : '0',
            isset($entry['order_discount']) ? $entry['order_discount'] : '0.00',
            isset($entry['order_shipping_charge']) ? $entry['order_shipping_charge'] : '0.00',
            isset($entry['order_cod_fee']) ? $entry['order_cod_fee'] : '0.00',
            isset($entry['order_tax']) ? $entry['order_tax'] : '0.00',
            isset($entry['order_date']) ? $entry['order_date'] : '0000-00-00 00:00:00',
            isset($entry['order_modified']) ? $entry['order_modified'] : '',
            isset($entry['order_status']) ? $entry['order_status'] : '',
            isset($entry['order_delidue_date']) ? $entry['order_delidue_date'] : '指定しない'
        );
        $res = $wpdb->query($query);
        if ($res === false) {
            throw new RuntimeException($wpdb->last_error, self::WPDB_ERROR);
        }
        $order_id = $wpdb->insert_id;

        $index = 0;
        $cart_table = $wpdb->prefix . 'usces_ordercart';
        $cart_meta_table = $wpdb->prefix . 'usces_ordercart_meta';
        foreach ($cart as $row_index => $value) {
            $item_code = get_post_meta($value['post_id'], '_itemCode', true);
            $item_name = get_post_meta($value['post_id'], '_itemName', true);
            $skus = $usces->get_skus($value['post_id'], 'code');
            $sku_encoded = $value['sku'];
            $skucode = urldecode($value['sku']);
            $sku = $skus[$skucode];
            $tax = 0;
            $query = $wpdb->prepare(
                "INSERT INTO $cart_table 
                (
                order_id, row_index, post_id, item_code, item_name, 
                sku_code, sku_name, cprice, price, quantity, 
                unit, tax, destination_id, cart_serial 
                ) VALUES (
                %d, %d, %d, %s, %s, 
                %s, %s, %f, %f, %f, 
                %s, %d, %d, %s 
                )",
                $order_id,
                $row_index,
                $value['post_id'],
                $item_code,
                $item_name,
                $skucode,
                $sku['name'],
                $sku['cprice'],
                $value['price'],
                $value['quantity'],
                $sku['unit'],
                $tax,
                null,
                $value['serial']
            );
            $res = $wpdb->query($query);
            if ($res === false) {
                throw new RuntimeException($wpdb->last_error, self::WPDB_ERROR);
            }

            $cart_id = $wpdb->insert_id;

            $index++;
        }
    }

    public function createOrder() {
    }

    /**
     * Creates a Welcart item
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @global \WP_User|null $current_user
     * @global \wpdb $wpdb
     * @param string $itemcode
     * @param array  $itemskus multi-dimensional array of SKUs for the item. Must contain at least one SKU
     * @param array  $itemargs extra arguments to add to `$_POST` for the `item_save_metadata()` Welcart function
     * @return int Post ID of new item
     * @throws InvalidArgumentException Thrown if required arguments are missing.
     * @throws Exception Thrown if an error is returned by a Welcart function.
     */
    public function createItem($itemcode, $itemskus, $itemargs = []): int {
        global $usces, $current_user, $wpdb;

        if (empty($itemskus)) {
            throw new InvalidArgumentException('"itemskus" must contain at least one SKU', self::SKUS_EMPTY);
        }
        // create POST object for item
        $post = static::factory()->post->create_and_get();

        // add necessary caps to current user for POST editing
        $current_user->add_cap('edit_post');
        $current_user->add_cap('edit_others_posts');
        $current_user->add_cap('edit_published_posts');

        // add SKUs first
        $index = 0;
        foreach ($itemskus as $sku) {
            if (empty($sku['newskuname'])) {
                throw new InvalidArgumentException('"newskuname" is missing for SKU at index ' . $index, self::SKU_NAME_EMPTY);
            }
            if (empty($sku['newskuprice'])) {
                throw new InvalidArgumentException('"newskuprice" is missing for SKU at index ' . $index, self::SKU_PRICE_EMPTY);
            }
            $_POST['newskuname'] = !empty($sku['newskuname']) ? $sku['newskuname'] : '';
            $_POST['newskucprice'] = !empty($sku['newskucprice']) ? $sku['newskucprice'] : '';
            $_POST['newskuprice'] = !empty($sku['newskuprice']) ? $sku['newskuprice'] : '';
            $_POST['newskuzaikonum'] = !empty($sku['newskuzaikonum']) ? $sku['newskuzaikonum'] : '';
            // set zaiko to 'In Stock' by default
            $_POST['newskuzaikoselect'] = !empty($sku['newskuzaikoselect']) ? $sku['newskuzaikoselect'] : 0;
            $_POST['newskudisp'] = !empty($sku['newskudisp']) ? $sku['newskudisp'] : '';
            $_POST['newskuunit'] = !empty($sku['newskuunit']) ? $sku['newskuunit'] : '';
            $_POST['newskugptekiyo'] = !empty($sku['newskugptekiyo']) ? $sku['newskugptekiyo'] : '';
            $_POST['newskutaxrate'] = !empty($sku['newskutaxrate']) ? $sku['newskutaxrate'] : '';
            $res = add_item_sku_meta($post->ID);
            if ($res === false) {
                throw new Exception('An error occured during SKU registration.');
            }
        }

        $_POST['usces_nonce'] = wp_create_nonce('usc-e-shop');
        $_POST['page'] = 'usces_itemedit';
        $_POST['itemCode'] = $itemcode;
        if (empty($itemargs['itemName'])) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'itemName'");
            $itemargs['itemName'] = 'TEST_itemName_' . $count;
        }
        foreach ($itemargs as $argkey => $val) {
            $_POST[$argkey] = $val;
        }

        $res = item_save_metadata($post->ID, $post);
        if ($res === $post->ID) {
            throw new Exception('An error occured during item registration.');
        }
        if ($usces->action_status === 'error') {
            throw new Exception($usces->action_message ?? 'An error occured during item registration.');
        }

        return $post->ID;
    }
}
