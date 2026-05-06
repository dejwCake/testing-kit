<?php

declare(strict_types=1);

namespace DejwCake\TestingKit\OpenApi;

use ArrayObject;
use Illuminate\Support\Collection;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;

use function is_string;

/**
 * Class Util.
 *
 * This class acts as compatibility layer between NelmioApiDocBundle and swagger-php.
 *
 * It was written to replace the GuilhemN/swagger layer as a lower effort to maintain alternative.
 *
 * The main purpose of this class is to search for and create child Annotations
 * of swagger Annotation classes with the following convenience methods
 * to get or create the respective Annotation instances if not found
 *
 * @see Util::getPath
 * @see Util::getSchema()
 * @see Util::getProperty()
 * @see Util::getOperation()
 * @see Util::getOperationParameter()
 *
 * which in turn get or create the Annotation instances through the following more general methods
 * @see Util::getChild()
 * @see Util::getCollectionItem()
 * @see Util::getIndexedCollectionItem()
 *
 * which then searches for an existing Annotation through
 * @see Util::searchCollectionItem()
 * @see Util::searchIndexedCollectionItem()
 *
 * and if not found the Annotation creates it through
 * @see Util::createCollectionItem()
 * @see Util::createContext()
 */
final class Util
{
    /**
     * All http method verbs as known by swagger.
     */
    public const OPERATIONS = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'];

    /**
     * Return an existing PathItem object from $api->paths[] having its member path set to $path.
     * Create, add to $api->paths[] and return this new PathItem object and set the property if none found.
     *
     * @see OA\OpenApi::$paths
     * @see OA\PathItem::path
     */
    public static function getPath(OA\OpenApi $api, string $path): OA\PathItem
    {
        return self::getIndexedCollectionItem($api, OA\PathItem::class, $path);
    }

    /**
     * @return Collection<int, OA\PathItem>
     */
    public static function getPaths(OA\OpenApi $api, string $path): Collection
    {
        if (!str_contains($path, '?')) {
            return new Collection([self::getPath($api, $path)]);
        }

        return new Collection(
            array_map(
                static fn (string $possiblePath) => self::getPath($api, $possiblePath),
                self::determinePossiblePaths($path),
            ),
        );
    }

    /**
     * Return an existing Schema object from $api->components->schemas[] having its member schema set to $schema.
     * Create, add to $api->components->schemas[] and return this new Schema object and set the property if none found.
     *
     * @see OA\Schema::$schema
     * @see OA\Components::$schemas
     */
    public static function getSchema(OA\OpenApi $api, string $schema): OA\Schema
    {
        if (!$api->components instanceof OA\Components) {
            $api->components = new OA\Components([]);
        }

        return self::getIndexedCollectionItem($api->components, OA\Schema::class, $schema);
    }

    /**
     * Return an existing Property object from $schema->properties[]
     * having its member property set to $property.
     *
     * Create, add to $schema->properties[] and return this new Property object
     * and set the property if none found.
     *
     * @see OA\Schema::$properties
     * @see OA\Property::$property
     */
    public static function getProperty(OA\Schema $schema, string $property): OA\Property
    {
        return self::getIndexedCollectionItem($schema, OA\Property::class, $property);
    }

    /**
     * Return an existing Operation from $path->{$method}
     * or create, set $path->{$method} and return this new Operation object.
     *
     * @see OA\PathItem::$get
     * @see OA\PathItem::$post
     * @see OA\PathItem::$put
     * @see OA\PathItem::$patch
     * @see OA\PathItem::$delete
     * @see OA\PathItem::$options
     * @see OA\PathItem::$head
     */
    public static function getOperation(OA\PathItem $path, string $method): OA\Operation
    {
        $class = array_keys($path::$_nested, \strtolower($method), true)[0];

        return self::getChild($path, $class, ['path' => $path->path]);
    }

