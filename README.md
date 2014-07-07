# DataDecorator

This package can be used to do post-processing on a set of data. It looks for templates and calls the specified method to get the output. It is useful especially when data is retrieved from a database and post-processing needs to be applied on every record.

## Use Case
**Database Structure**
``id: unsignedInteger, name: string``

**Database Data**
``{id: 1, name: 'one'}, {id: 2, name: 'two'}, {id: 3, name: 'three'}, {id: four, name: 'four'}``

Let's say you are querying this data (using eloquent ORM here) ``MyModel::select('id', 'name')->get();`` but you need to apply some processing on the value of ``name`` for each record.
Below we have a function called ``MyClass::myStaticMethod($record['name'])`` which appends ``_modified`` to the input string.

#### BEFORE: Traditional Way
```php
$records = MyModel::select('id', 'name')->get()->toArray();
foreach($records as &$record) {
   $record['name'] = MyClass::myStaticMethod($record['name']);
}

// output: ``{id: 1, name: 'one_modified'}, {id: 2, name: 'two_modified'}, {id: 3, name: 'three_modified'}, {id: four, name: 'four_modified'}``
```

#### AFTER: DataDecorator Way
With the DataRecorator way, you can specify within the select string how the data should be processed. The syntax follows conventions and should not be read as raw PHP code. The two formats are explained in the next section. 
```php
$records = MyModel::select('id', 'name AS ${MyClass::myStaticMethod(?)->name}')->get()->toArray();
$records = DataDecorator::processCollection($records);

// output: ``{id: 1, name: 'one_modified'}, {id: 2, name: 'two_modified'}, {id: 3, name: 'three_modified'}, {id: four, name: 'four_modified'}``
```
- the decorator needs to be wrapped in ``${ }``
- the ``?`` represents the value of name
- the ending ``->name`` means to apply the resulting value back to the key ``name``, this could be changed to any other name
- the results needs to be ran through ``DataDecorator::processCollection($records)``


## Supported Formats

This first format specifies how a model should be instantiated and initialized, and which method on its presenter
should be called

**final_key_name** which key to store the processed data in

```
format: ${Model(attribute to set on model)->presenterMethod(final_key_name)}
example: ``${Profile(username)->presentLogoSrc(icon)}``
```

This second format facilitates getting the output by calling an arbitrary static method of a class. Question mark in the second format has a special meaning and binding will be done on it. Other parameters of a method can be specified if any.

**final_key_name** which key to store the processed data in

```
format: ${ClassName::staticMethod(?)->outputKey}
example: ``${Avatar::getAvatarPath(profile_photo_small, ?)->final_key_name}``
```         
