<?php

/**
 * This package can be used to do some post-process on a data set. It looks for templates and calls the specified method to get the output.
 * It is useful especially when data is retrieved from a database and then needs some post-processing.
 *
 * There are two formats supported for this library:
 *    1. ${Model(attribute to set on model)->presenterMethod(key name after processing the data)}
 *    2. ${ClassName::staticMethod(?)->outputKey}
 *
 * While the first format specifies how a model should be instantiated and initialized, and which method on its presenter
 * should be called, the second format facilitates getting the output by calling an arbitrary static method of a class.
 * Question mark in the second format has a special meaning and binding will be done on it. Other parameters of a method
 * can be specified if any.
 *
 * Example:
 *    1. ${Profile(username)->presentLogoSrc(icon)}
 *    2. ${Avatar::getAvatarPath(profile_photo_small, ?)->thumbnail}
 *
 */
class DataDecorator {

//  const PATTERN = '#^\$\{\s*(?<model>\w+)\((?<mattr>\w+)\)\s*->\s*(?<presenter>\w+)\((?<pattr>\w+)\)\s*\}$#';
  const PATTERN = '
    /\$\{
      \s*
      (
        (?<model>\w+) \((?<mattr>\w+)\)                                               #e.g. Profile(username)
      )
      \s*->\s*
        (?<presenter>\w+)                                                             #e.g. presentLogoSrc
        \((?<pattr>\w+)\)                                                             #e.g. (icon)
      |
        (?<class>[^{:]+) \:\: (?<method>\w+) \((?<params>[a-zA-Z0-9?,_ ]+)\)          #e.g. Avatar::getAvatarPath(profile_picture, ?)
      \s*->\s*
        (?<outkey>\w+)                                                                #e.g. thumbnail
    \s*\}/x';

  private $_groups = [
    'static'    =>  ['class', 'method', 'params', 'outkey'],
    'presenter' =>  ['model', 'mattr', 'presenter', 'pattr']
  ];

  /**
   * Check whether the current key is a template with a palce holder or not
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
    preg_match(self::PATTERN, $template, $matches);
    if (!$this->_isGroupMatched('static', $matches) && !$this->_isGroupMatched('presenter', $matches)) {
      throw new \Exception('Invalid template pattern!');
    }
    if (!empty($matches['class'])) {   //static method call on class
      $params = array();
      foreach (explode(',', $matches['params']) as $param) {
        $p = trim($param);
        $p = $p == '?' ? $value: $p;
        $params[] = $p;
      }
      return ['key' => $matches['outkey'], 'value' => call_user_func_array([$matches['class'], $matches['method']], $params)];
    } else {
      $model = new $matches['model']; //instantiate the model
      $model->$matches['mattr'] = $value; //set the model's attribute
      $presenter = $matches['model'] . 'Presenter';   //get presenter name
      $presenter = new $presenter($model);    //instantiate the presenter
      return ['key' => $matches['pattr'], 'value' => call_user_func([$presenter, $matches['presenter']])];  //call the presenter method
    }
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
        $output[$k] = self::processCollection($value);    //if an element of the collection is a collection itself, process recursively...
      } else {
        $result = $dataPresenter->process($k, $value);
        $output[$result['key']] = $result['value'];
      }
    }
    return $output;
  }

  /**
   * Return true if all the keys of a group are matched by pattern
   *
   * @param       $group
   * @param array $matches
   * @return bool
   */
  private function _isGroupMatched($group, array $matches) {
    $ret = true;
    foreach ($this->_groups[$group] as $k) {
      $ret = $ret && !empty($matches[$k]);
    }
    return $ret;
  }
}
