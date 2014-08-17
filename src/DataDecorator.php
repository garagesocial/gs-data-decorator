<?php

/**
 * DataDecorator
 * https://github.com/garagesocial/gs-data-decorator
 * 
 * This package can be used to do some post-process on a data set. It looks for templates and calls the specified method to get the output.
 * It is useful especially when data is retrieved from a database and then needs some post-processing.
 * The following formats are supported:
 *    1. ${Model({attr: ?}).presenterMethod()}:outputKey
 *    2. ${ClassName.staticMethod(?)}:outputKey
 *
 * While the first format specifies how a model should be instantiated and initialized, and which method on its presenter
 * should be called, the second format facilitates getting the output by calling an arbitrary static method of a class.
 * Question mark in the second format has a special meaning and binding will be done on it. Other parameters of a method
 * can be specified if any.
 *
 * Example:
 *    1. ${VehicleMake({slug: ?}).presentLogoSrc()}:iconAutoMake
 *    2. ${\Gs\Libraries\Lib_storage.urlOrFallbackByKeyFixed(profile_photo_small, ?)}:thumbnail
 *
 */
class DataDecorator {

  const PATTERN = '
    /\$\{\s*
      (
          (?<class>[^{:(]+) ( \( \{ (?<properties>.*) \} \) )?
          \.
      )?
      (?<method>\w+) \((?<params>[a-zA-Z0-9?,_ ]+)?\)
    \s*\}\:(?<outkey>\w+)/x';

  /**
   * Check whether the current key is a template with a placeholder or not
   *
   * @param $template
   * @return bool
   */
  public function hasPlaceholder($template) {
    return substr($template, 0, 1) == '$';
  }

  /**
   * Process the template and call the appropriate methods to get output
   *
   * @param $template
   * @param $value
   * @return array
   * @throws \Exception
   */
  public function process($template, $value) {
    if (!$this->hasPlaceholder($template)) {
      return ['key' => $template, 'value' => $value];
    }
    $matches = $this->parse($template);
    if (empty($matches['method'])) {
      throw new \Exception('Invalid template pattern!');
    }
    if (empty($matches['class']) && !empty($matches['method'])) {   // handle function calls
      return [
        'key' => $matches['outkey'],
        'value' => call_user_func_array($matches['method'], $this->_bindParams($matches['params'], $value))
      ];
    }
    if (empty($matches['properties'])) {   // static method call on class
      $params = $this->_bindParams($matches['params'], $value);
      return [
        'key' => $matches['outkey'],
        'value' => call_user_func_array([$matches['class'], $matches['method']], $params)
      ];
    } else {
      $model = new $matches['class']; // instantiate the model
      foreach (explode(',', $matches['properties']) as $keyVal) {
        list($key, $v) = explode(':', $keyVal);
        if (trim($v) == '?') {
          $this->reconstructCallChain($model, trim($key), $value); // set the model's attribute
        }
      }
      $presenter = strstr($matches['method'], 'present') === false ? $matches['class'] : $matches['class'] . 'Presenter';   // get presenter name
      $presenter = new $presenter($model);    // instantiate the presenter
      return [
        'key' => $matches['outkey'],
        'value' => call_user_func([$presenter, $matches['method']])
      ];  //call the presenter method
    }
  }

  /**
   * Parse parameter string and binds question mark with the passed value
   *
   * @param $paramsString
   * @param $value
   * @return array
   */
  private function _bindParams($paramsString, $value)
  {
    $params = array();
    foreach (explode(',', $paramsString) as $param) {
      $p = trim($param);
      $p = $p == '?' ? $value: $p;
      $params[] = $p;
    }
    return $params;
  }

  /**
   * Parses the template based on the standard pattern
   *
   * @param string $template
   * @return array
   */
  public function parse($template)
  {
    preg_match(self::PATTERN, $template, $matches);
    return $matches;
  }

  /**
   * Bake an object call chain into the object
   * Given object $obj = new StdClass() and $callChainStr = 'name->company->name' and $value = 'test'
   * Build the call chain into $obj so as to enable $obj->name->company->name --> 'test'
   *
   * @param obj $obj  The object to build the call chain into
   * @param string $callChainStr  The chain of relations ex: name->company->name
   * @param string $value The value to set the final chain to
   * @return obj  Return the original $obj with the extra data hierarchy
   */
  public function reconstructCallChain($obj, $callChainStr, $value) {
    // explode the string by make->company->name
    $strs = explode('->', $callChainStr);
    // current object relation to implement, ie: make
    $currentVal = array_shift($strs);
    // implode rest of array back to string, "company->name"
    $strsImploded = implode($strs, '->');

    // if this is the last key then stop recursion and set value to $value
    if (count($strs) == 0) {
      $obj->$currentVal = $value;
      return $obj;
    }

    // recurse through the rest of the chain
    $obj->$currentVal = $this->reconstructCallChain(new \StdClass(), $strsImploded, $value);
    return $obj;
  }

  /**
   * Process a collection, call the post-processors based on the template and return the result
   *
   * @static
   * @param $collection
   * @return array
   */
  public static function processCollection($collection) {
    $dataPresenter = new self();
    $output = array();
    foreach ($collection as $k => $value) {
      if (is_array($value)) {
        $output[$k] = self::processCollection($value);    // if an element of the collection is a collection itself, process recursively...
      } else {
        $result = $dataPresenter->process($k, $value);
        $output[$result['key']] = $result['value'];
      }
    }
    return $output;
  }
}
