<?php

namespace Page\Welcart;

class CartCustomer
{
    // include url of current page
    public static $URL = Cart::URL;

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */
    public static $emailField = '#loginmail';

    public static $passwordField = '#loginpass';

    public static $loginButton = '.to_memberlogin_button';

    /**
     * @var \Codeception\Module\WPWebDriver
     */
    protected $I;

    public function __construct(\Codeception\Module\WPWebDriver $I) {
        $this->I = $I;
    }

    public function waitForPage(): void {
        $this->I->waitForElement(self::$emailField);
    }

    public function login($email, $password): void {
        $this->I->seeElement(self::$emailField);
        $this->I->fillField(self::$emailField, $email);
        $this->I->fillField(self::$passwordField, $password);
        $this->I->click(self::$loginButton);
    }
}
