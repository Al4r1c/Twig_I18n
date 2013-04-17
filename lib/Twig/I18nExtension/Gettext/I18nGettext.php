<?php
/**
 * Zend Framework (http://framework.zend.com/)
 * @link http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */
use Twig_I18nExtension_Gettext_TextDomain as TextDomain;
use Twig_I18nExtension_Object_GettextObject as GettextObject;


/**
 * Gettext loader.
 */
class Twig_I18nExtension_Gettext_I18nGettext
{
    /**
     * Current file pointer.
     * @var resource
     */
    protected $file;

    /**
     * Whether the current file is little endian.
     * @var bool
     */
    protected $littleEndian;

    /**
     * @param string $filename
     * @throws Exception
     * @return TextDomain
     */
    public function load($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \Exception(sprintf('Could not open file %s for reading', $filename));
        }

        $textDomain = new TextDomain();


        try {
            $this->file = fopen($filename, 'rb');
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Could not open file %s for reading', $filename));
        }


        // Verify magic number
        $magic = fread($this->file, 4);

        if ($magic == "\x95\x04\x12\xde") {
            $this->littleEndian = false;
        } elseif ($magic == "\xde\x12\x04\x95") {
            $this->littleEndian = true;
        } else {
            fclose($this->file);
            throw new \Exception(sprintf('%s is not a valid gettext file', $filename));
        }

        // Verify major revision (only 0 and 1 supported)
        $majorRevision = ($this->readInteger() >> 16);

        if ($majorRevision !== 0 && $majorRevision !== 1) {
            fclose($this->file);
            throw new \Exception(sprintf('%s has an unknown major revision', $filename));
        }

        // Gather main information
        $numStrings = $this->readInteger();
        $originalStringTableOffset = $this->readInteger();
        $translationStringTableOffset = $this->readInteger();

        // Usually there follow size and offset of the hash table, but we have
        // no need for it, so we skip them.
        fseek($this->file, $originalStringTableOffset);
        $originalStringTable = $this->readIntegerList(2 * $numStrings);

        fseek($this->file, $translationStringTableOffset);
        $translationStringTable = $this->readIntegerList(2 * $numStrings);

        // Read in all translations
        for ($current = 0; $current < $numStrings; $current++) {
            $sizeKey = $current * 2 + 1;
            $offsetKey = $current * 2 + 2;
            $originalStringSize = $originalStringTable[$sizeKey];
            $originalStringOffset = $originalStringTable[$offsetKey];
            $translationStringSize = $translationStringTable[$sizeKey];
            $translationStringOffset = $translationStringTable[$offsetKey];

            $originalString = array('');
            if ($originalStringSize > 0) {
                fseek($this->file, $originalStringOffset);
                $originalString = explode("\0", fread($this->file, $originalStringSize));
            }

            if ($translationStringSize > 0) {
                fseek($this->file, $translationStringOffset);
                $translationString = explode("\0", fread($this->file, $translationStringSize));

                $object = new GettextObject();

                if (count($originalString) > 1 && count($translationString) > 1) {

                    $object->setSingularKey($originalString[0]);
                    $object->setValues($translationString);

                    array_shift($originalString);

                    foreach ($originalString as $string) {
                        $object->setPluralKey($string);
                    }
                } else {
                    $object->setSingularKey($originalString[0]);
                    $object->setValues($translationString[0]);
                }

                $textDomain->addObject($object);
            }
        }

        // Read header entries
        if (($headerObject = $textDomain->getObjectBySingularKey('')) !== false) {
            $rawHeaders = explode("\n", trim($headerObject->getValues()));

            foreach ($rawHeaders as $rawHeader) {
                list($header, $content) = explode(':', $rawHeader, 2);

                if (trim(strtolower($header)) === 'plural-forms') {
                    $explodeSemiColon = array_map('trim', explode(';', $content));
                    $explodeSemiColon[1] = substr(trim($explodeSemiColon[1]), 7);

                    $totalPluralForm = substr($explodeSemiColon[0], 9);

                    if ($totalPluralForm == 1) {
                        $rule = function () {
                            return 0;
                        };
                    } elseif ($totalPluralForm == 2) {
                        $ruleAsString = trim(str_replace(' ', '', $explodeSemiColon[1]), '()');
                        $pattern = '#^([n0-9]+)([=!><]+)([n0-9]+)$#';

                        if (preg_match_all($pattern, $ruleAsString, $matches, PREG_SET_ORDER) > 0) {
                            if (strcmp($matches[0][2], '!=') == 0) {
                                $rule = function ($number) {
                                    if (Twig_I18nExtension_Symfony_Interval::test($number, '{1}')) {
                                        return 0;
                                    } else {
                                        return 1;
                                    }
                                };
                            } elseif (strcmp($matches[0][2], '>') == 0 || strcmp($matches[0][2], '<') == 0) {
                                $rule = function ($number) {
                                    if (Twig_I18nExtension_Symfony_Interval::test($number, '[0,1]')) {
                                        return 0;
                                    } else {
                                        return 1;
                                    }
                                };
                            }
                        }
                    } elseif ($totalPluralForm > 2) {
                        $explodeDoubleDot = array_map('trim', explode(':', $explodeSemiColon[1]));

                        if (count($explodeDoubleDot) == $totalPluralForm) {
                            $condition = '';

                            for ($cpt = 0; $cpt < $totalPluralForm; $cpt++) {
                                $tempString = str_replace(' ', '', $explodeDoubleDot[$cpt]);

                                if (strpos($tempString, '?') > 0) {
                                    $tabCond = explode('?', str_replace('n', '$number', $tempString));

                                    $condition .= '(' . $tabCond[0] . ') ? ' . $tabCond[1] . ' : (';
                                } else {
                                    $condition .= $tempString;

                                    for ($i = 0; $i < $cpt; $i++) {
                                        $condition .= ')';
                                    }
                                }
                            }

                            $rule = function ($number) use ($condition) {
                                eval('$result = ' . $condition . ';');

                                return $result;
                            };
                        }
                    }

                    if (!isset($rule)) {
                        throw new \Exception(sprintf('Malformed plural form "%s".', $content));
                    }

                    $textDomain->setPluralRule($rule);
                }
            }

            $textDomain->removeObjectBySingularKey('');
        }


        fclose($this->file);

        return $textDomain;
    }

    /**
     * Read a single integer from the current file.
     * @return integer
     */
    protected function readInteger()
    {
        if ($this->littleEndian) {
            $result = unpack('Vint', fread($this->file, 4));
        } else {
            $result = unpack('Nint', fread($this->file, 4));
        }

        return $result['int'];
    }

    /**
     * Read an integer from the current file.
     * @param integer $num
     * @return integer
     */
    protected function readIntegerList($num)
    {
        if ($this->littleEndian) {
            return unpack('V' . $num, fread($this->file, 4 * $num));
        }

        return unpack('N' . $num, fread($this->file, 4 * $num));
    }
}