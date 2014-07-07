This package can be used to do some post-process on a data set. It looks for templates and calls the specified method to get the output.
It is useful especially when data is retrieved from a database and then needs some post-processing.

There are two formats supported for this library:
   1. ``${Model(attribute to set on model)->presenterMethod(key name after processing the data)}``
   2. ``${ClassName::staticMethod(?)->outputKey}``

While the first format specifies how a model should be instantiated and initialized, and which method on its presenter
should be called, the second format facilitates getting the output by calling an arbitrary static method of a class.
Question mark in the second format has a special meaning and binding will be done on it. Other parameters of a method
can be specified if any.

Example:
   1. ``${Profile(username)->presentLogoSrc(icon)}``
   2. ``${Avatar::getAvatarPath(profile_photo_small, ?)->thumbnail}``
