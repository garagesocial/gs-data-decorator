<?php
require 'src/DataDecorator.php';

class Avatar {
    public static function getAvatarPath($str) {
        return "http://127.0.0.1/test/img/$str.png";
    }
}

$collection = [
    ['name' => 'Foo', 'age' => 20, '${Avatar::getAvatarPath(?)->avatar}' => 'foo'],
    ['name' => 'Bar', 'age' => 24, '${Avatar::getAvatarPath(?)->avatar}' => 'bar']
];      // This can be retrieved from database which is usally the case, but here just for demonstration an array is used

$processed = DataDecorator::processCollection($collection);
print_r($processed);
