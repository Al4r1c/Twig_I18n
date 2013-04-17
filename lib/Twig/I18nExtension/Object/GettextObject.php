<?php

class Twig_I18nExtension_Object_GettextObject
{
    /**
     * @var string
     */
    private $singularKey;

    /**
     * @var string
     */
    private $pluralKey;

    /**
     * @var string|array
     */
    private $values;

    /**
     * @return string
     */
    public function getPluralKey()
    {
        return $this->pluralKey;
    }

    /**
     * @return string
     */
    public function getSingularKey()
    {
        return $this->singularKey;
    }

    /**
     * @return array|string
     */
    public function getValues()
    {
        return $this->values;
    }

    public function setPluralKey($pluralKey)
    {
        $this->pluralKey = $pluralKey;
    }

    public function setSingularKey($singularKey)
    {
        $this->singularKey = $singularKey;
    }

    public function setValues($values)
    {
        $this->values = $values;
    }
}