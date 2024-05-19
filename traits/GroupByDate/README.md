# GroupByDay Trait 

The `GroupByDay` Trait is a reusable Laravel trait that provides functionality to group records by day or by hour and apply an aggregate function (like `sum`, `count`, `avg`) to a specified column. This trait can be used across different Eloquent models in a Laravel application.

## Installation

1. Copy the `GroupByDayTrait.php` file into the `app/Traits` directory of your Laravel application.

2. Include the trait in any model where you want to use the grouping functionality.

## Usage

### Including the Trait in a Model

To use the trait in a model, include it using the `use` keyword within your model class:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\GroupByDay;

class Transaction extends Model
{
    use GroupByDay;

    // Other model properties and methods
}

```
Then you can use it as :
```php
// Group by each day and apply the aggregate method on the 'amount' column within the date range
        $groupedData = Transaction::groupByDay('amount', $aggregate, 'created_at', $from, $to);
```