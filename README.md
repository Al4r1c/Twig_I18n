Twig I18n Extension v1.1
==========================

Twig translation extension using [.MO files](http://www.gnu.org/software/gettext/manual/html_node/MO-Files.html). This extension provides the functionality of Symfony's Translation component while using .MO files as data source, without the need of using Symfony.

This extension offers much more functionality than Twig's official [i18n extension](http://twig.sensiolabs.org/doc/extensions/i18n.html). It is possible to specify a custom domain, use better replacement techniques and use custom intervals depending on a numeric value.

Also, instead of using PHP's gettext functions, it use binary parser inspired from [Zend Framework one](http://framework.zend.com/). Thus it resolve caching problems gettext have.

Configuration
-------------

The application can be loaded with composer:

	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/Al4r1c/Twig_I18n"
		}
	],
	"require": {
		"twig/twigi18n": "v1.1"
	},

The Twi18n extension adds I18n support to Twig. It defines three tag, `trans`, `transplural` and `transchoice`. You need to register this extension before using one of the blocks:

    $twig->addExtension(
        new \Twig_I18nExtension_Extension_I18n(
		    'cz',
		    '/path/to/translation_root/',
		    array('cz' => 'default-cz', 'fr' => 'default-fr')
	    )
    );

Parameters are:
* The actual env locale, then used by default. Here 'cz'.
* Path where translations files (.mo) are stored. The folders then must have gettext like structure. (/path/'locale'/LC_MESSAGES/fileName.mo)
* Array with configuration or available locales.


In the previous example, we suppose that we got two files:
* /path/to/translation_root/cz/LC_MESSAGES/default-cz.mo
* /path/to/translation_root/fr/LC_MESSAGES/default-fr.mo

And that, by default Twig will use the cz one.

Files must be valid .MO files or it won't work properly. ([More informations](http://www.gnu.org/software/gettext/manual/html_node/MO-Files.html)).


Usage
-----

Twig_I18n provides specialized Twig tags (`trans`, `transplural` and `transchoice`) to help with message translation of static blocks of text:

    {% trans %}Hello %name%{% endtrans %}

    {% transplural %}
        There is one apple
    {% plural count %}
        There are %count% apples
    {% endtransplural %}

    {% transchoice count %}
        {0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples
    {% endtranschoice %}

The `transplural` and `transchoice` tags automatically get the `%count%` variable from the current context and pass it to the translator. If you use the `transplural` tag to specify a message for a singular and plural number, the extension will choose which message to use. If you use the `transchoice` tag, this extension will choose which message is used. This mechanism only works when you use a placeholder following the `%var%` pattern.

You can also specify the message domain and pass some additional variables:

    {% trans with {'%name%': 'Alice'} from 'my_app_domain' %}Hello %name%{% endtrans %}

    {% transplural with {'%name%': 'Alice'} from 'my_app_domain' %}
        There is one apple, %name%
    {% plural count %}
        There are %count% apples, %name%
    {% endtransplural %}

    {% transchoice count with {'%name%': 'Alice'} from 'my_app_domain' %}
        {0} There are no apples, %name%|{1} There is one apple, %name%|]1,Inf] There are %count% apples, %name%
    {% endtranschoice %}

Filters trans, transplural and transchoice can be used to translate variable texts and complex expressions:

	{% set message = 'Hello %name%!' %}

	{% set message_plural = 'Hello %name%, total: %count%!' %}

    {{ message|trans }}

    {{ message|transplural(message_plural, count) }}

    {{ message|transchoice(count) }}

    {{ 'There is one apple, %name%'|trans({'%name%': 'Bob'}, 'my_app_domain') }}

    {{ message|transplural(message_plural, count, {'%name%': 'Bob'}, 'my_app_domain') }}

    {{ message|transchoice(count, {'%name%': 'Bob'}, 'my_app_domain') }}

Learn more
----------

Take a look at Symfony's documentation to read more about the [Twig syntaxis](http://symfony.com/doc/current/book/translation.html#twig-templates).

License
-------

Twig_I18n is a modified version of parts of the Symfony package and it's included Twig library. It has also part of [Twi18n](https://github.com/jhogervorst/Twi18n).

For the full copyright and license information, please view the `LICENSE` file that is distributed with the source code.
