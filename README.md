# graphql-php-scalars

A collection of custom scalar types for usage with https://github.com/webonyx/graphql-php

[![Validate](https://github.com/compwright/graphql-php-scalars/actions/workflows/validate.yml/badge.svg)](https://github.com/compwright/graphql-php-scalars/actions/workflows/validate.yml)
[![GitHub license](https://img.shields.io/github/license/compwright/graphql-php-scalars.svg)](https://github.com/compwright/graphql-php-scalars/blob/master/LICENSE)
[![Packagist](https://img.shields.io/packagist/v/compwright/graphql-php-scalars.svg)](https://packagist.org/packages/compwright/graphql-php-scalars)
[![Packagist](https://img.shields.io/packagist/dt/compwright/graphql-php-scalars.svg)](https://packagist.org/packages/compwright/graphql-php-scalars)

> This is a fork of mll-labs/graphql-php-scalars that supports PHP 7.4 and eliminates several unnecessary third party dependencies. As a result, email validation will be slightly stricter (we rely on the built-in PHP filter_var()).

## Installation

    composer require compwright/graphql-php-scalars

## Usage

You can use the provided Scalars just like any other type in your schema definition.
Check [SchemaUsageTest](tests/SchemaUsageTest.php) for an example.

If using the SDL, you can use the included ScalarDirectiveDecorator to load your desired custom scalar class and attach it to a custom scalar type.

You'll need this in your schema:

```graphql
# used to specify the desired class to execute for a custom scalar 
directive @scalar(class: String!) on SCALAR

# add the directive to each custom scalar
scalar Email @scalar(class: "Compwright\\GraphqlScalars\\Email")
```

Then when loading the schema, pass an instance of ScalarDirectiveDecorator as the $typeConfigDecorator argument to BuildSchema::build():

```php
BuildSchema::build($ast, new ScalarDirectiveDecorator());
```

If multiple decorators are needed to implement various other things, they could be pipelined together using something like [https://github.com/thephpleague/pipeline](league\pipeline).

### [BigInt](src/BigInt.php)

An arbitrarily long sequence of digits that represents a big integer.

### [Date](src/Date.php)

A date string with format `Y-m-d`, e.g. `2011-05-23`.

The following conversion applies to all date scalars:

- Outgoing values can either be valid date strings or `\DateTimeInterface` instances.
- Incoming values must always be valid date strings and will be converted to `\DateTimeImmutable` instances.

### [DateTime](src/DateTime.php)

A datetime string with format `Y-m-d H:i:s`, e.g. `2018-05-23 13:43:32`.

### [DateTimeTz](src/DateTimeTz.php)

A datetime string with format `Y-m-d\TH:i:s.uP`, e.g. `2020-04-20T16:20:04+04:00`, `2020-04-20T16:20:04Z`.

### [Email](src/Email.php)

A [RFC 5321](https://tools.ietf.org/html/rfc5321) compliant email.

### [JSON](src/JSON.php)

Arbitrary data encoded in JavaScript Object Notation. See https://www.json.org.

This expects a string in JSON format, not a GraphQL literal.

```graphql
type Query {
  foo(bar: JSON!): JSON!
}

# Wrong, the given value is a GraphQL literal object
{
  foo(bar: { baz: 2 })
}

# Correct, the given value is a JSON string representing an object
{
  foo(bar: "{ \"bar\": 2 }")
}
```

JSON responses will contain nested JSON strings.

```json
{
  "data": {
    "foo": "{ \"bar\": 2 }"
  }
}
```

### [Mixed](src/MixedScalar.php)

Loose type that allows any value. Be careful when passing in large `Int` or `Float` literals,
as they may not be parsed correctly on the server side. Use `String` literals if you are
dealing with really large numbers to be on the safe side.

### [Null](src/NullScalar.php)

Always `null`. Strictly validates value is non-null, no coercion.

### [Regex](src/Regex.php)

The `Regex` class allows you to define a custom scalar that validates that the given
value matches a regular expression.

The quickest way to define a custom scalar is the `make` factory method. Just provide
a name and a regular expression, you will receive a ready-to-use custom regex scalar.

```php
use Compwright\GraphqlScalars\Regex;

$hexValue = Regex::make(
    'HexValue',
    'A hexadecimal color is specified with: `#RRGGBB`, where `RR` (red), `GG` (green) and `BB` (blue) are hexadecimal integers between `00` and `FF` specifying the intensity of the color.',
    '/^#?([a-f0-9]{6}|[a-f0-9]{3})$/'
);
```

You may also define your regex scalar as a class.

```php
use Compwright\GraphqlScalars\Regex;

// The name is implicitly set through the class name here
class HexValue extends Regex
{
    /**
     * The description that is used for schema introspection.
     */
    public ?string $description = <<<'DESCRIPTION'
A hexadecimal color is specified with: `#RRGGBB`, where `RR` (red), `GG` (green) and `BB` (blue)
are hexadecimal integers between `00` and `FF` specifying the intensity of the color.
DESCRIPTION;

    public static function regex(): string
    {
        return '/^#?([a-f0-9]{6}|[a-f0-9]{3})$/';
    }
}
```

### [StringScalar](src/StringScalar.php)

The `StringScalar` encapsulates all the boilerplate associated with creating a string-based Scalar type.
It performs basic checks and coercion, you can focus on the minimal logic that is specific to your use case.

All you have to specify is a function that checks if the given string is valid.
Use the factory method `make` to generate an instance on the fly.

```php
use Compwright\GraphqlScalars\StringScalar;

$coolName = StringScalar::make(
    'CoolName',
    'A name that is most definitely cool.',
    static function (string $name): bool {
        return in_array($name, [
           'Vladar',
           'Benedikt',
           'Christopher',
        ]);
    }
);
```

Or you may simply extend the class, check out the implementation of the [Email](src/Email.php) scalar to see how.
