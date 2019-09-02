# Garden JSON Transform

A little library that transforms data based on a specification that can be expressed in JSON.

## Purpose

The purpose of this package is to provide simple data transformation that can be applied to arrays of data like those that come from JSON APIs. The transformations are specified in simple arrays so that an entire transformation specification can also be serialized to JSON, making this a completely data-driven library.

## The Transformer Class

The heart of this library is the `Transformer` class that takes a transformation spec in its constructor and then uses that spec to tell it how to transform data.

```php
$t = new Transformer($spec);
$target = $t->transform($source);
```

The `Transformer` class is also callable so it can be passed as an argument that requires a callback such as `array_map`.

## Transformation Specs

A transformation spec is an associated array that contains all of the keys of the target array, but with values that are JSON references to keys in the source array.

```json
{
    "targetKey1": "/sourceKey1",
    "targetKey2": "/sourceKey2/sourceKey2.1"
}
```

You can see from the above, you can reference a nested value using a forward slash to separate nested references.

### Nested Data

For complex target arrays you nest keys by structuring the target like your desired data format:

```json
{
    "user": {
        "username": "/username",
        "fullName": "/fullName"
    }
}
```

### Default Values

If you reference a key that doesn't exist in the source data then it will be omitted in the transformed data. If you want to provide a default value you can use the following spec:

```json
{
    "username": {
        "$ref": "/username",
        "$default": "anonymous"
    }
}
```

If you omit the `$default` value then the default value is assumed to be `null`.

### Absolute vs. Relative References

You may have noticed that the references in the examples above all start with a `/` character. This is because they are all *absolute* references. If you don't use a `/` at the beginning of your reference then you are specifying a *relative* reference.

*Currently, there is no specific functionality for relative references, but it is recommended that you stick with absolute references to ensure forwards compatibility.*

### Literal Values

Sometimes you might want to provide a specific literal value in the result. You can do this with the `$literal` key.

```json
{
    "version": {
        "$literal": "1.1"
    }
}
```

## Why doesn't this library do enough?

Some people think this library is flimsy, foolish, and not worth worrying about. But don't you believe it! The initial implementation of this package is intended to provide the minimum amount of functionality to be useful for 90% of problems.

Any sensible features will be added as requirements evolve. However, they must adhere to the core purpose of this package: transformations that can be expressed as plain JSON.
