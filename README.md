# 🚩 interpolator

**JavaScript-like string interpolation in PHP.**

## Usage

### Scalar values
Pass an array of variables to `StringInterpolator` constructor, then use the method `StringInterpolator::interpolate()` to interpolate strings, in which variables may be enclosed between `${` and `}`:

```php
use Interpolator\StringInterpolator;

$interpolator = new StringInterpolator(['name' => 'John']);

echo $interpolator->interpolate('Hello ${name}'); // Hello John
```

You can also access array keys with subscript notation:

```php
$interpolator = new StringInterpolator(['numbers' => [77, 22, 31, 194]]);

echo $interpolator->interpolate('The third number is ${numbers[2]}'); // The third number is 31
```

String keys may also be accessed with dot notation:

```php
$interpolator = new StringInterpolator(['person' => ['name' => 'John', 'age' => 29]]);

echo $interpolator->interpolate('${person.name} is ${person.age} years old'); // John is 29 years old
```

### Objects

Public properties can be accessed with dot notation:

```php
$person = new stdClass();

$person->name = 'John';
$person->age = 29;

$interpolator = new StringInterpolator(['person' => $person]);

echo $interpolator->interpolate('${person.name} is ${person.age} years old'); // John is 29 years old
```

But you can also call methods and constants:

```php
class ExampleClass
{
    public const ID = '#ExampleClass';

    private int $timestamp;

    public function __construct()
    {
        $this->timestamp = time();
    }

    public function getDate()
    {
        return date('Y-m-d', $this->timestamp);
    }
}

$interpolator = new StringInterpolator(['obj' => new ExampleClass()]);

// The object #ExampleClass has this date: 2022-02-12
echo $interpolator->interpolate('The object ${obj.ID} has this date: ${obj.getDate()}');
```

You can also pass closures and they will be called:

```php
$interpolator = new StringInterpolator(['print' => function ($value) {
    return print_r($value, true);
}]);

// These are some values: Array ( [foo] => bar [0] => 4 [1] => 27 )
echo $interpolator->interpolate('These are some values: ${print(["foo" => "bar", 4, 27])}');
```