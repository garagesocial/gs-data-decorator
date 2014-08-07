<?php

require __DIR__ . '/../src/DataDecorator.php';

class DataPresenterTest extends PHPUnit_Framework_TestCase
{
    protected $useDatabase = false;
    protected $useLoggedInUser = false;

    public function testParser()
    {
        $dp = new DataDecorator();
        $p1 = $dp->parse('${Car({name: ?}).presentUrl()}:url');
        $this->assertEquals($p1['class'], 'Car');
        $this->assertEquals($p1['properties'], 'name: ?');
        $this->assertEquals($p1['method'], 'presentUrl');
        $this->assertEquals($p1['outkey'], 'url');
        $p2 = $dp->parse('${UrlDecorator.decorate(short, ?)}:thumbnail');
        $this->assertEquals($p2['class'], 'UrlDecorator');
        $this->assertEquals($p2['method'], 'decorate');
        $this->assertEquals($p2['params'], 'short, ?');
        $this->assertEquals($p2['outkey'], 'thumbnail');
    }
    
    public function testProcess()
    {
        $dp = new DataDecorator();
        $p1 = $dp->process('${strtoupper(?)}:name', 'foo');
        $this->assertEquals($p1['value'], 'FOO');

        $p2 = $dp->process('${MyDecorator.commaTobar(?)}:out', 'bar1,bar2,bar3');
        $this->assertEquals($p2['value'], 'bar1|bar2|bar3');

        $p2 = $dp->process('${MyDecorator({attr: ?}).presentAttr(?)}:out', 'baz');
        $this->assertEquals($p2['value'], 'BAZ');
    }

}

class MyDecorator
{
    public $attr;

    public static function commaToBar($str)
    {
        return str_replace(',', '|', $str);
    }
}

class MyDecoratorPresenter
{
    public $model;
    public function __construct($model) {
        $this->model = $model;
    }

    public function presentAttr()
    {
        return strtoupper($this->model->attr);
    }
}
