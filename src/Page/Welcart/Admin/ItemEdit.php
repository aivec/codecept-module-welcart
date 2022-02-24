<?php

namespace Page\Welcart\Admin;

class ItemEdit
{
    const SHIPPED_DIVISION = 'shipped';
    const DATA_DIVISION = 'data';
    const SERVICE_DIVISION = 'service';

    public static $URL = '/admin.php?page=usces_itemedit';

    public static $tableid = '#mainDataTable';

    public static $ilistnaviclass = '.item_list_navi';

    public static $itemcodeinputid = '#itemCode';

    /**
     * @var \Codeception\Module\WPWebDriver
     */
    protected $I;

    public function __construct(\Codeception\Module\WPWebDriver $I) {
        $this->I = $I;
    }

    public function openListPage() {
        $this->I->amOnAdminPage(self::$URL);
    }

    public function openEditPage($itemName) {
        $this->I->executeJS(
            "jQuery('{$this::$tableid} strong:contains(\"{$itemName}\")').siblings('{$this::$ilistnaviclass}').find('a')[0].click();"
        );
        $this->I->waitForElement(self::$itemcodeinputid);
    }

    public function setItemDivision($division) {
        $this->I->click('#division_' . $division);
    }

    public function publish() {
        $this->I->submitForm('#post', [], '#publish');
    }
}
