# Dormant
Dabble-based Ornament adapter

A simple [Ornament adapter](http://ornament.monomelodies.nl) using the
[Dabble database abstraction](http://dabble.monomelodies.nl).

## Installation

### Composer (recommended)
```$ composer require monomelodies/dormant```

### Manual
1. Download or clone the repo;
2. Add '/path/to/dormant/src' as a classmap to `Dormant\\` in your PSR-4 config.

## Usage
```php
<?php

use Dormant\Dabble;

class MyModel
{
    use Dabble;

    public function __construct()
    {
        // Assuming $adapter is your Dabble adapter...
        $this->addDabbleAdapter($GLOBALS['adapter']);
    }
}

