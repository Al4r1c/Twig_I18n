<?php

/*
 * This file is part of the the Twig extension Twi18n.
 * URL: http://github.com/jhogervorst/Twi18n
 *
 * This file was part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) 2012 Jonathan Hogervorst
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig_I18nExtension_Gettext_TextDomain as TextDomain;

class Twig_I18nExtension_Extension_I18n extends Twig_Extension
{
    /**
     * @var string
     */
    private $_locale;

    /**
     * @var Twig_I18nExtension_Symfony_MessageSelector
     */
    private $selector;

    /**
     * @var Twig_I18nExtension_Gettext_I18nGettext
     */
    private $_i18n;

    /**
     * @var TextDomain[]
     */
    private $_catalogues = array();

    /**
     * @var array
     */
    private $_unloadedRess;

    /**
     * @var string
     */
    private $_pathToFolder;

    /**
     * @param string $locale
     * @param string $pathToFolder
     * @param string $configLocales
     */
    public function __construct($locale, $pathToFolder, $configLocales)
    {
        $this->setLocale($locale);
        $this->_unloadedRess = $configLocales;
        $this->_pathToFolder = $pathToFolder;
        $this->selector = new Twig_I18nExtension_Symfony_MessageSelector();
        $this->_i18n = new Twig_I18nExtension_Gettext_I18nGettext();
    }

    /**
     * @param string $domain
     * @return bool|TextDomain
     */
    public function getCatalogue($domain)
    {
        if (array_key_exists($domain, $this->_catalogues)) {
            return $this->_catalogues[$domain];
        }

        if (array_key_exists($domain, $this->_unloadedRess)) {
            $this->_catalogues[$domain] =
                $this->_i18n->load(
                    $this->_pathToFolder . '/' . $domain . '/LC_MESSAGES/' . $this->_unloadedRess[$domain] . '.mo'
                );

            return $this->_catalogues[$domain];
        }

        return false;
    }

    /**
     * Set the locale.
     * @param string $locale The locale
     */
    public function setLocale($locale = null)
    {
        setlocale(LC_ALL, $locale . '.utf-8');

        $this->_locale = $locale;
    }

    /**
     * Returns the token parser instances to add to the existing list.
     * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
     */
    public function getTokenParsers()
    {
        return array(
            // {% trans %}Symfony is great!{% endtrans %}
            new Twig_I18nExtension_TokenParser_Trans(),
            // {% transplural %}
            //     There is one apple
            // {% plural %}
            //     There are {{ count }} apples
            // {% endtransplural %}
            new Twig_I18nExtension_TokenParser_TransPlural(),
            // {% transchoice count %}
            //     {0} There are no apples|{1} There is one apple|]1,Inf] There are {{ count }} apples
            // {% endtranschoice %}
            new Twig_I18nExtension_TokenParser_TransChoice(),
        );
    }

    /**
     * Translates the given message.
     * @param string $message    The message id
     * @param array $arguments  An array of parameters for the message
     * @param string $domain     The domain for the message
     * @return string The translated string
     */
    public function trans($message, array $arguments = array(), $domain = null)
    {
        if (is_null($domain)) {
            $domain = $this->_locale;
        }

        if (($i18nFile = $this->getCatalogue($domain)) !== false) {
            if (($gettextObject = $i18nFile->getObjectBySingularKey($message)) !== false) {
                if (is_array($value = $gettextObject->getValues())) {
                    $message = $value[0];
                } else {
                    $message = $value;
                }
            }
        }

        return strtr($message, $arguments);
    }

    /**
     * Translates the given plural message.
     * @param integer $number    The number to use to find the indice of the message
     * @param string $message   The message id
     * @param array $arguments  An array of parameters for the message
     * @param string $domain     The domain for the message
     * @return string The translated string
     */
    public function transPlural($number, $message, array $arguments = array(), $domain = null)
    {
        if (is_null($domain)) {
            $domain = $this->_locale;
        }

        if (($i18nFile = $this->getCatalogue($domain)) !== false) {
            $idTrad = call_user_func($i18nFile->getPluralRule(), $number);

            if (($gettextObject = $i18nFile->getObjectBySingularKey($message)) !== false) {
                $message = $gettextObject->getValues()[$idTrad];
            }
        }

        return strtr($message, array_merge($arguments, array('%count%' => $number)));
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     * @param string $message    The message id
     * @param integer $number     The number to use to find the indice of the message
     * @param array $arguments  An array of parameters for the message
     * @param string $domain     The domain for the message
     * @return string The translated string
     */
    public function transChoice($number, $message, array $arguments = array(), $domain = null)
    {
        $message = $this->selector->choose($message, (int)$number, $this->_locale);

        return $this->trans($message, array_merge($arguments, array('%count%' => $number)), $domain);
    }

    /**
     * Returns a list of filters to add to the existing list.
     * @return array An array of filters
     */
    public function getFilters()
    {
        return array(
            'trans' => new Twig_Filter_Method($this, 'trans'),
            'transplural' => new Twig_Filter_Method($this, 'transPlural'),
            'transchoice' => new Twig_Filter_Method($this, 'transChoice'),
        );
    }

    /**
     * Returns the name of the extension.
     * @return string The extension name
     */
    public function getName()
    {
        return 'translator';
    }
}
