<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(23, new lime_output_color());

class ValidatorIdentity extends sfValidator
{
  protected function configure($options = array(), $messages = array())
  {
    parent::configure($options, $messages);

    $this->setOption('foo', 'bar');
    $this->setMessage('foo', 'bar');
  }

  protected function doClean($value)
  {
    return $value;
  }
}

// ->configure()
$t->diag('->configure()');
$v = new ValidatorIdentity();
$t->is($v->getOption('foo'), 'bar', '->configure() can add some options');
$v = new ValidatorIdentity(array('foo' => 'foobar'));
$t->is($v->getOption('foo'), 'foobar', '->configure() takes an options array as its first argument and values override default option values');
$v = new ValidatorIdentity();
$t->is($v->getMessage('foo'), 'bar', '->configure() can add some message');
$v = new ValidatorIdentity(array(), array('foo' => 'foobar'));
$t->is($v->getMessage('foo'), 'foobar', '->configure() takes a messages array as its second argument and values override default message values');

$v = new ValidatorIdentity();

// ->clean()
$t->diag('->clean()');
$t->is($v->clean('foo'), 'foo', '->clean() returns a cleanup version of the data to validate');
try
{
  $t->is($v->clean(''), '');
  $t->fail('->clean() throws a sfValidatorError exception if the data does not validate');
}
catch (sfValidatorError $e)
{
  $t->pass('->clean() throws a sfValidatorError exception if the data does not validate');
}
$t->is($v->clean('  foo  '), '  foo  ', '->clean() does not trim whitespaces by default');

// ->getEmptyValue()
$t->diag('->getEmptyValue()');
$v->setOption('required', false);
$v->setOption('empty_value', 'defaultnullvalue');
$t->is($v->clean(''), 'defaultnullvalue', '->getEmptyValue() returns the representation of an empty value for this validator');
$v->setOption('empty_value', null);

// ->setOption()
$t->diag('->setOption()');
$v->setOption('required', false);
$t->is($v->clean(''), null, '->setOption() changes options (required for example)');
$v->setOption('trim', true);
$t->is($v->clean('  foo  '), 'foo', '->setOption() can turn on whitespace trimming');

// ->hasOption()
$t->diag('->hasOption()');
$t->ok($v->hasOption('required'), '->hasOption() returns true if the validator has the option');
$t->ok(!$v->hasOption('nonexistant'), '->hasOption() returns false if the validator does not have the option');

// ->getOption()
$t->diag('->getOption()');
$t->is($v->getOption('required'), false, '->getOption() returns the value of an option');
$t->is($v->getOption('nonexistant'), null, '->getOption() returns null if the option does not exist');

// ->getOptions() ->setOptions()
$t->diag('->getOptions() ->setOptions()');
$v->setOptions(array('required' => true, 'trim' => false));
$t->is($v->getOptions(), array('required' => true, 'trim' => false), '->setOptions() changes all options');

// ->getMessages()
$t->diag('->getMessages()');
$t->is($v->getMessages(), array('required' => 'Required.', 'invalid' => 'Invalid.', 'foo' => 'bar'), '->getMessages() returns an array of all error messages');

// ->getMessage()
$t->diag('->getMessage()');
$t->is($v->getMessage('required'), 'Required.', '->getMessage() returns an error message string');
$t->is($v->getMessage('nonexistant'), '', '->getMessage() returns an empty string if the message does not exist');

// ->setMessage()
$t->diag('->setMessage()');
$v->setMessage('required', 'The field is required.');
try
{
  $v->clean('');
  $t->isnt($e->getMessage(), 'The field is required.', '->setMessage() changes the default error message string');
}
catch (sfValidatorError $e)
{
  $t->is($e->getMessage(), 'The field is required.', '->setMessage() changes the default error message string');
}

// ->setMessages()
$t->diag('->setMessages()');
$v->setMessages(array('required' => 'This is required.'));
$t->is($v->getMessages(), array('required' => 'This is required.'), '->setMessages() changes all error messages');

// ->getErrorCodes()
$t->diag('->getErrorCodes()');
$t->is($v->getErrorCodes(), array('required', 'invalid'), '->getErrorCodes() returns an array of error codes the validator can use');

// ::getCharset() ::setCharset()
$t->diag('::getCharset() ::setCharset()');
$t->is(sfValidator::getCharset(), 'UTF-8', '::getCharset() returns the charset to use for validators');
sfValidator::setCharset('ISO-8859-1');
$t->is(sfValidator::getCharset(), 'ISO-8859-1', '::setCharset() changes the charset to use for validators');
