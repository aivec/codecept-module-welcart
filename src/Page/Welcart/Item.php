<?php

namespace Page\Welcart;

class Item
{
    // include url of current page
    public static $URL = '';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */
    public static $addToCartButton = '.skubutton';

    /**
     * @var \Codeception\Module\WPWebDriver
     */
    protected $I;

    public function __construct(\Codeception\Module\WPWebDriver $I) {
        $this->I = $I;
    }

    public function amOnPage($permalinkPath) {
        $this->I->amOnPage($permalinkPath);
        $this->I->canSeeElement($this::$addToCartButton);
    }

    public function addItemToCart() {
        $this->I->seeElement(self::$addToCartButton);
        $this->I->click(self::$addToCartButton);
    }
}
