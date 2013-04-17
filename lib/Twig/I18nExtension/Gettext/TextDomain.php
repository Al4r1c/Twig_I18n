<?php
/**
 * Zend Framework (http://framework.zend.com/)
 * @link http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */
use Twig_I18nExtension_Object_GettextObject as GettextObject;

/**
 * Text domain.
 */
class Twig_I18nExtension_Gettext_TextDomain
{
    /**
     * @var callable
     */
    private $pluralRule;

    /**
     * @var GettextObject[]
     */
    private $objects;

    /**
     * @param callable $callableRule
     */
    public function setPluralRule($callableRule)
    {
        $this->pluralRule = $callableRule;
    }

    /**
     * @return callable
     */
    public function getPluralRule()
    {
        return $this->pluralRule;
    }

    /**
     * @param GettextObject $newObject
     */
    public function addObject($newObject)
    {
        $this->objects[] = $newObject;
    }

    /**
     * @param string $singularKey
     * @return boolean|Twig_I18nExtension_Object_GettextObject
     */
    public function getObjectBySingularKey($singularKey)
    {
        foreach ($this->objects as $oneObject) {
            if (strcmp($oneObject->getSingularKey(), $singularKey) == 0) {
                return $oneObject;
            }
        }

        return false;
    }

    /**
     * @param string $singularKey
     */
    public function removeObjectBySingularKey($singularKey)
    {
        foreach ($this->objects as $key => $oneObject) {
            if (strcmp($oneObject->getSingularKey(), $singularKey) == 0) {
                unset($this->objects[$key]);
            }
        }
    }
}