# Garden JSON Transform

A little library that transforms data based on a specification that can be expressed in JSON.

## Purpose

The purpose of this package is to provide simple data transformation that can be applied to arrays of data like those that come from JSON APIs. The transformations are specified in simple arrays so that an entire transformation specification can also be serialized to JSON, making this a completely data-driven library.

## The Transformer Class

The heart of this library is the `Transformer` class that takes a transformation spec in its constructor and then uses that spec to tell it how to transform data.

## Transformation Specs

A transformation spec is an associated array that contains all of the keys of the target array, but with values that are JSON references to keys in the source array.

```php
[
    'destKey1' => '/sourceKey1',
    'destKey2' => '/sourceKey2/sourceKey2.1',
]
```

You can see from the above, you can reference a nested value using a forward slash to separate nested references.

### Nested Data

For complex target arrays you nest keys by structuring the target like your desired data format:

```php
[
    'user' => [
        'username' => '/username',
        'fullName' => '/fullName',
    ],
]
```

### Default Values

If you reference a key that doesn't exist in the source data then it will be omitted in the transformed data. If you want to provide a default value you can use the following spec:

```php
[
    'username' => [
        '$ref' => '/username',
        '$default' => 'anonymous',
    ],
]
```

If you omit the `$default` value then the default value is assumed to be `null`.

### Literal Values

Sometimes you might want to provide a specific literal value in the result. You can do this with the `$literal` key.

```php
[
    'vesion' => [
        '$literal' => '1.1',
    ],
]
```

## This Library Could Be More Useful

Some people think this library is flimsy; foolish; but don't you believe it! The initial implementation of this package is intended to provide the least amount of functionality possible to be useful for 90% of use-cases. Any sensible functionality will be added as requirements evolve, as long as it adheres to the core purpose of transformations that can be expressed as plain JSON.
