<?php

namespace Page\Welcart;

class Cart
{
    const URL = '/usces-cart';

    // include url of current page
    public static $URL = self::URL;

    public static $nextbtn = '.to_customerinfo_button';

    /**
     * @var \Codeception\Module\WPWebDriver
     */
    protected $I;

    public function __construct(\Codeception\Module\WPWebDriver $I) {
        $this->I = $I;
    }

    public function amOnPage() {
        $this->I->amOnPage($this::$URL);
    }

    public function canSeeNextButton() {
        $this->I->seeElement($this::$nextbtn);
    }

    public function goToNextPage() {
        $this->I->click($this::$nextbtn);
    }
}
