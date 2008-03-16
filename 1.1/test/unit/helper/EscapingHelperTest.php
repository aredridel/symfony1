<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../../test/bootstrap/unit.php');

require_once(dirname(__FILE__).'/../../../lib/helper/EscapingHelper.php');

$t = new lime_test(13, new lime_output_color());

sfConfig::set('sf_charset', 'UTF-8');

// esc_entities()
$t->diag('esc_entities()');
$t->is(esc_entities(10), 10, 'esc_entities() does not escape integers');
$t->is(esc_entities(false), false, 'esc_entities() does not escape booleans');
$t->is(esc_entities('foo bar'), 'foo bar', 'esc_entities() only escapes strings');
$t->is(esc_entities('<b>foo</b> bar'), '&lt;b&gt;foo&lt;/b&gt; bar', 'esc_entities() only escapes strings');
$t->is(esc_entities('ü&'), '&uuml;&amp;', 'esc_entities() utf-8 characters are translated');

// esc_specialchars()
$t->diag('esc_specialchars()');
$t->is(esc_specialchars(10), 10, 'esc_specialchars() does not escape integers');
$t->is(esc_specialchars(false), false, 'esc_specialchars() does not escape booleans');
$t->is(esc_specialchars('foo bar'), 'foo bar', 'esc_specialchars() only escapes strings');
$t->is(esc_specialchars('<b>foo</b> bar'), '&lt;b&gt;foo&lt;/b&gt; bar', 'esc_specialchars() only escapes strings');
$t->is(esc_specialchars('ü&'), 'ü&amp;', 'esc_specialchars() utf-8 characters are NOT translated');

// esc_raw()
$t->diag('esc_raw()');
$t->is(esc_raw('foo'), 'foo', 'esc_raw() returns the first argument as is');

// esc_js()
$t->diag('esc_js()');
$t->is(esc_js('alert(\'foo\' + "bar")'), 'alert(&#039;foo&#039; + &quot;bar&quot;)', 'esc_js() escapes javascripts');

// esc_js()
$t->diag('esc_js_no_entities()');
$t->is(esc_js_no_entities('alert(\'foo\' + "bar")'), 'alert(\\\'foo\\\' + \\"bar\\")', 'esc_js_no_entities() escapes javascripts');