    /**
     * Return an existing Parameter object from $operation->parameters[]
     * having its members name set to $name and in set to $in.
     *
     * Create, add to $operation->parameters[] and return
     * this new Parameter object and set its members if none found.
     *
     * @see OA\Operation::$parameters
     * @see OA\Parameter::$name
     * @see OA\Parameter::$in
     */
    public static function getOperationParameter(OA\Operation $operation, string $name, string $in): OA\Parameter
    {
        return self::getCollectionItem($operation, OA\Parameter::class, ['name' => $name, 'in' => $in]);
    }

    /**
     * Return an existing nested Annotation from $parent->{$property} if exists.
     * Create, add to $parent->{$property} and set its members to $properties otherwise.
     *
     * $property is determined from $parent::$_nested[$class]
     * it is expected to be a string nested property.
     *
     * @see OA\AbstractAnnotation::$_nested
     */
    public static function getChild(
        OA\AbstractAnnotation $parent,
        string $class,
        array $properties = [],
    ): OA\AbstractAnnotation {
        $nested = $parent::$_nested;
        $property = $nested[$class];

        if ($parent->{$property} === null || $parent->{$property} === Generator::UNDEFINED) {
            $parent->{$property} = self::createChild($parent, $class, $properties);
        }

        return $parent->{$property};
    }

    /**
     * Return an existing nested Annotation from $parent->{$collection}[]
     * having all $properties set to the respective values.
     *
     * Create, add to $parent->{$collection}[] and set its members
     * to $properties otherwise.
     *
     * $collection is determined from $parent::$_nested[$class]
     * it is expected to be a single value array nested Annotation.
     *
     * @see OA\AbstractAnnotation::$_nested
     */
    public static function getCollectionItem(
        OA\AbstractAnnotation $parent,
        string $class,
        array $properties = [],
    ): OA\AbstractAnnotation {
        $key = null;
        $nested = $parent::$_nested;
        $collection = $nested[$class][0];

        if (count($properties)) {
            $key = self::searchCollectionItem(
                $parent->{$collection} && $parent->{$collection} !== Generator::UNDEFINED ? $parent->{$collection} : [],
                $properties,
            );
        }
        if ($key === null) {
            $key = self::createCollectionItem($parent, $collection, $class, $properties);
        }

        return $parent->{$collection}[$key];
    }

    /**
     * Return an existing nested Annotation from $parent->{$collection}[]
     * having its mapped $property set to $value.
     *
     * Create, add to $parent->{$collection}[] and set its member $property to $value otherwise.
     *
     * $collection is determined from $parent::$_nested[$class]
     * it is expected to be a double value array nested Annotation
     * with the second value being the mapping index $property.
     *
     * @see OA\AbstractAnnotation::$_nested
     */
    public static function getIndexedCollectionItem(
        OA\AbstractAnnotation $parent,
        string $class,
        mixed $value,
    ): OA\AbstractAnnotation {
        $nested = $parent::$_nested;
        [$collection, $property] = $nested[$class];

        $key = self::searchIndexedCollectionItem(
            $parent->{$collection} && $parent->{$collection} !== Generator::UNDEFINED ? $parent->{$collection} : [],
            $property,
            $value,
        );

        if ($key === false) {
            $key = self::createCollectionItem($parent, $collection, $class, [$property => $value]);
        }

        return $parent->{$collection}[$key];
    }

    /**
     * Search for an Annotation within $collection that has all members set
     * to the respective values in the associative array $properties.
     */
    public static function searchCollectionItem(array $collection, array $properties): int|string|null
    {
        foreach ($collection ?: [] as $i => $child) {
            foreach ($properties as $k => $prop) {
                if ($child->{$k} !== $prop) {
                    continue 2;
                }
            }

            return $i;
        }

        return null;
    }

    /**
     * Search for an Annotation within the $collection that has its member $index set to $value.
     */
    public static function searchIndexedCollectionItem(
        array $collection,
        string $member,
        mixed $value,
    ): false|int|string {
        return array_search($value, array_column($collection, $member), true);
    }

    /**
     * Create a new Object of $class with members $properties within $parent->{$collection}[]
     * and return the created index.
     */
    public static function createCollectionItem(
        OA\AbstractAnnotation $parent,
        string $collection,
        string $class,
        array $properties = [],
    ): int {
        if ($parent->{$collection} === Generator::UNDEFINED) {
            $parent->{$collection} = [];
        }

        $key = \count($parent->{$collection} ?: []);
        $parent->{$collection}[$key] = self::createChild($parent, $class, $properties);

        return $key;
    }

