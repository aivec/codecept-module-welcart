<?php

namespace Page\Welcart;

class CartCompletion
{
    // include url of current page
    public static $URL = Cart::URL;

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */
    public static $pageel = '#cart_completion';

    /**
     * @var \Codeception\Module\WPWebDriver
     */
    protected $I;

    public function __construct(\Codeception\Module\WPWebDriver $I) {
        $this->I = $I;
    }

    public function waitForPage() {
        $this->I->waitForElement(self::$pageel);
    }
}
