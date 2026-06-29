# Green ORM Relation Aggregation

## Goal

Green relation aggregation attaches computed relation values to parent models without N+1 queries:

```php
$posts = (new PostTable())
    ->includeCount(['comments', 'likes'])
    ->includeAvg('reviews:rating')
    ->fetchAll();
```

The resulting attributes follow `{relation}_{operator}` or `{relation}_{operator}_{column}`:

```json
{
  "comments_count": 15,
  "likes_count": 42,
  "reviews_avg_rating": 4.6
}
```

## Folder Structure

```text
src/Database/
  Database.php
  Model.php
  Table.php
  RelationAggregate/
    AggregateOperatorInterface.php
    AggregateOperatorRegistry.php
    RelationAggregateAlias.php
    RelationAggregateLoader.php
    RelationAggregateParser.php
    RelationAggregateRequest.php
    RelationMetadata.php
    RelationMetadataResolver.php
    Exceptions/
    Operators/
```

## Pipeline

1. Public ORM API records aggregate requests on `Table`.
2. IQL parsing detects aggregate operations inside include segments.
3. Relation metadata is resolved from the table's explicit `$relations` map.
4. Requests for the same relation are batched into one grouped query.
5. Values are normalized by the aggregate operator and attached to parent models.
6. Nested aggregate requests execute from the immediate parent collection after that collection is eager-loaded.

## Public API

```php
$table->includeCount('comments');
$table->includeExists('comments');
$table->includeSum('orders:total');
$table->includeAvg('reviews:rating');
$table->includeMin('orders:total');
$table->includeMax('orders:total');
$table->includeCustom('orders:profit_margin', 'median');
```

Arrays are supported:

```php
$table->includeCount(['comments', 'likes']);
```

Nested programmatic paths are supported by placing the aggregate on the terminal relation:

```php
$table->includeCount('comments.likes');
```

## IQL Grammar Update

```text
include      := path ("," path)*
path         := segment ("." segment)*
segment      := identifier ["(" operation ("," operation)* ")"]
operation    := constraint | aggregate
constraint   := ("limit" | "offset" | "order" | "select" | "filter") ":" value
aggregate    := ("count" | "exists") | aggregateWithColumn
aggregateWithColumn := ("sum" | "avg" | "min" | "max" | customOperator) ":" identifier
```

Examples:

```php
$table->include('comments(count)');
$table->include('comments(limit:5,count)');
$table->include('orders(sum:price)');
$table->include('reviews(avg:rating)');
$table->include('comments.likes(count)');
```

## SQL Strategy

Green uses grouped relation queries rather than correlated subqueries per row.

For `Post hasMany comments` with count and average:

```sql
SELECT
  r."post_id" AS __green_parent_key,
  COUNT(*) AS "__agg_0",
  AVG(r."rating") AS "__agg_1"
FROM "comments" r
WHERE r."post_id" IN (:green_parent_keys)
GROUP BY r."post_id"
```

For many-to-many:

```sql
SELECT
  p."user_id" AS __green_parent_key,
  COUNT(*) AS "__agg_0"
FROM "user_roles" p
INNER JOIN "roles" r ON r."id" = p."role_id"
WHERE p."user_id" IN (:green_parent_keys)
GROUP BY p."user_id"
```

## Responsibilities

- `Table`: ORM entry point, eager loading, IQL integration, and aggregate request orchestration.
- `RelationAggregateRequest`: immutable relation/operator/column/alias request.
- `RelationAggregateParser`: parses programmatic `relation:column` aggregate inputs.
- `RelationMetadataResolver`: validates relation names and resolves related model metadata.
- `RelationAggregateLoader`: builds and executes one batch query per relation path.
- `AggregateOperatorInterface`: extension contract for SQL expression and result normalization.
- `AggregateOperatorRegistry`: driver-like registry for built-in and custom operators.
- `RelationAggregateAlias`: naming convention helper.

## Error Handling

The aggregation subsystem throws explicit exceptions for invalid relation names, invalid aggregate columns, invalid syntax, and unknown operators. Unknown relations include available relation names in the message.

## Extensibility

Custom aggregate operators implement `AggregateOperatorInterface` and are registered on the table:

```php
$table->registerAggregateOperator(new MedianOperator());
$table->includeCustom('orders:total', 'median');
$table->include('orders(median:total)');
```

Custom operators can control whether a column is required, generate SQL expressions, and normalize database results.

## Performance Notes

- Aggregates are loaded in batches using `WHERE key IN (...) GROUP BY key`.
- Multiple aggregate operations on the same relation share one query.
- Nested aggregates execute only after the parent relation has been batch-loaded.
- Default values are assigned before querying, so missing groups become `0`, `false`, or `null` without extra queries.
- Relation definitions remain explicit; there is no global relation registry or hidden model magic.