    /**
     * Create a new Object of $class with members $properties and set the context parent to be $parent.
     *
     * @throws \InvalidArgumentException at an attempt to pass in properties that are found in $parent::$_nested
     */
    public static function createChild(
        OA\AbstractAnnotation $parent,
        string $class,
        array $properties = [],
    ): OA\AbstractAnnotation {
        $nesting = self::getNestingIndexes($class);

        if (count(array_intersect(array_keys($properties), $nesting))) {
            throw new \InvalidArgumentException('Nesting Annotations is not supported.');
        }

        return new $class(
            array_merge($properties, ['_context' => self::createContext(['nested' => $parent], $parent->_context)]),
        );
    }

    /**
     * Create a new Context with members $properties and parent context $parent.
     *
     * @see Context
     */
    public static function createContext(array $properties = [], ?Context $parent = null): Context
    {
        $properties['comment'] = '';

        return new Context($properties, $parent);
    }

    /**
     * Merge $from into $annotation. $overwrite is only used for leaf scalar values.
     *
     * The main purpose is to create a Swagger Object from array config values
     * in the structure of a json serialized Swagger object.
     */
    public static function merge(
        OA\AbstractAnnotation $annotation,
        array|\ArrayObject|OA\AbstractAnnotation $from,
        bool $overwrite = false,
    ): void {
        if (\is_array($from)) {
            self::mergeFromArray($annotation, $from, $overwrite);
        } elseif (\is_a($from, OA\AbstractAnnotation::class)) {
            assert($from instanceof OA\AbstractAnnotation);
            self::mergeFromArray($annotation, json_decode(json_encode($from), true), $overwrite);
        } elseif (\is_a($from, \ArrayObject::class)) {
            assert($from instanceof \ArrayObject);
            self::mergeFromArray($annotation, $from->getArrayCopy(), $overwrite);
        }
    }

    /**
     * @return array<string>
     */
    //phpcs:disable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    public static function determinePossiblePaths(string $path): array
    {
        $segments = array_reverse(explode('/', $path));

        $requiredPathPrefix = '';

        while (count($segments) > 0) {
            $segment = array_pop($segments);
            if (!str_ends_with($segment, '?}')) {
                $requiredPathPrefix .= ($requiredPathPrefix !== '' ? '/' : '') . $segment;

                continue;
            }
            $segments[] = $segment;

            break;
        }

        $optionalSegments = [];
        $invalidSegments = [];

        foreach ($segments as $segment) {
            if (str_ends_with($segment, '?}') && count($invalidSegments) === 0) {
                $optionalSegments[] = $segment;

                continue;
            }
            $invalidSegments[] = $segment;
        }

        while (count($invalidSegments) > 0) {
            $segment = array_pop($invalidSegments);
            if (!str_ends_with($segment, '?}')) {
                $requiredPathPrefix .= ($requiredPathPrefix !== '' ? '/' : '') . $segment;

                continue;
            }
            $updatedSegment = substr($segment, 0, -2) . '}';
            $requiredPathPrefix .= ($requiredPathPrefix !== '' ? '/' : '') . $updatedSegment;
        }

        $possiblePaths = [$requiredPathPrefix];
        $optionalPath = $requiredPathPrefix;

        while (count($optionalSegments) > 0) {
            $segment = array_pop($optionalSegments);
            $updatedSegment = substr($segment, 0, -2) . '}';
            $optionalPath .= ($optionalPath !== '' ? '/' : '') . $updatedSegment;
            $possiblePaths[] = $optionalPath;
        }

        return $possiblePaths;
    }
    //phpcs:enable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh

    private static function mergeFromArray(OA\AbstractAnnotation $annotation, array $properties, bool $overwrite): void
    {
        $done = [];

        foreach ($annotation::$_nested as $className => $propertyName) {
            if (\is_string($propertyName)) {
                if (array_key_exists($propertyName, $properties)) {
                    self::mergeChild($annotation, $className, $properties[$propertyName], $overwrite);
                    $done[] = $propertyName;
                }
            } elseif (\array_key_exists($propertyName[0], $properties)) {
                $collection = $propertyName[0];
                $property = $propertyName[1] ?? null;
                self::mergeCollection($annotation, $className, $property, $properties[$collection], $overwrite);
                $done[] = $collection;
            }
        }

        $defaults = \get_class_vars($annotation::class);

        foreach ($annotation::$_types as $propertyName => $type) {
            if (array_key_exists($propertyName, $properties)) {
                self::mergeTyped($annotation, $propertyName, $type, $properties, $defaults, $overwrite);
                $done[] = $propertyName;
            }
        }

        foreach ($properties as $propertyName => $value) {
            if ($propertyName === '$ref') {
                $propertyName = 'ref';
            }
            if (!\in_array($propertyName, $done, true)) {
                self::mergeProperty($annotation, $propertyName, $value, $defaults[$propertyName], $overwrite);
            }
        }
    }

    /**
     * Merge $from into $annotation. $overwrite is only used for leaf scalar values.
     *
     * The main purpose is to create a Swagger Object from array config values
     */
    private static function mergeChild(
        OA\AbstractAnnotation $annotation,
        string $className,
        array|ArrayObject|OA\AbstractAnnotation $value,
        bool $overwrite,
    ): void {
        self::merge(self::getChild($annotation, $className), $value, $overwrite);
    }

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint */
    private static function mergeCollection(
        OA\AbstractAnnotation $annotation,
        string $className,
        $property,
        $items,
        bool $overwrite,
    ): void {
        if ($property !== null) {
            foreach ($items as $prop => $value) {
                $child = self::getIndexedCollectionItem($annotation, $className, (string) $prop);
                self::merge($child, $value);
            }
        } else {
            $nesting = self::getNestingIndexes($className);
            foreach ($items as $props) {
                $create = [];
                $merge = [];
                foreach ($props as $k => $v) {
                    if (\in_array($k, $nesting, true)) {
                        $merge[$k] = $v;
                    } else {
                        $create[$k] = $v;
                    }
                }
                self::merge(self::getCollectionItem($annotation, $className, $create), $merge, $overwrite);
            }
        }
    }

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint */
    private static function mergeTyped(
        OA\AbstractAnnotation $annotation,
        string $propertyName,
        $type,
        array $properties,
        array $defaults,
        bool $overwrite,
    ): void {
        if (is_string($type) && strpos($type, '[') === 0) {
            $innerType = substr($type, 1, -1);

            if (!$annotation->{$propertyName} || $annotation->{$propertyName} === Generator::UNDEFINED) {
                $annotation->{$propertyName} = [];
            }

            if (!class_exists($innerType)) {
                /* type is declared as array in @see OA\AbstractAnnotation::$_types */
                $annotation->{$propertyName} = array_unique(array_merge(
                    $annotation->{$propertyName},
                    $properties[$propertyName],
                ));

                return;
            }

            // $type == [Schema] for instance
            foreach ($properties[$propertyName] as $child) {
                $annotation->{$propertyName}[] = $annot = self::createChild($annotation, $innerType, []);
                self::merge($annot, $child, $overwrite);
            }
        } else {
            self::mergeProperty(
                $annotation,
                $propertyName,
                $properties[$propertyName],
                $defaults[$propertyName],
                $overwrite,
            );
        }
    }

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint */
    private static function mergeProperty(
        OA\AbstractAnnotation $annotation,
        string $propertyName,
        $value,
        $default,
        bool $overwrite,
    ): void {
        if ($overwrite === true || $default === $annotation->{$propertyName}) {
            $annotation->{$propertyName} = $value;
        }
    }

    /** @param class-string $class */
    private static function getNestingIndexes(string $class): array
    {
        return array_values(array_map(
            static fn ($value) => \is_array($value)
                    ? $value[0]
                    : $value,
            $class::$_nested,
        ));
    }
}
